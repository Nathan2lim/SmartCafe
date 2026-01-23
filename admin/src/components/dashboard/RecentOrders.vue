<script setup lang="ts">
import { formatRelativeTime, formatPrice } from '@/utils/formatters'
import StatusBadge from '@/components/common/StatusBadge.vue'
import type { Order, User } from '@/types'

defineProps<{
  orders: Order[]
}>()

function getCustomerName(customer: User | string): string {
  if (typeof customer === 'string') return 'Client'
  return `${customer.firstName} ${customer.lastName}`
}
</script>

<template>
  <div class="card">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-900">Commandes récentes</h3>
      <router-link to="/orders" class="text-sm font-medium text-primary-600 hover:text-primary-700">
        Voir tout
      </router-link>
    </div>
    <div class="divide-y divide-gray-200">
      <div v-if="orders.length === 0" class="px-6 py-8 text-center text-gray-500">
        Aucune commande récente
      </div>
      <router-link
        v-for="order in orders"
        :key="order.id"
        :to="`/orders/${order.id}`"
        class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50 transition-colors"
      >
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <p class="text-sm font-medium text-gray-900">{{ order.orderNumber }}</p>
            <StatusBadge :status="order.status" />
          </div>
          <p class="text-sm text-gray-500 truncate">{{ getCustomerName(order.customer) }}</p>
        </div>
        <div class="text-right">
          <p class="text-sm font-medium text-gray-900">{{ formatPrice(order.totalAmount) }}</p>
          <p class="text-xs text-gray-500">{{ formatRelativeTime(order.createdAt) }}</p>
        </div>
      </router-link>
    </div>
  </div>
</template>
