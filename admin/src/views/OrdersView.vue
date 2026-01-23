<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useOrdersStore } from '@/stores/orders'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import type { OrderStatus } from '@/types'
import OrderCard from '@/components/orders/OrderCard.vue'
import OrderDetails from '@/components/orders/OrderDetails.vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import EmptyState from '@/components/common/EmptyState.vue'

const ordersStore = useOrdersStore()
const toast = useToast()
const confirmDialog = useConfirm()

const activeTab = ref<'all' | OrderStatus>('all')
const selectedOrderId = ref<number | null>(null)

const tabs = [
  { id: 'all' as const, label: 'Toutes' },
  { id: 'pending' as const, label: 'En attente' },
  { id: 'confirmed' as const, label: 'Confirmées' },
  { id: 'preparing' as const, label: 'En préparation' },
  { id: 'ready' as const, label: 'Prêtes' },
  { id: 'delivered' as const, label: 'Livrées' }
]

const filteredOrders = computed(() => {
  if (activeTab.value === 'all') return ordersStore.orders
  return ordersStore.orders.filter(o => o.status === activeTab.value)
})

const selectedOrder = computed(() => {
  if (!selectedOrderId.value) return null
  return ordersStore.orders.find(o => o.id === selectedOrderId.value) || null
})

onMounted(() => {
  ordersStore.fetchOrders()
  ordersStore.startPolling(10000)
})

onUnmounted(() => {
  ordersStore.stopPolling()
})

async function handleUpdateStatus(id: number, status: OrderStatus) {
  if (status === 'cancelled') {
    const confirmed = await confirmDialog.confirm({
      title: 'Annuler la commande',
      message: 'Êtes-vous sûr de vouloir annuler cette commande ? Cette action est irréversible.',
      confirmText: 'Annuler la commande',
      type: 'danger'
    })
    if (!confirmed) return
  }

  const success = await ordersStore.updateStatus(id, status)
  if (success) {
    toast.success('Statut mis à jour')
  } else {
    toast.error(ordersStore.error || 'Erreur lors de la mise à jour')
  }
}

function handleViewDetails(id: number) {
  selectedOrderId.value = id
}

function closeDetails() {
  selectedOrderId.value = null
}

function handleDetailsStatusUpdate(status: OrderStatus) {
  if (selectedOrderId.value) {
    handleUpdateStatus(selectedOrderId.value, status)
  }
}
</script>

<template>
  <div>
    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200">
      <nav class="flex gap-4 -mb-px overflow-x-auto">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'py-3 px-1 text-sm font-medium border-b-2 whitespace-nowrap transition-colors',
            activeTab === tab.id
              ? 'border-primary-600 text-primary-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
        >
          {{ tab.label }}
          <span
            v-if="tab.id !== 'all'"
            :class="[
              'ml-2 px-2 py-0.5 rounded-full text-xs',
              activeTab === tab.id ? 'bg-primary-100 text-primary-700' : 'bg-gray-100 text-gray-600'
            ]"
          >
            {{ ordersStore.orders.filter(o => o.status === tab.id).length }}
          </span>
        </button>
      </nav>
    </div>

    <!-- Loading -->
    <div v-if="ordersStore.loading && ordersStore.orders.length === 0" class="flex items-center justify-center py-12">
      <LoadingSpinner size="lg" />
    </div>

    <!-- Empty state -->
    <EmptyState
      v-else-if="filteredOrders.length === 0"
      title="Aucune commande"
      description="Il n'y a pas de commande correspondant à ce filtre."
      icon="orders"
    />

    <!-- Orders grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <OrderCard
        v-for="order in filteredOrders"
        :key="order.id"
        :order="order"
        @update-status="handleUpdateStatus"
        @view-details="handleViewDetails"
      />
    </div>

    <!-- Order details modal -->
    <OrderDetails
      v-if="selectedOrder"
      :order="selectedOrder"
      @close="closeDetails"
      @update-status="handleDetailsStatusUpdate"
    />
  </div>
</template>
