import { ref } from 'vue'
import axios from 'axios'

export function useContactos() {
  const rows = ref<any[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function list(params: { q?: string; entidade_id?: number } = {}) {
    loading.value = true; error.value = null
    try {
      const { data } = await axios.get('/api/contactos', { params })
      // Se teu controller retorna paginate(), data = {data:[...], ...}
      rows.value = Array.isArray(data?.data) ? data.data : data
    } catch (e: any) { error.value = e?.message ?? 'Erro a carregar contactos' }
    finally { loading.value = false }
  }

  async function create(payload: any) {
    loading.value = true
    try { await axios.post('/api/contactos', payload) }
    finally { loading.value = false }
  }

  async function update(id: number, payload: any) {
    loading.value = true
    try { await axios.put(`/api/contactos/${id}`, payload) }
    finally { loading.value = false }
  }

  async function remove(id: number) {
    loading.value = true
    try { await axios.delete(`/api/contactos/${id}`) }
    finally { loading.value = false }
  }

  return { rows, loading, error, list, create, update, remove }
}
