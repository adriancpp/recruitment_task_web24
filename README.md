# System importu transakcji bankowych

Monorepo: **Laravel 12 (API)** w `backend/` oraz **Vue 3 + Vite** w `frontend/`. Import obsługuje pliki **CSV, JSON i XML**; poprawne rekordy trafiają do `transactions`, błędne do `import_logs`, podsumowanie do `imports`.

## Wymagania

- **PHP** ≥ 8.2, **Composer**
- **MariaDB** lub MySQL (lokalnie; w testach PHPUnit używany jest SQLite w pamięci)
- **Node.js** (projekt frontu testowany na Node 18+; Vite 5)
- (Opcjonalnie) **Redis** — nie jest wymagany; domyślnie kolejka i cache mogą iść przez bazę (`QUEUE_CONNECTION`, `CACHE_STORE` w Laravel)

## Struktura katalogów

| Katalog / plik | Opis |
|----------------|------|
| `backend/` | Aplikacja Laravel: API, migracje, job importu, parsery, walidacja |
| `frontend/` | SPA Vue: upload, lista importów, szczegóły z logami |
| `samples/` | Przykładowe pliki do ręcznych testów i testów automatycznych |

## Backend (Laravel)

### 1. Instalacja i konfiguracja

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

W pliku `.env` ustaw połączenie z bazą, np.:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bank_imports_web24
DB_USERNAME=root
DB_PASSWORD=root
```

Na macOS przy lokalnym MariaDB często bezpieczniej jest użyć **`127.0.0.1`** zamiast `localhost`, żeby wymusić TCP zamiast socketa.

### 2. Migracje

```bash
php artisan migrate
```

### 3. Uruchomienie API

```bash
php artisan serve --host=127.0.0.1 --port=8001
```

API pod adresem: `http://127.0.0.1:8001` (prefiks tras: `/api/...`).

### 4. Kolejka importu

Przetwarzanie pliku jest uruchamiane przez job `ProcessImportJob`. W kontrolerze używane jest **`Bus::dispatchSync()`**, żeby po `POST /api/imports` od razu zwrócić finalny status importu bez osobnego workera. Aby przejść na asynchroniczną kolejkę (`database` itd.), zamień wywołanie na `ProcessImportJob::dispatch(...)` i uruchom:

```bash
php artisan queue:work
```

## Frontend (Vue 3 + Vite)

### 1. Instalacja

```bash
cd frontend
npm install
```

### 2. Konfiguracja (opcjonalnie)

Skopiuj `frontend/.env.example` → `frontend/.env` i ewentualnie ustaw:

- `VITE_PROXY_TARGET` — host Laravela dla proxy w trybie dev (domyślnie `http://127.0.0.1:8001`)
- `VITE_API_BASE_URL` — pełny URL API (np. na produkcji); jeśli puste, używane jest `/api` (w dev przechodzi przez proxy Vite)

### 3. Uruchomienie deweloperskie

W osobnym terminalu (przy działającym `php artisan serve`):

```bash
cd frontend
npm run dev
```

Aplikacja frontowa: **http://localhost:5173** — żądania do `/api` są proxyowane na backend z `VITE_PROXY_TARGET`.

### 4. Build produkcyjny

```bash
cd frontend
npm run build
```

Wynik w `frontend/dist/` (katalog ignorowany przez Git).

## Endpointy API

| Metoda | Ścieżka | Opis |
|--------|---------|------|
| `POST` | `/api/imports` | Upload: pole formularza `file` (csv / json / xml), max ~50 MB |
| `GET` | `/api/imports` | Lista importów; query: `page`, `per_page` (domyślnie 15, max 100) |
| `GET` | `/api/imports/{id}` | Szczegóły importu + paginowane logi; query: `page`, `per_page` (domyślnie 50, max 200) |

### Przykład: upload CSV (curl)

```bash
curl -sS -X POST "http://127.0.0.1:8001/api/imports" \
  -H "Accept: application/json" \
  -F "file=@samples/valid.csv"
```

### Przykład: lista importów

```bash
curl -sS "http://127.0.0.1:8001/api/imports?page=1&per_page=10" \
  -H "Accept: application/json"
```

### Przykład: szczegóły i logi

```bash
curl -sS "http://127.0.0.1:8001/api/imports/1" \
  -H "Accept: application/json"
```

Odpowiedź `GET /api/imports/{id}` ma kształt: `{ "import": { ... }, "logs": { "data": [...], "links": {...}, "meta": {...} } }`.

## Testy automatyczne

```bash
cd backend
php artisan test
```

Testy Feature korzystają z plików w `samples/` (ścieżka względem root repozytorium). Parser XML używa `libxml_use_internal_errors`, żeby błędy składni nie kończyły się przypadkowym `ErrorException` z handlera PHP.

## Przykładowe pliki

W katalogu **`samples/`** znajdują się m.in.:

- `valid.csv`, `valid.json`, `valid.xml` — wyłącznie poprawne rekordy  
- `partial.csv` — jeden poprawny, jeden z błędnym numerem konta  
- `invalid.json` — uszkodzony JSON (np. do sprawdzenia obsługi błędów)

Format kolumn zgodny z treścią zadania rekrutacyjnego (`transaction_id`, `account_number`, `transaction_date`, `amount`, `currency`).

## CORS

Opublikowany jest `backend/config/cors.php` (ścieżki `api/*`). Przy osobnych originach frontu i API dostosuj `allowed_origins` pod środowisko produkcyjne.

## Decyzje techniczne (skrót)

- Walidacja **IBAN** przez własną regułę `App\Rules\Iban` (mod 97), bo Laravel 12 nie dostarcza wbudowanej reguły `iban` w walidatorze.
- Brak dodatkowych kolumn w tabeli `imports` poza specyfikacją — plik zapisany jest pod ścieżką `storage/app/imports/{id}/{basename(file_name)}` (metoda `Import::storedFileRelativePath()`).
- Import rekordów: błędy walidacji / zapisu pojedynczego wiersza nie przerywają całego pliku; krytyczny błąd parsowania pliku kończy import statusem `failed` z wpisem w `import_logs`.