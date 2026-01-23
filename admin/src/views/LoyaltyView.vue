<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { loyaltyService } from '@/services/loyalty.service'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import type { LoyaltyReward, LoyaltyRewardCreate } from '@/types'
import { REWARD_TYPE_LABELS } from '@/types'
import RewardForm from '@/components/loyalty/RewardForm.vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import EmptyState from '@/components/common/EmptyState.vue'

const toast = useToast()
const confirmDialog = useConfirm()

const rewards = ref<LoyaltyReward[]>([])
const loading = ref(true)
const showForm = ref(false)
const editingReward = ref<LoyaltyReward | null>(null)

onMounted(async () => {
  try {
    rewards.value = await loyaltyService.getRewards()
  } catch {
    toast.error('Erreur lors du chargement des récompenses')
  } finally {
    loading.value = false
  }
})

function openCreateForm() {
  editingReward.value = null
  showForm.value = true
}

function openEditForm(reward: LoyaltyReward) {
  editingReward.value = reward
  showForm.value = true
}

function closeForm() {
  showForm.value = false
  editingReward.value = null
}

async function handleSave(data: LoyaltyRewardCreate) {
  try {
    if (editingReward.value) {
      const updated = await loyaltyService.updateReward(editingReward.value.id, data)
      const index = rewards.value.findIndex(r => r.id === editingReward.value!.id)
      if (index !== -1) {
        rewards.value[index] = updated
      }
      toast.success('Récompense mise à jour')
    } else {
      const created = await loyaltyService.createReward(data)
      rewards.value.push(created)
      toast.success('Récompense créée')
    }
    closeForm()
  } catch {
    toast.error('Une erreur est survenue')
  }
}

async function handleDelete(id: number) {
  const confirmed = await confirmDialog.confirm({
    title: 'Supprimer la récompense',
    message: 'Êtes-vous sûr de vouloir supprimer cette récompense ?',
    confirmText: 'Supprimer',
    type: 'danger'
  })

  if (!confirmed) return

  try {
    await loyaltyService.deleteReward(id)
    rewards.value = rewards.value.filter(r => r.id !== id)
    toast.success('Récompense supprimée')
  } catch {
    toast.error('Erreur lors de la suppression')
  }
}

async function toggleActive(reward: LoyaltyReward) {
  try {
    const updated = await loyaltyService.updateReward(reward.id, { active: !reward.active })
    const index = rewards.value.findIndex(r => r.id === reward.id)
    if (index !== -1) {
      rewards.value[index] = updated
    }
    toast.success(updated.active ? 'Récompense activée' : 'Récompense désactivée')
  } catch {
    toast.error('Erreur lors de la mise à jour')
  }
}

function getTierLabel(tier?: string): string {
  const labels: Record<string, string> = {
    bronze: 'Bronze',
    silver: 'Argent',
    gold: 'Or',
    platinum: 'Platine',
    diamond: 'Diamant'
  }
  return tier ? labels[tier] || tier : '-'
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold text-gray-900">Récompenses</h2>
        <p class="text-sm text-gray-500">Gérez les récompenses du programme fidélité</p>
      </div>
      <button @click="openCreateForm" class="btn btn-primary">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Nouvelle récompense
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <LoadingSpinner size="lg" />
    </div>

    <!-- Empty state -->
    <EmptyState
      v-else-if="rewards.length === 0"
      title="Aucune récompense"
      description="Créez des récompenses pour votre programme fidélité."
      icon="loyalty"
    >
      <template #action>
        <button @click="openCreateForm" class="btn btn-primary">
          Créer une récompense
        </button>
      </template>
    </EmptyState>

    <!-- Rewards grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="reward in rewards"
        :key="reward.id"
        :class="[
          'card p-4',
          !reward.active && 'opacity-60'
        ]"
      >
        <div class="flex items-start justify-between gap-2 mb-3">
          <div>
            <h3 class="font-semibold text-gray-900">{{ reward.name }}</h3>
            <span class="text-xs text-gray-500">{{ REWARD_TYPE_LABELS[reward.type] }}</span>
          </div>
          <div class="flex items-center gap-1">
            <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
            </svg>
            <span class="font-bold text-gray-900">{{ reward.pointsCost }}</span>
          </div>
        </div>

        <p v-if="reward.description" class="text-sm text-gray-600 mb-3 line-clamp-2">
          {{ reward.description }}
        </p>

        <div class="flex flex-wrap gap-2 mb-4">
          <span
            v-if="reward.requiredTier"
            class="badge bg-purple-100 text-purple-700"
          >
            {{ getTierLabel(reward.requiredTier) }} requis
          </span>
          <span
            v-if="reward.stockQuantity !== null && reward.stockQuantity !== undefined"
            class="badge bg-gray-100 text-gray-700"
          >
            Stock: {{ reward.stockQuantity }}
          </span>
          <span
            :class="[
              'badge',
              reward.active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'
            ]"
          >
            {{ reward.active ? 'Active' : 'Inactive' }}
          </span>
        </div>

        <div class="flex items-center gap-2 pt-3 border-t border-gray-100">
          <button
            @click="toggleActive(reward)"
            :class="[
              'btn text-xs py-1.5 px-3',
              reward.active ? 'btn-secondary' : 'btn-success'
            ]"
          >
            {{ reward.active ? 'Désactiver' : 'Activer' }}
          </button>
          <button
            @click="openEditForm(reward)"
            class="btn btn-secondary text-xs py-1.5 px-3"
          >
            Modifier
          </button>
          <button
            @click="handleDelete(reward.id)"
            class="btn btn-danger text-xs py-1.5 px-3"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Form modal -->
    <RewardForm
      v-if="showForm"
      :reward="editingReward"
      @close="closeForm"
      @save="handleSave"
    />
  </div>
</template>
