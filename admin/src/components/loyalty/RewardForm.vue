<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import type { LoyaltyReward, LoyaltyRewardCreate, RewardType, LoyaltyTier } from '@/types'
import { REWARD_TYPE_LABELS } from '@/types'
import Modal from '@/components/common/Modal.vue'

const props = defineProps<{
  reward?: LoyaltyReward | null
}>()

const emit = defineEmits<{
  close: []
  save: [data: LoyaltyRewardCreate]
}>()

const isEditing = computed(() => !!props.reward)
const title = computed(() => isEditing.value ? 'Modifier la récompense' : 'Nouvelle récompense')

const rewardTypes: { value: RewardType; label: string }[] = Object.entries(REWARD_TYPE_LABELS).map(
  ([value, label]) => ({ value: value as RewardType, label })
)

const tiers: { value: LoyaltyTier | ''; label: string }[] = [
  { value: '', label: 'Aucun' },
  { value: 'bronze', label: 'Bronze' },
  { value: 'silver', label: 'Argent' },
  { value: 'gold', label: 'Or' },
  { value: 'platinum', label: 'Platine' },
  { value: 'diamond', label: 'Diamant' }
]

const form = ref<LoyaltyRewardCreate>({
  name: '',
  description: '',
  pointsCost: 100,
  type: 'free_product',
  discountValue: undefined,
  discountPercent: undefined,
  freeProduct: undefined,
  requiredTier: undefined,
  active: true,
  stockQuantity: undefined
})

watch(() => props.reward, (reward) => {
  if (reward) {
    form.value = {
      name: reward.name,
      description: reward.description || '',
      pointsCost: reward.pointsCost,
      type: reward.type,
      discountValue: reward.discountValue,
      discountPercent: reward.discountPercent,
      freeProduct: typeof reward.freeProduct === 'string' ? reward.freeProduct : undefined,
      requiredTier: reward.requiredTier,
      active: reward.active,
      stockQuantity: reward.stockQuantity
    }
  } else {
    resetForm()
  }
}, { immediate: true })

function resetForm() {
  form.value = {
    name: '',
    description: '',
    pointsCost: 100,
    type: 'free_product',
    discountValue: undefined,
    discountPercent: undefined,
    freeProduct: undefined,
    requiredTier: undefined,
    active: true,
    stockQuantity: undefined
  }
}

const showDiscountValue = computed(() => form.value.type === 'discount_amount')
const showDiscountPercent = computed(() => form.value.type === 'discount_percent')

function handleSubmit() {
  const data: LoyaltyRewardCreate = {
    ...form.value,
    requiredTier: form.value.requiredTier || undefined
  }
  emit('save', data)
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
            placeholder="Café offert"
          />
        </div>

        <!-- Type -->
        <div>
          <label for="type" class="label">Type *</label>
          <select id="type" v-model="form.type" required class="input">
            <option v-for="t in rewardTypes" :key="t.value" :value="t.value">
              {{ t.label }}
            </option>
          </select>
        </div>

        <!-- Points cost -->
        <div>
          <label for="points" class="label">Coût en points *</label>
          <input
            id="points"
            v-model.number="form.pointsCost"
            type="number"
            min="1"
            required
            class="input"
          />
        </div>

        <!-- Discount value -->
        <div v-if="showDiscountValue">
          <label for="discountValue" class="label">Valeur de la réduction (€)</label>
          <input
            id="discountValue"
            v-model.number="form.discountValue"
            type="number"
            step="0.01"
            min="0"
            class="input"
          />
        </div>

        <!-- Discount percent -->
        <div v-if="showDiscountPercent">
          <label for="discountPercent" class="label">Pourcentage de réduction</label>
          <input
            id="discountPercent"
            v-model.number="form.discountPercent"
            type="number"
            min="1"
            max="100"
            class="input"
          />
        </div>

        <!-- Required tier -->
        <div>
          <label for="tier" class="label">Niveau requis</label>
          <select id="tier" v-model="form.requiredTier" class="input">
            <option v-for="t in tiers" :key="t.value" :value="t.value">
              {{ t.label }}
            </option>
          </select>
        </div>

        <!-- Stock -->
        <div>
          <label for="stock" class="label">Stock (optionnel)</label>
          <input
            id="stock"
            v-model.number="form.stockQuantity"
            type="number"
            min="0"
            class="input"
            placeholder="Illimité"
          />
        </div>

        <!-- Description -->
        <div class="sm:col-span-2">
          <label for="description" class="label">Description</label>
          <textarea
            id="description"
            v-model="form.description"
            rows="2"
            class="input"
            placeholder="Description de la récompense..."
          />
        </div>

        <!-- Active -->
        <div class="sm:col-span-2">
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              v-model="form.active"
              type="checkbox"
              class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
            />
            <span class="text-sm text-gray-700">Récompense active</span>
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
