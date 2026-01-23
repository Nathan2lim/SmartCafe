import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Extra, ExtraCreate, ExtraUpdate } from '@/types'
import { extrasService } from '@/services/extras.service'

export const useExtrasStore = defineStore('extras', () => {
  const extras = ref<Extra[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const lowStockExtras = computed(() => extras.value.filter(e =>
    e.stockQuantity <= e.lowStockThreshold
  ))
  const availableExtras = computed(() => extras.value.filter(e => e.available))

  async function fetchExtras(): Promise<void> {
    loading.value = true
    error.value = null

    try {
      extras.value = await extrasService.getAll()
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      error.value = axiosError.response?.data?.message || 'Erreur lors du chargement des extras'
    } finally {
      loading.value = false
    }
  }

  async function createExtra(extra: ExtraCreate): Promise<Extra | null> {
    error.value = null

    try {
      const newExtra = await extrasService.create(extra)
      extras.value.push(newExtra)
      return newExtra
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      error.value = axiosError.response?.data?.message || 'Erreur lors de la création de l\'extra'
      return null
    }
  }

  async function updateExtra(id: number, extra: ExtraUpdate): Promise<Extra | null> {
    error.value = null

    try {
      const updatedExtra = await extrasService.update(id, extra)
      const index = extras.value.findIndex(e => e.id === id)
      if (index !== -1) {
        extras.value[index] = updatedExtra
      }
      return updatedExtra
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      error.value = axiosError.response?.data?.message || 'Erreur lors de la mise à jour de l\'extra'
      return null
    }
  }

  async function deleteExtra(id: number): Promise<boolean> {
    error.value = null

    try {
      await extrasService.delete(id)
      extras.value = extras.value.filter(e => e.id !== id)
      return true
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      error.value = axiosError.response?.data?.message || 'Erreur lors de la suppression de l\'extra'
      return false
    }
  }

  async function restockExtra(id: number, quantity: number): Promise<boolean> {
    error.value = null

    try {
      const updatedExtra = await extrasService.restock(id, quantity)
      const index = extras.value.findIndex(e => e.id === id)
      if (index !== -1) {
        extras.value[index] = updatedExtra
      }
      return true
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      error.value = axiosError.response?.data?.message || 'Erreur lors du réapprovisionnement'
      return false
    }
  }

  function getExtraById(id: number): Extra | undefined {
    return extras.value.find(e => e.id === id)
  }

  return {
    extras,
    loading,
    error,
    lowStockExtras,
    availableExtras,
    fetchExtras,
    createExtra,
    updateExtra,
    deleteExtra,
    restockExtra,
    getExtraById
  }
})
