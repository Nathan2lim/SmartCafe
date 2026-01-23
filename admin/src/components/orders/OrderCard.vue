<script setup lang="ts">
import { computed } from 'vue'
import type { Order, OrderStatus } from '@/types'
import { ORDER_STATUS_TRANSITIONS } from '@/types'
import { formatPrice, formatRelativeTime } from '@/utils/formatters'
import StatusBadge from '@/components/common/StatusBadge.vue'

const props = defineProps<{
  order: Order
}>()

const emit = defineEmits<{
  'update-status': [id: number, status: OrderStatus]
  'view-details': [id: number]
}>()

const customerName = computed(() => {
  const customer = props.order.customer
  if (typeof customer === 'string') return 'Client'
  return `${customer.firstName} ${customer.lastName}`
})

const nextStatuses = computed(() => ORDER_STATUS_TRANSITIONS[props.order.status])

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
  <div class="card p-4 hover:shadow-md transition-shadow">
    <div class="flex items-start justify-between gap-4 mb-3">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <span class="text-sm font-bold text-gray-900">{{ order.orderNumber }}</span>
          <StatusBadge :status="order.status" />
        </div>
        <p class="text-sm text-gray-600">{{ customerName }}</p>
        <p class="text-xs text-gray-500">{{ formatRelativeTime(order.createdAt) }}</p>
      </div>
      <p class="text-lg font-bold text-gray-900">{{ formatPrice(order.totalAmount) }}</p>
    </div>

    <!-- Items preview -->
    <div class="text-sm text-gray-600 mb-3">
      <p v-if="order.items.length === 1">1 article</p>
      <p v-else>{{ order.items.length }} articles</p>
      <p v-if="order.tableNumber" class="text-xs text-gray-500">
        Table {{ order.tableNumber }}
      </p>
    </div>

    <!-- Actions -->
    <div class="flex items-center gap-2 pt-3 border-t border-gray-100">
      <button
        @click="emit('view-details', order.id)"
        class="btn btn-secondary text-xs py-1.5 px-3"
      >
        Détails
      </button>
      <div class="flex-1" />
      <button
        v-for="status in nextStatuses"
        :key="status"
        @click="emit('update-status', order.id, status)"
        :class="[getActionClass(status), 'text-xs py-1.5 px-3']"
      >
        {{ getActionLabel(status) }}
      </button>
    </div>
  </div>
</template>
