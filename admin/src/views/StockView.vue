<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { productsService } from '@/services/products.service'
import { extrasService } from '@/services/extras.service'
import { useToast } from '@/composables/useToast'
import type { LowStockItem } from '@/types'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import EmptyState from '@/components/common/EmptyState.vue'

const toast = useToast()

const loading = ref(true)
const restockQuantities = ref<Record<string, number>>({})
const lowStockItems = ref<LowStockItem[]>([])

async function fetchLowStockItems() {
  const [products, extras] = await Promise.all([
    productsService.getLowStock(),
    extrasService.getLowStock()
  ])

  const items: LowStockItem[] = [
    ...products.map(p => ({
      id: p.id,
      name: p.name,
      type: 'product' as const,
      stockQuantity: p.stockQuantity ?? 0,
      lowStockThreshold: p.lowStockThreshold
    })),
    ...extras.map(e => ({
      id: e.id,
      name: e.name,
      type: 'extra' as const,
      stockQuantity: e.stockQuantity,
      lowStockThreshold: e.lowStockThreshold
    }))
  ]

  lowStockItems.value = items.sort((a, b) => a.stockQuantity - b.stockQuantity)
}

onMounted(async () => {
  await fetchLowStockItems()
  loading.value = false
})

function getItemKey(item: LowStockItem): string {
  return `${item.type}-${item.id}`
}

function getQuantity(item: LowStockItem): number {
  return restockQuantities.value[getItemKey(item)] ?? 10
}

function setQuantity(item: LowStockItem, value: number) {
  restockQuantities.value[getItemKey(item)] = value
}

async function handleRestock(item: LowStockItem) {
  const quantity = getQuantity(item)
  if (quantity <= 0) return

  let success = false

  try {
    if (item.type === 'extra') {
      await extrasService.restock(item.id, quantity)
      success = true
    } else {
      const product = await productsService.getById(item.id)
      if (product) {
        const newStock = (product.stockQuantity ?? 0) + quantity
        await productsService.update(item.id, { stockQuantity: newStock })
        success = true
      }
    }
  } catch {
    success = false
  }

  if (success) {
    toast.success(`Stock de "${item.name}" mis à jour`)
    delete restockQuantities.value[getItemKey(item)]
    // Refresh the list
    await fetchLowStockItems()
  } else {
    toast.error('Erreur lors du réapprovisionnement')
  }
}

function getStockPercentage(item: LowStockItem): number {
  return Math.min((item.stockQuantity / item.lowStockThreshold) * 100, 100)
}

function getProgressClass(item: LowStockItem): string {
  const percentage = getStockPercentage(item)
  if (percentage <= 50) return 'bg-red-500'
  if (percentage <= 100) return 'bg-amber-500'
  return 'bg-green-500'
}
</script>

<template>
  <div>
    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <LoadingSpinner size="lg" />
    </div>

    <!-- All good -->
    <EmptyState
      v-else-if="lowStockItems.length === 0"
      title="Tout va bien !"
      description="Tous les produits et extras ont un niveau de stock suffisant."
      icon="stock"
    />

    <!-- Low stock items -->
    <div v-else class="space-y-4">
      <div class="flex items-center gap-2 text-amber-600 mb-6">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span class="font-medium">{{ lowStockItems.length }} article(s) en stock bas</span>
      </div>

      <div class="card overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Article</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Réapprovisionner</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="item in lowStockItems" :key="getItemKey(item)" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <p class="font-medium text-gray-900">{{ item.name }}</p>
              </td>
              <td class="px-6 py-4">
                <span
                  :class="[
                    'badge',
                    item.type === 'product' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'
                  ]"
                >
                  {{ item.type === 'product' ? 'Produit' : 'Extra' }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="w-32">
                  <div class="flex items-center justify-between mb-1">
                    <span class="text-sm font-medium text-gray-900">{{ item.stockQuantity }}</span>
                    <span class="text-xs text-gray-500">/ {{ item.lowStockThreshold }}</span>
                  </div>
                  <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div
                      :class="['h-full rounded-full transition-all', getProgressClass(item)]"
                      :style="{ width: `${getStockPercentage(item)}%` }"
                    />
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <input
                    :value="getQuantity(item)"
                    @input="setQuantity(item, Number(($event.target as HTMLInputElement).value))"
                    type="number"
                    min="1"
                    class="input w-20 text-center"
                  />
                  <button
                    @click="handleRestock(item)"
                    class="btn btn-success text-xs py-1.5"
                  >
                    Ajouter
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
