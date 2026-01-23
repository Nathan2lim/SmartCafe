<script setup lang="ts">
import type { LowStockItem } from '@/types'

defineProps<{
  items: LowStockItem[]
}>()

function getStockClass(item: LowStockItem): string {
  const percentage = (item.stockQuantity / item.lowStockThreshold) * 100
  if (percentage <= 50) return 'text-red-600 bg-red-100'
  if (percentage <= 100) return 'text-amber-600 bg-amber-100'
  return 'text-green-600 bg-green-100'
}

function getProgressWidth(item: LowStockItem): string {
  const percentage = Math.min((item.stockQuantity / item.lowStockThreshold) * 100, 100)
  return `${percentage}%`
}

function getProgressClass(item: LowStockItem): string {
  const percentage = (item.stockQuantity / item.lowStockThreshold) * 100
  if (percentage <= 50) return 'bg-red-500'
  if (percentage <= 100) return 'bg-amber-500'
  return 'bg-green-500'
}
</script>

<template>
  <div class="card">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-900">Alertes stock bas</h3>
      <router-link to="/stock" class="text-sm font-medium text-primary-600 hover:text-primary-700">
        Gérer
      </router-link>
    </div>
    <div class="divide-y divide-gray-200">
      <div v-if="items.length === 0" class="px-6 py-8 text-center">
        <div class="mx-auto w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mb-3">
          <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <p class="text-sm text-gray-500">Tous les stocks sont suffisants</p>
      </div>
      <div
        v-for="item in items"
        :key="`${item.type}-${item.id}`"
        class="px-6 py-4"
      >
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-gray-900">{{ item.name }}</span>
            <span :class="['text-xs px-2 py-0.5 rounded-full', getStockClass(item)]">
              {{ item.type === 'product' ? 'Produit' : 'Extra' }}
            </span>
          </div>
          <span class="text-sm font-medium text-gray-900">
            {{ item.stockQuantity }} / {{ item.lowStockThreshold }}
          </span>
        </div>
        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
          <div
            :class="['h-full rounded-full transition-all', getProgressClass(item)]"
            :style="{ width: getProgressWidth(item) }"
          />
        </div>
      </div>
    </div>
  </div>
</template>
