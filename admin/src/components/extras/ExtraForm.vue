<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import type { Extra, ExtraCreate } from '@/types'
import Modal from '@/components/common/Modal.vue'

const props = defineProps<{
  extra?: Extra | null
}>()

const emit = defineEmits<{
  close: []
  save: [data: ExtraCreate]
}>()

const isEditing = computed(() => !!props.extra)
const title = computed(() => isEditing.value ? 'Modifier l\'extra' : 'Nouvel extra')

const form = ref<ExtraCreate>({
  name: '',
  description: '',
  price: 0,
  stockQuantity: 0,
  lowStockThreshold: 10,
  available: true
})

watch(() => props.extra, (extra) => {
  if (extra) {
    form.value = {
      name: extra.name,
      description: extra.description || '',
      price: extra.price,
      stockQuantity: extra.stockQuantity,
      lowStockThreshold: extra.lowStockThreshold,
      available: extra.available
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
    stockQuantity: 0,
    lowStockThreshold: 10,
    available: true
  }
}

function handleSubmit() {
  emit('save', { ...form.value })
}
</script>

<template>
  <Modal :title="title" size="md" @close="emit('close')">
    <form @submit.prevent="handleSubmit" class="space-y-4">
      <!-- Name -->
      <div>
        <label for="name" class="label">Nom *</label>
        <input
          id="name"
          v-model="form.name"
          type="text"
          required
          class="input"
          placeholder="Crème chantilly"
        />
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
      <div>
        <label for="description" class="label">Description</label>
        <textarea
          id="description"
          v-model="form.description"
          rows="2"
          class="input"
          placeholder="Description de l'extra..."
        />
      </div>

      <!-- Stock -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label for="stock" class="label">Stock</label>
          <input
            id="stock"
            v-model.number="form.stockQuantity"
            type="number"
            min="0"
            class="input"
          />
        </div>
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
      </div>

      <!-- Available -->
      <label class="flex items-center gap-2 cursor-pointer">
        <input
          v-model="form.available"
          type="checkbox"
          class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
        />
        <span class="text-sm text-gray-700">Disponible</span>
      </label>
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
