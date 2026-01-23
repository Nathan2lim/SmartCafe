<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useProductsStore } from '@/stores/products'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import type { Product, ProductCreate } from '@/types'
import ProductCard from '@/components/products/ProductCard.vue'
import ProductForm from '@/components/products/ProductForm.vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import EmptyState from '@/components/common/EmptyState.vue'

const productsStore = useProductsStore()
const toast = useToast()
const confirmDialog = useConfirm()

const showForm = ref(false)
const editingProduct = ref<Product | null>(null)
const searchQuery = ref('')
const selectedCategory = ref('')

const filteredProducts = computed(() => {
  let products = productsStore.products

  if (selectedCategory.value) {
    products = products.filter(p => p.category === selectedCategory.value)
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    products = products.filter(p =>
      p.name.toLowerCase().includes(query) ||
      p.description?.toLowerCase().includes(query)
    )
  }

  return products
})

onMounted(() => {
  productsStore.fetchProducts()
})

function openCreateForm() {
  editingProduct.value = null
  showForm.value = true
}

function openEditForm(product: Product) {
  editingProduct.value = product
  showForm.value = true
}

function closeForm() {
  showForm.value = false
  editingProduct.value = null
}

async function handleSave(data: ProductCreate) {
  let success: boolean

  if (editingProduct.value) {
    const result = await productsStore.updateProduct(editingProduct.value.id, data)
    success = !!result
  } else {
    const result = await productsStore.createProduct(data)
    success = !!result
  }

  if (success) {
    toast.success(editingProduct.value ? 'Produit mis à jour' : 'Produit créé')
    closeForm()
  } else {
    toast.error(productsStore.error || 'Une erreur est survenue')
  }
}

async function handleDelete(id: number) {
  const confirmed = await confirmDialog.confirm({
    title: 'Supprimer le produit',
    message: 'Êtes-vous sûr de vouloir supprimer ce produit ? Cette action est irréversible.',
    confirmText: 'Supprimer',
    type: 'danger'
  })

  if (!confirmed) return

  const success = await productsStore.deleteProduct(id)
  if (success) {
    toast.success('Produit supprimé')
  } else {
    toast.error(productsStore.error || 'Erreur lors de la suppression')
  }
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
      <div class="flex flex-wrap items-center gap-4">
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

        <!-- Category filter -->
        <select v-model="selectedCategory" class="input w-48">
          <option value="">Toutes les catégories</option>
          <option v-for="cat in productsStore.categories" :key="cat" :value="cat">
            {{ cat }}
          </option>
        </select>
      </div>

      <button @click="openCreateForm" class="btn btn-primary">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Nouveau produit
      </button>
    </div>

    <!-- Loading -->
    <div v-if="productsStore.loading && productsStore.products.length === 0" class="flex items-center justify-center py-12">
      <LoadingSpinner size="lg" />
    </div>

    <!-- Empty state -->
    <EmptyState
      v-else-if="filteredProducts.length === 0"
      :title="searchQuery || selectedCategory ? 'Aucun résultat' : 'Aucun produit'"
      :description="searchQuery || selectedCategory ? 'Aucun produit ne correspond à vos critères.' : 'Commencez par créer votre premier produit.'"
      icon="products"
    >
      <template v-if="!searchQuery && !selectedCategory" #action>
        <button @click="openCreateForm" class="btn btn-primary">
          Créer un produit
        </button>
      </template>
    </EmptyState>

    <!-- Products grid -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <ProductCard
        v-for="product in filteredProducts"
        :key="product.id"
        :product="product"
        @edit="openEditForm"
        @delete="handleDelete"
      />
    </div>

    <!-- Form modal -->
    <ProductForm
      v-if="showForm"
      :product="editingProduct"
      :categories="productsStore.categories"
      @close="closeForm"
      @save="handleSave"
    />
  </div>
</template>
