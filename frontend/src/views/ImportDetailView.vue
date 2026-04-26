<script setup>
import { ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { fetchImportDetail } from '../api/imports'
import StatusBadge from '../components/StatusBadge.vue'

const props = defineProps({
  id: {
    type: [String, Number],
    required: true,
  },
})

const loading = ref(true)
const error = ref(null)
const imp = ref(null)
const logs = ref([])
const logsMeta = ref(null)
const logPage = ref(1)
const perPage = ref(50)

async function load() {
  loading.value = true
  error.value = null
  try {
    const data = await fetchImportDetail(props.id, logPage.value, perPage.value)
    imp.value = data.import ?? null
    logs.value = data.logs?.data ?? []
    logsMeta.value = data.logs?.meta ?? null
  } catch (e) {
    if (e.response?.status === 404) {
      error.value = 'Nie znaleziono importu o podanym ID.'
    } else {
      error.value = e.response?.data?.message || 'Nie udało się wczytać szczegółów.'
    }
    imp.value = null
    logs.value = []
    logsMeta.value = null
  } finally {
    loading.value = false
  }
}

watch(
  () => [props.id, logPage.value],
  () => {
    load()
  },
  { immediate: true },
)

function goLogPage(p) {
  logPage.value = p
}
</script>

<template>
  <div>
    <p class="back">
      <RouterLink to="/imports">← Lista importów</RouterLink>
    </p>

    <div v-if="loading" class="muted card pad">Ładowanie…</div>
    <div v-else-if="error" class="alert alert-error card pad" role="alert">{{ error }}</div>

    <template v-else-if="imp">
      <div class="card summary">
        <div class="summary-head">
          <h1>Import #{{ imp.id }}</h1>
          <StatusBadge :status="imp.status" />
        </div>
        <dl class="grid">
          <div>
            <dt>Plik</dt>
            <dd class="mono">{{ imp.file_name }}</dd>
          </div>
          <div>
            <dt>Wszystkie rekordy</dt>
            <dd>{{ imp.total_records }}</dd>
          </div>
          <div>
            <dt>Poprawne</dt>
            <dd>{{ imp.successful_records }}</dd>
          </div>
          <div>
            <dt>Błędne</dt>
            <dd>{{ imp.failed_records }}</dd>
          </div>
          <div>
            <dt>Utworzono</dt>
            <dd class="muted">{{ imp.created_at }}</dd>
          </div>
        </dl>
      </div>

      <div class="card logs">
        <h2>Logi błędów</h2>
        <p v-if="!logs.length" class="muted empty">Brak błędów dla tego importu.</p>
        <div v-else class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>transaction_id</th>
                <th>Komunikat</th>
                <th>Data</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="log in logs" :key="log.id">
                <td>{{ log.id }}</td>
                <td class="mono">{{ log.transaction_id ?? '—' }}</td>
                <td>{{ log.error_message }}</td>
                <td class="muted small">{{ log.created_at }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="logsMeta && logsMeta.last_page > 1" class="pager">
          <button type="button" class="btn ghost" :disabled="logPage <= 1" @click="goLogPage(logPage - 1)">
            Poprzednia
          </button>
          <span class="muted">Strona {{ logsMeta.current_page }} / {{ logsMeta.last_page }}</span>
          <button
            type="button"
            class="btn ghost"
            :disabled="logPage >= logsMeta.last_page"
            @click="goLogPage(logPage + 1)"
          >
            Następna
          </button>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.back {
  margin: 0 0 1rem;
}

.card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
}

.pad {
  padding: 1.25rem 1.5rem;
}

.summary {
  padding: 1.25rem 1.5rem;
  margin-bottom: 1rem;
}

.summary-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}

h1 {
  margin: 0;
  font-size: 1.25rem;
}

h2 {
  margin: 0 0 0.75rem;
  font-size: 1.05rem;
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(11rem, 1fr));
  gap: 1rem 1.5rem;
  margin: 0;
}

dt {
  margin: 0;
  font-size: 0.75rem;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

dd {
  margin: 0.15rem 0 0;
  font-weight: 500;
}

.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  word-break: break-all;
}

.muted {
  color: var(--muted);
}

.small {
  font-size: 0.8rem;
}

.logs {
  padding: 1.25rem 1.5rem;
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.88rem;
}

th,
td {
  text-align: left;
  padding: 0.5rem 0.55rem;
  border-bottom: 1px solid var(--border);
  vertical-align: top;
}

th {
  color: var(--muted);
  font-weight: 600;
  font-size: 0.72rem;
  text-transform: uppercase;
}

.empty {
  margin: 0 0 0.5rem;
}

.pager {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-top: 1rem;
  flex-wrap: wrap;
}

.btn.ghost {
  background: transparent;
  border: 1px solid var(--border);
  color: var(--text);
  padding: 0.45rem 0.85rem;
  border-radius: 8px;
  font: inherit;
  cursor: pointer;
}

.btn.ghost:hover:not(:disabled) {
  border-color: var(--accent);
  color: var(--accent);
}

.btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.alert-error {
  background: rgba(255, 69, 58, 0.12);
  border: 1px solid rgba(255, 69, 58, 0.35);
  color: #ffb4ad;
}
</style>
