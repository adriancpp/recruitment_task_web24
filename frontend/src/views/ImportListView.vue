<script setup>
import { ref, watch, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { fetchImports } from '../api/imports'
import StatusBadge from '../components/StatusBadge.vue'

const loading = ref(true)
const error = ref(null)
const rows = ref([])
const meta = ref(null)
const page = ref(1)
const perPage = ref(15)

async function load() {
  loading.value = true
  error.value = null
  try {
    const data = await fetchImports(page.value, perPage.value)
    rows.value = data.data ?? []
    meta.value = data.meta ?? null
  } catch (e) {
    error.value =
      e.response?.data?.message || 'Nie udało się pobrać listy importów.'
    rows.value = []
    meta.value = null
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(page, load)

function go(p) {
  page.value = p
}
</script>

<template>
  <div class="card">
    <h1>Historia importów</h1>

    <div v-if="loading" class="muted">Ładowanie…</div>
    <div v-else-if="error" class="alert alert-error" role="alert">{{ error }}</div>
    <div v-else-if="!rows.length" class="empty">Brak importów. <RouterLink to="/">Dodaj pierwszy</RouterLink>.</div>

    <template v-else>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Plik</th>
              <th>Rekordy</th>
              <th>OK</th>
              <th>Błędy</th>
              <th>Status</th>
              <th>Data</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id">
              <td>{{ row.id }}</td>
              <td class="mono">{{ row.file_name }}</td>
              <td>{{ row.total_records }}</td>
              <td>{{ row.successful_records }}</td>
              <td>{{ row.failed_records }}</td>
              <td><StatusBadge :status="row.status" /></td>
              <td class="muted small">{{ row.created_at }}</td>
              <td>
                <RouterLink :to="{ name: 'import-detail', params: { id: row.id } }">Szczegóły</RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="meta && meta.last_page > 1" class="pager">
        <button type="button" class="btn ghost" :disabled="page <= 1" @click="go(page - 1)">Poprzednia</button>
        <span class="muted">Strona {{ meta.current_page }} / {{ meta.last_page }}</span>
        <button type="button" class="btn ghost" :disabled="page >= meta.last_page" @click="go(page + 1)">
          Następna
        </button>
      </div>
    </template>
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
  margin: 0 0 1rem;
  font-size: 1.35rem;
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

th,
td {
  text-align: left;
  padding: 0.55rem 0.65rem;
  border-bottom: 1px solid var(--border);
}

th {
  color: var(--muted);
  font-weight: 600;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  max-width: 14rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.muted {
  color: var(--muted);
}

.small {
  font-size: 0.8rem;
}

.empty {
  color: var(--muted);
  padding: 1rem 0;
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
  padding: 0.65rem 0.85rem;
  border-radius: 8px;
  background: rgba(255, 69, 58, 0.12);
  border: 1px solid rgba(255, 69, 58, 0.35);
  color: #ffb4ad;
}
</style>
