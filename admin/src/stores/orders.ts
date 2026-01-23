import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Order, OrderStatus } from '@/types'
import { ordersService } from '@/services/orders.service'

export const useOrdersStore = defineStore('orders', () => {
  const orders = ref<Order[]>([])
  const currentOrder = ref<Order | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const pollingInterval = ref<ReturnType<typeof setInterval> | null>(null)

  const pendingOrders = computed(() => orders.value.filter(o => o.status === 'pending'))
  const confirmedOrders = computed(() => orders.value.filter(o => o.status === 'confirmed'))
  const preparingOrders = computed(() => orders.value.filter(o => o.status === 'preparing'))
  const readyOrders = computed(() => orders.value.filter(o => o.status === 'ready'))
  const activeOrders = computed(() => orders.value.filter(o =>
    ['pending', 'confirmed', 'preparing', 'ready'].includes(o.status)
  ))

  async function fetchOrders(status?: OrderStatus): Promise<void> {
    loading.value = true
    error.value = null

    try {
      orders.value = await ordersService.getAll(status)
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      error.value = axiosError.response?.data?.message || 'Erreur lors du chargement des commandes'
    } finally {
      loading.value = false
    }
  }

  async function fetchOrder(id: number): Promise<void> {
    loading.value = true
    error.value = null

    try {
      currentOrder.value = await ordersService.getById(id)
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      error.value = axiosError.response?.data?.message || 'Erreur lors du chargement de la commande'
    } finally {
      loading.value = false
    }
  }

  async function updateStatus(id: number, status: OrderStatus): Promise<boolean> {
    error.value = null

    try {
      const updatedOrder = await ordersService.updateStatus(id, status)
      const index = orders.value.findIndex(o => o.id === id)
      if (index !== -1) {
        orders.value[index] = updatedOrder
      }
      if (currentOrder.value?.id === id) {
        currentOrder.value = updatedOrder
      }
      return true
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      error.value = axiosError.response?.data?.message || 'Erreur lors de la mise à jour du statut'
      return false
    }
  }

  function startPolling(intervalMs: number = 10000): void {
    stopPolling()
    pollingInterval.value = setInterval(() => {
      fetchOrders()
    }, intervalMs)
  }

  function stopPolling(): void {
    if (pollingInterval.value) {
      clearInterval(pollingInterval.value)
      pollingInterval.value = null
    }
  }

  return {
    orders,
    currentOrder,
    loading,
    error,
    pendingOrders,
    confirmedOrders,
    preparingOrders,
    readyOrders,
    activeOrders,
    fetchOrders,
    fetchOrder,
    updateStatus,
    startPolling,
    stopPolling
  }
})
