<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { uploadImport } from '../api/imports'

const router = useRouter()
const file = ref(null)
const loading = ref(false)
const error = ref(null)

function onFile(e) {
  const f = e.target.files?.[0]
  file.value = f || null
  error.value = null
}

async function submit() {
  error.value = null
  if (!file.value) {
    error.value = 'Wybierz plik CSV, JSON lub XML.'
    return
  }
  loading.value = true
  try {
    const body = await uploadImport(file.value)
    const id = body?.data?.id
    if (id) {
      await router.push({ name: 'import-detail', params: { id: String(id) } })
    } else {
      error.value = 'Nieoczekiwana odpowiedź serwera.'
    }
  } catch (e) {
    const msg = e.response?.data?.message
    const errs = e.response?.data?.errors
    if (errs?.file?.[0]) {
      error.value = errs.file[0]
    } else if (typeof msg === 'string') {
      error.value = msg
    } else {
      error.value = 'Nie udało się wysłać pliku. Sprawdź połączenie z API.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="card">
    <h1>Upload pliku</h1>
    <p class="lead">
      Dozwolone formaty: <strong>CSV</strong>, <strong>JSON</strong>, <strong>XML</strong> (max 50 MB).
    </p>

    <div v-if="error" class="alert alert-error" role="alert">{{ error }}</div>

    <form class="form" @submit.prevent="submit">
      <label class="label">
        <span>Plik</span>
        <input type="file" accept=".csv,.json,.xml,text/csv,application/json,application/xml,text/xml" @change="onFile" />
      </label>
      <button type="submit" class="btn" :disabled="loading">
        {{ loading ? 'Wysyłanie…' : 'Wyślij import' }}
      </button>
    </form>
  </div>
</template>

<style scoped>
.card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.5rem 1.75rem;
}

h1 {
  margin: 0 0 0.5rem;
  font-size: 1.35rem;
}

.lead {
  margin: 0 0 1.25rem;
  color: var(--muted);
  font-size: 0.95rem;
}

.form {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 1rem;
}

.label {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  width: 100%;
  max-width: 28rem;
}

.label span {
  font-size: 0.85rem;
  color: var(--muted);
}

.btn {
  padding: 0.55rem 1.1rem;
  border-radius: 8px;
  border: none;
  background: var(--accent);
  color: #fff;
  font-weight: 600;
}

.btn:hover:not(:disabled) {
  background: var(--accent-hover);
}

.btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.alert {
  padding: 0.65rem 0.85rem;
  border-radius: 8px;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.alert-error {
  background: rgba(255, 69, 58, 0.12);
  border: 1px solid rgba(255, 69, 58, 0.35);
  color: #ffb4ad;
}
</style>
