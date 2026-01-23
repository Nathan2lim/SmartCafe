<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import type { Product, ProductCreate } from '@/types'
import Modal from '@/components/common/Modal.vue'

const props = defineProps<{
  product?: Product | null
  categories: string[]
}>()

const emit = defineEmits<{
  close: []
  save: [data: ProductCreate]
}>()

const isEditing = computed(() => !!props.product)
const title = computed(() => isEditing.value ? 'Modifier le produit' : 'Nouveau produit')

const form = ref<ProductCreate>({
  name: '',
  description: '',
  price: 0,
  category: '',
  available: true,
  alaCarte: true,
  imageUrl: '',
  stockQuantity: undefined,
  lowStockThreshold: 10
})

const newCategory = ref('')
const showNewCategory = ref(false)

watch(() => props.product, (product) => {
  if (product) {
    form.value = {
      name: product.name,
      description: product.description || '',
      price: product.price,
      category: product.category,
      available: product.available,
      alaCarte: product.alaCarte,
      imageUrl: product.imageUrl || '',
      stockQuantity: product.stockQuantity ?? undefined,
      lowStockThreshold: product.lowStockThreshold
    }
  } else {
    resetForm()
  }
}, { immediate: true })

function resetForm() {
  form.value = {
    name: '',
    description: '',
    price: 0,
    category: '',
    available: true,
    alaCarte: true,
    imageUrl: '',
    stockQuantity: undefined,
    lowStockThreshold: 10
  }
  newCategory.value = ''
  showNewCategory.value = false
}

function handleCategoryChange(event: Event) {
  const value = (event.target as HTMLSelectElement).value
  if (value === '__new__') {
    showNewCategory.value = true
    form.value.category = ''
  } else {
    showNewCategory.value = false
    form.value.category = value
  }
}

function handleSubmit() {
  if (showNewCategory.value && newCategory.value) {
    form.value.category = newCategory.value
  }
  emit('save', { ...form.value })
}
</script>

<template>
  <Modal :title="title" size="lg" @close="emit('close')">
    <form @submit.prevent="handleSubmit" class="space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Name -->
        <div class="sm:col-span-2">
          <label for="name" class="label">Nom *</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            required
            class="input"
            placeholder="Café Latte"
          />
        </div>

        <!-- Category -->
        <div>
          <label for="category" class="label">Catégorie *</label>
          <select
            v-if="!showNewCategory"
            id="category"
            :value="form.category"
            @change="handleCategoryChange"
            required
            class="input"
          >
            <option value="">Sélectionner...</option>
            <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
            <option value="__new__">+ Nouvelle catégorie</option>
          </select>
          <div v-else class="flex gap-2">
            <input
              v-model="newCategory"
              type="text"
              required
              class="input flex-1"
              placeholder="Nom de la catégorie"
            />
            <button
              type="button"
              @click="showNewCategory = false; form.category = ''"
              class="btn btn-secondary"
            >
              Annuler
            </button>
          </div>
        </div>

        <!-- Price -->
        <div>
          <label for="price" class="label">Prix (€) *</label>
          <input
            id="price"
            v-model.number="form.price"
            type="number"
            step="0.01"
            min="0"
            required
            class="input"
          />
        </div>

        <!-- Description -->
        <div class="sm:col-span-2">
          <label for="description" class="label">Description</label>
          <textarea
            id="description"
            v-model="form.description"
            rows="3"
            class="input"
            placeholder="Description du produit..."
          />
        </div>

        <!-- Image URL -->
        <div class="sm:col-span-2">
          <label for="imageUrl" class="label">URL de l'image</label>
          <input
            id="imageUrl"
            v-model="form.imageUrl"
            type="url"
            class="input"
            placeholder="https://..."
          />
        </div>

        <!-- Stock -->
        <div>
          <label for="stock" class="label">Stock</label>
          <input
            id="stock"
            v-model.number="form.stockQuantity"
            type="number"
            min="0"
            class="input"
            placeholder="Quantité"
          />
        </div>

        <!-- Low stock threshold -->
        <div>
          <label for="threshold" class="label">Seuil d'alerte</label>
          <input
            id="threshold"
            v-model.number="form.lowStockThreshold"
            type="number"
            min="0"
            class="input"
          />
        </div>

        <!-- Toggles -->
        <div class="sm:col-span-2 flex flex-wrap gap-6">
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              v-model="form.available"
              type="checkbox"
              class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
            />
            <span class="text-sm text-gray-700">Disponible</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              v-model="form.alaCarte"
              type="checkbox"
              class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
            />
            <span class="text-sm text-gray-700">À la carte</span>
          </label>
        </div>
      </div>
    </form>

    <template #footer>
      <div class="flex items-center justify-end gap-3">
        <button @click="emit('close')" class="btn btn-secondary">
          Annuler
        </button>
        <button @click="handleSubmit" class="btn btn-primary">
          {{ isEditing ? 'Enregistrer' : 'Créer' }}
        </button>
      </div>
    </template>
  </Modal>
</template>
