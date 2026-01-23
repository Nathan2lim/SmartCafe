<script setup lang="ts">
import { onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useOrdersStore } from '@/stores/orders'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import type { OrderStatus, User, Product, Extra } from '@/types'
import { ORDER_STATUS_TRANSITIONS } from '@/types'
import { formatPrice, formatDateTime } from '@/utils/formatters'
import StatusBadge from '@/components/common/StatusBadge.vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const route = useRoute()
const router = useRouter()
const ordersStore = useOrdersStore()
const toast = useToast()
const confirmDialog = useConfirm()

const orderId = computed(() => Number(route.params.id))

onMounted(() => {
  ordersStore.fetchOrder(orderId.value)
})

const order = computed(() => ordersStore.currentOrder)
const nextStatuses = computed(() => order.value ? ORDER_STATUS_TRANSITIONS[order.value.status] : [])

const customerName = computed(() => {
  if (!order.value) return ''
  const customer = order.value.customer
  if (typeof customer === 'string') return 'Client'
  return `${(customer as User).firstName} ${(customer as User).lastName}`
})

const customerEmail = computed(() => {
  if (!order.value) return ''
  const customer = order.value.customer
  if (typeof customer === 'string') return ''
  return (customer as User).email
})

function getProductName(product: Product | string): string {
  if (typeof product === 'string') return 'Produit'
  return product.name
}

function getExtraName(extra: Extra | string): string {
  if (typeof extra === 'string') return 'Extra'
  return extra.name
}

async function handleUpdateStatus(status: OrderStatus) {
  if (status === 'cancelled') {
    const confirmed = await confirmDialog.confirm({
      title: 'Annuler la commande',
      message: 'Êtes-vous sûr de vouloir annuler cette commande ? Cette action est irréversible.',
      confirmText: 'Annuler la commande',
      type: 'danger'
    })
    if (!confirmed) return
  }

  const success = await ordersStore.updateStatus(orderId.value, status)
  if (success) {
    toast.success('Statut mis à jour')
  } else {
    toast.error(ordersStore.error || 'Erreur lors de la mise à jour')
  }
}

function getActionLabel(status: OrderStatus): string {
  const labels: Record<OrderStatus, string> = {
    confirmed: 'Confirmer',
    preparing: 'Préparer',
    ready: 'Prêt',
    delivered: 'Livré',
    cancelled: 'Annuler',
    pending: ''
  }
  return labels[status]
}

function getActionClass(status: OrderStatus): string {
  if (status === 'cancelled') return 'btn btn-danger'
  return 'btn btn-success'
}
</script>

<template>
  <div>
    <!-- Back button -->
    <button @click="router.back()" class="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
      </svg>
      Retour aux commandes
    </button>

    <!-- Loading -->
    <div v-if="ordersStore.loading" class="flex items-center justify-center py-12">
      <LoadingSpinner size="lg" />
    </div>

    <!-- Error -->
    <div v-else-if="ordersStore.error || !order" class="card p-6 text-center">
      <p class="text-red-600 mb-4">{{ ordersStore.error || 'Commande non trouvée' }}</p>
      <button @click="router.push('/orders')" class="btn btn-primary">
        Retour aux commandes
      </button>
    </div>

    <!-- Order details -->
    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="card p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <div class="flex items-center gap-3 mb-2">
              <h2 class="text-2xl font-bold text-gray-900">{{ order.orderNumber }}</h2>
              <StatusBadge :status="order.status" />
            </div>
            <p class="text-gray-600">{{ customerName }}</p>
            <p v-if="customerEmail" class="text-sm text-gray-500">{{ customerEmail }}</p>
          </div>
          <div class="flex items-center gap-2">
            <button
              v-for="status in nextStatuses"
              :key="status"
              @click="handleUpdateStatus(status)"
              :class="getActionClass(status)"
            >
              {{ getActionLabel(status) }}
            </button>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Items -->
        <div class="lg:col-span-2 card p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Articles</h3>
          <div class="space-y-4">
            <div
              v-for="item in order.items"
              :key="item.id"
              class="flex items-start justify-between p-4 bg-gray-50 rounded-lg"
            >
              <div class="flex-1">
                <p class="font-medium text-gray-900">
                  {{ item.quantity }}x {{ getProductName(item.product) }}
                </p>
                <p class="text-sm text-gray-600">{{ formatPrice(item.unitPrice) }} / unité</p>
                <div v-if="item.extras && item.extras.length > 0" class="mt-2 space-y-1">
                  <p
                    v-for="extra in item.extras"
                    :key="extra.id"
                    class="text-sm text-gray-500"
                  >
                    + {{ extra.quantity }}x {{ getExtraName(extra.extra) }}
                    <span class="text-gray-400">({{ formatPrice(extra.unitPrice) }})</span>
                  </p>
                </div>
                <p v-if="item.specialInstructions" class="text-sm text-gray-500 mt-2 italic">
                  "{{ item.specialInstructions }}"
                </p>
              </div>
              <p class="font-semibold text-gray-900">{{ formatPrice(item.subtotal) }}</p>
            </div>
          </div>

          <!-- Total -->
          <div class="flex items-center justify-between mt-6 pt-6 border-t border-gray-200">
            <p class="text-lg font-semibold text-gray-900">Total</p>
            <p class="text-2xl font-bold text-primary-600">{{ formatPrice(order.totalAmount) }}</p>
          </div>
        </div>

        <!-- Info -->
        <div class="space-y-6">
          <!-- Timeline -->
          <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Historique</h3>
            <div class="space-y-4">
              <div class="flex items-start gap-3">
                <div class="w-2 h-2 mt-2 rounded-full bg-gray-400" />
                <div>
                  <p class="text-sm font-medium text-gray-900">Commande créée</p>
                  <p class="text-xs text-gray-500">{{ formatDateTime(order.createdAt) }}</p>
                </div>
              </div>
              <div v-if="order.confirmedAt" class="flex items-start gap-3">
                <div class="w-2 h-2 mt-2 rounded-full bg-blue-500" />
                <div>
                  <p class="text-sm font-medium text-gray-900">Confirmée</p>
                  <p class="text-xs text-gray-500">{{ formatDateTime(order.confirmedAt) }}</p>
                </div>
              </div>
              <div v-if="order.readyAt" class="flex items-start gap-3">
                <div class="w-2 h-2 mt-2 rounded-full bg-green-500" />
                <div>
                  <p class="text-sm font-medium text-gray-900">Prête</p>
                  <p class="text-xs text-gray-500">{{ formatDateTime(order.readyAt) }}</p>
                </div>
              </div>
              <div v-if="order.deliveredAt" class="flex items-start gap-3">
                <div class="w-2 h-2 mt-2 rounded-full bg-gray-900" />
                <div>
                  <p class="text-sm font-medium text-gray-900">Livrée</p>
                  <p class="text-xs text-gray-500">{{ formatDateTime(order.deliveredAt) }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Details -->
          <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Détails</h3>
            <dl class="space-y-3 text-sm">
              <div v-if="order.tableNumber">
                <dt class="text-gray-500">Table</dt>
                <dd class="font-medium text-gray-900">{{ order.tableNumber }}</dd>
              </div>
              <div v-if="order.notes">
                <dt class="text-gray-500">Notes</dt>
                <dd class="text-gray-900">{{ order.notes }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
