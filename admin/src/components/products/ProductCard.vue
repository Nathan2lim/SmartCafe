<script setup lang="ts">
import { computed } from 'vue'
import type { Product } from '@/types'
import { formatPrice } from '@/utils/formatters'

const props = defineProps<{
  product: Product
}>()

const emit = defineEmits<{
  edit: [product: Product]
  delete: [id: number]
}>()

const isLowStock = computed(() => {
  if (props.product.lowStock !== undefined) return props.product.lowStock
  return (props.product.stockQuantity ?? 0) <= props.product.lowStockThreshold
})
</script>

<template>
  <div class="card overflow-hidden hover:shadow-md transition-shadow">
    <!-- Image -->
    <div class="aspect-video bg-gray-100 relative">
      <img
        v-if="product.imageUrl"
        :src="product.imageUrl"
        :alt="product.name"
        class="w-full h-full object-cover"
      />
      <div v-else class="w-full h-full flex items-center justify-center">
        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
      </div>
      <!-- Stock badge -->
      <div
        v-if="product.stockQuantity !== null && product.stockQuantity !== undefined"
        :class="[
          'absolute top-2 right-2 px-2 py-1 rounded-full text-xs font-medium',
          isLowStock ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'
        ]"
      >
        Stock: {{ product.stockQuantity }}
      </div>
      <!-- Availability badge -->
      <div
        v-if="!product.available"
        class="absolute top-2 left-2 px-2 py-1 rounded-full text-xs font-medium bg-gray-900/75 text-white"
      >
        Indisponible
      </div>
    </div>

    <!-- Content -->
    <div class="p-4">
      <div class="flex items-start justify-between gap-2 mb-2">
        <div>
          <h3 class="font-semibold text-gray-900">{{ product.name }}</h3>
          <p class="text-xs text-gray-500">{{ product.category }}</p>
        </div>
        <p class="text-lg font-bold text-primary-600">{{ formatPrice(product.price) }}</p>
      </div>

      <p v-if="product.description" class="text-sm text-gray-600 line-clamp-2 mb-4">
        {{ product.description }}
      </p>

      <!-- Actions -->
      <div class="flex items-center gap-2 pt-3 border-t border-gray-100">
        <button
          @click="emit('edit', product)"
          class="btn btn-secondary text-xs py-1.5 px-3 flex-1"
        >
          Modifier
        </button>
        <button
          @click="emit('delete', product.id)"
          class="btn btn-danger text-xs py-1.5 px-3"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>
