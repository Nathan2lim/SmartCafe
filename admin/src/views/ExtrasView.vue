<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useExtrasStore } from '@/stores/extras'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { formatPrice } from '@/utils/formatters'
import type { Extra, ExtraCreate } from '@/types'
import ExtraForm from '@/components/extras/ExtraForm.vue'
import RestockModal from '@/components/extras/RestockModal.vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import EmptyState from '@/components/common/EmptyState.vue'

const extrasStore = useExtrasStore()
const toast = useToast()
const confirmDialog = useConfirm()

const showForm = ref(false)
const editingExtra = ref<Extra | null>(null)
const restockingExtra = ref<Extra | null>(null)
const searchQuery = ref('')

const filteredExtras = computed(() => {
  if (!searchQuery.value) return extrasStore.extras

  const query = searchQuery.value.toLowerCase()
  return extrasStore.extras.filter(e =>
    e.name.toLowerCase().includes(query) ||
    e.description?.toLowerCase().includes(query)
  )
})

function isLowStock(extra: Extra): boolean {
  if (extra.lowStock !== undefined) return extra.lowStock
  return extra.stockQuantity <= extra.lowStockThreshold
}

onMounted(() => {
  extrasStore.fetchExtras()
})

function openCreateForm() {
  editingExtra.value = null
  showForm.value = true
}

function openEditForm(extra: Extra) {
  editingExtra.value = extra
  showForm.value = true
}

function closeForm() {
  showForm.value = false
  editingExtra.value = null
}

function openRestockModal(extra: Extra) {
  restockingExtra.value = extra
}

function closeRestockModal() {
  restockingExtra.value = null
}

async function handleSave(data: ExtraCreate) {
  let success: boolean

  if (editingExtra.value) {
    const result = await extrasStore.updateExtra(editingExtra.value.id, data)
    success = !!result
  } else {
    const result = await extrasStore.createExtra(data)
    success = !!result
  }

  if (success) {
    toast.success(editingExtra.value ? 'Extra mis à jour' : 'Extra créé')
    closeForm()
  } else {
    toast.error(extrasStore.error || 'Une erreur est survenue')
  }
}

async function handleDelete(id: number) {
  const confirmed = await confirmDialog.confirm({
    title: 'Supprimer l\'extra',
    message: 'Êtes-vous sûr de vouloir supprimer cet extra ? Cette action est irréversible.',
    confirmText: 'Supprimer',
    type: 'danger'
  })

  if (!confirmed) return

  const success = await extrasStore.deleteExtra(id)
  if (success) {
    toast.success('Extra supprimé')
  } else {
    toast.error(extrasStore.error || 'Erreur lors de la suppression')
  }
}

async function handleRestock(quantity: number) {
  if (!restockingExtra.value) return

  const success = await extrasStore.restockExtra(restockingExtra.value.id, quantity)
  if (success) {
    toast.success('Stock mis à jour')
    closeRestockModal()
  } else {
    toast.error(extrasStore.error || 'Erreur lors du réapprovisionnement')
  }
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
      <!-- Search -->
      <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Rechercher..."
          class="input pl-10 w-64"
        />
      </div>

      <button @click="openCreateForm" class="btn btn-primary">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Nouvel extra
      </button>
    </div>

    <!-- Loading -->
    <div v-if="extrasStore.loading && extrasStore.extras.length === 0" class="flex items-center justify-center py-12">
      <LoadingSpinner size="lg" />
    </div>

    <!-- Empty state -->
    <EmptyState
      v-else-if="filteredExtras.length === 0"
      :title="searchQuery ? 'Aucun résultat' : 'Aucun extra'"
      :description="searchQuery ? 'Aucun extra ne correspond à votre recherche.' : 'Commencez par créer votre premier extra.'"
      icon="default"
    >
      <template v-if="!searchQuery" #action>
        <button @click="openCreateForm" class="btn btn-primary">
          Créer un extra
        </button>
      </template>
    </EmptyState>

    <!-- Extras table -->
    <div v-else class="card overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prix</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="extra in filteredExtras" :key="extra.id" class="hover:bg-gray-50">
            <td class="px-6 py-4">
              <div>
                <p class="font-medium text-gray-900">{{ extra.name }}</p>
                <p v-if="extra.description" class="text-sm text-gray-500 truncate max-w-xs">
                  {{ extra.description }}
                </p>
              </div>
            </td>
            <td class="px-6 py-4 text-gray-900">{{ formatPrice(extra.price) }}</td>
            <td class="px-6 py-4">
              <div class="flex items-center gap-2">
                <span
                  :class="[
                    'font-medium',
                    isLowStock(extra) ? 'text-red-600' : 'text-gray-900'
                  ]"
                >
                  {{ extra.stockQuantity }}
                </span>
                <span v-if="isLowStock(extra)" class="text-xs text-red-500">(bas)</span>
              </div>
            </td>
            <td class="px-6 py-4">
              <span
                :class="[
                  'badge',
                  extra.available ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'
                ]"
              >
                {{ extra.available ? 'Disponible' : 'Indisponible' }}
              </span>
            </td>
            <td class="px-6 py-4">
              <div class="flex items-center justify-end gap-2">
                <button
                  @click="openRestockModal(extra)"
                  class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                  title="Réapprovisionner"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                  </svg>
                </button>
                <button
                  @click="openEditForm(extra)"
                  class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                  title="Modifier"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button
                  @click="handleDelete(extra.id)"
                  class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                  title="Supprimer"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Form modal -->
    <ExtraForm
      v-if="showForm"
      :extra="editingExtra"
      @close="closeForm"
      @save="handleSave"
    />

    <!-- Restock modal -->
    <RestockModal
      v-if="restockingExtra"
      :extra="restockingExtra"
      @close="closeRestockModal"
      @restock="handleRestock"
    />
  </div>
</template>
