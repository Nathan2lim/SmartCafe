<script setup lang="ts">
import { ref } from 'vue'
import type { Extra } from '@/types'
import Modal from '@/components/common/Modal.vue'

defineProps<{
  extra: Extra
}>()

const emit = defineEmits<{
  close: []
  restock: [quantity: number]
}>()

const quantity = ref(10)

function handleSubmit() {
  if (quantity.value > 0) {
    emit('restock', quantity.value)
  }
}
</script>

<template>
  <Modal title="Réapprovisionner" size="sm" @close="emit('close')">
    <div class="space-y-4">
      <div class="text-center">
        <p class="text-lg font-medium text-gray-900 mb-1">{{ extra.name }}</p>
        <p class="text-sm text-gray-500">
          Stock actuel: <span class="font-medium">{{ extra.stockQuantity }}</span>
        </p>
      </div>

      <div>
        <label for="quantity" class="label">Quantité à ajouter</label>
        <input
          id="quantity"
          v-model.number="quantity"
          type="number"
          min="1"
          required
          class="input text-center text-lg"
        />
      </div>

      <div class="text-center text-sm text-gray-600">
        Nouveau stock: <span class="font-semibold text-gray-900">{{ extra.stockQuantity + quantity }}</span>
      </div>
    </div>

    <template #footer>
      <div class="flex items-center justify-end gap-3">
        <button @click="emit('close')" class="btn btn-secondary">
          Annuler
        </button>
        <button @click="handleSubmit" class="btn btn-success">
          Réapprovisionner
        </button>
      </div>
    </template>
  </Modal>
</template>
