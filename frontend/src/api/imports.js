import { api } from './client'

export async function uploadImport(file) {
  const formData = new FormData()
  formData.append('file', file)
  const { data } = await api.post('/imports', formData)
  return data
}

export async function fetchImports(page = 1, perPage = 15) {
  const { data } = await api.get('/imports', {
    params: { page, per_page: perPage },
  })
  return data
}

export async function fetchImportDetail(id, page = 1, perPage = 50) {
  const { data } = await api.get(`/imports/${id}`, {
    params: { page, per_page: perPage },
  })
  return data
}
