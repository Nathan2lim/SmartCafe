<script setup lang="ts">
import { computed } from 'vue'
import type { Order, OrderStatus, User, Product, Extra } from '@/types'
import { ORDER_STATUS_TRANSITIONS } from '@/types'
import { formatPrice, formatDateTime } from '@/utils/formatters'
import StatusBadge from '@/components/common/StatusBadge.vue'
import Modal from '@/components/common/Modal.vue'

const props = defineProps<{
  order: Order
}>()

const emit = defineEmits<{
  close: []
  'update-status': [status: OrderStatus]
}>()

const customerName = computed(() => {
  const customer = props.order.customer
  if (typeof customer === 'string') return 'Client'
  return `${(customer as User).firstName} ${(customer as User).lastName}`
})

const customerEmail = computed(() => {
  const customer = props.order.customer
  if (typeof customer === 'string') return ''
  return (customer as User).email
})

const nextStatuses = computed(() => ORDER_STATUS_TRANSITIONS[props.order.status])

function getProductName(product: Product | string): string {
  if (typeof product === 'string') return 'Produit'
  return product.name
}

function getExtraName(extra: Extra | string): string {
  if (typeof extra === 'string') return 'Extra'
  return extra.name
}

function getActionLabel(status: OrderStatus): string {
  const labels: Record<OrderStatus, string> = {
    confirmed: 'Confirmer la commande',
    preparing: 'Démarrer la préparation',
    ready: 'Marquer comme prête',
    delivered: 'Marquer comme livrée',
    cancelled: 'Annuler la commande',
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
  <Modal :title="`Commande ${order.orderNumber}`" size="lg" @close="emit('close')">
    <!-- Status and customer -->
    <div class="grid grid-cols-2 gap-4 mb-6">
      <div>
        <p class="text-sm text-gray-500 mb-1">Statut</p>
        <StatusBadge :status="order.status" />
      </div>
      <div>
        <p class="text-sm text-gray-500 mb-1">Client</p>
        <p class="font-medium text-gray-900">{{ customerName }}</p>
        <p v-if="customerEmail" class="text-sm text-gray-600">{{ customerEmail }}</p>
      </div>
    </div>

    <!-- Dates -->
    <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
      <div>
        <p class="text-gray-500">Créée le</p>
        <p class="text-gray-900">{{ formatDateTime(order.createdAt) }}</p>
      </div>
      <div v-if="order.tableNumber">
        <p class="text-gray-500">Table</p>
        <p class="text-gray-900">{{ order.tableNumber }}</p>
      </div>
      <div v-if="order.confirmedAt">
        <p class="text-gray-500">Confirmée</p>
        <p class="text-gray-900">{{ formatDateTime(order.confirmedAt) }}</p>
      </div>
      <div v-if="order.readyAt">
        <p class="text-gray-500">Prête</p>
        <p class="text-gray-900">{{ formatDateTime(order.readyAt) }}</p>
      </div>
      <div v-if="order.deliveredAt">
        <p class="text-gray-500">Livrée</p>
        <p class="text-gray-900">{{ formatDateTime(order.deliveredAt) }}</p>
      </div>
    </div>

    <!-- Items -->
    <div class="mb-6">
      <h4 class="text-sm font-medium text-gray-900 mb-3">Articles</h4>
      <div class="space-y-3">
        <div
          v-for="item in order.items"
          :key="item.id"
          class="bg-gray-50 rounded-lg p-3"
        >
          <div class="flex items-start justify-between">
            <div>
              <p class="font-medium text-gray-900">
                {{ item.quantity }}x {{ getProductName(item.product) }}
              </p>
              <p class="text-sm text-gray-600">{{ formatPrice(item.unitPrice) }} / unité</p>
              <!-- Extras -->
              <div v-if="item.extras && item.extras.length > 0" class="mt-1">
                <p
                  v-for="extra in item.extras"
                  :key="extra.id"
                  class="text-xs text-gray-500"
                >
                  + {{ extra.quantity }}x {{ getExtraName(extra.extra) }}
                  ({{ formatPrice(extra.unitPrice) }})
                </p>
              </div>
              <!-- Special instructions -->
              <p v-if="item.specialInstructions" class="text-xs text-gray-500 mt-1 italic">
                "{{ item.specialInstructions }}"
              </p>
            </div>
            <p class="font-medium text-gray-900">{{ formatPrice(item.subtotal) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Notes -->
    <div v-if="order.notes" class="mb-6">
      <h4 class="text-sm font-medium text-gray-900 mb-2">Notes</h4>
      <p class="text-sm text-gray-600 bg-gray-50 rounded-lg p-3">{{ order.notes }}</p>
    </div>

    <!-- Total -->
    <div class="flex items-center justify-between py-4 border-t border-gray-200">
      <p class="text-lg font-semibold text-gray-900">Total</p>
      <p class="text-xl font-bold text-primary-600">{{ formatPrice(order.totalAmount) }}</p>
    </div>

    <!-- Actions -->
    <template #footer>
      <div class="flex items-center gap-3">
        <button @click="emit('close')" class="btn btn-secondary">
          Fermer
        </button>
        <div class="flex-1" />
        <button
          v-for="status in nextStatuses"
          :key="status"
          @click="emit('update-status', status)"
          :class="getActionClass(status)"
        >
          {{ getActionLabel(status) }}
        </button>
      </div>
    </template>
  </Modal>
</template>
