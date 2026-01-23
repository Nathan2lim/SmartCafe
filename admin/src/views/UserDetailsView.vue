<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUsersStore } from '@/stores/users'
import { loyaltyService } from '@/services/loyalty.service'
import { formatDate, formatDateTime } from '@/utils/formatters'
import type { LoyaltyTransaction, LoyaltyAccount } from '@/types'
import { TRANSACTION_TYPE_LABELS } from '@/types'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const route = useRoute()
const router = useRouter()
const usersStore = useUsersStore()

const userId = computed(() => Number(route.params.id))
const loyaltyAccount = ref<LoyaltyAccount | null>(null)
const transactions = ref<LoyaltyTransaction[]>([])
const loadingTransactions = ref(false)

const user = computed(() => usersStore.currentUser)

const isAdmin = computed(() => user.value?.roles.includes('ROLE_ADMIN') ?? false)

onMounted(async () => {
  await usersStore.fetchUser(userId.value)

  if (user.value?.loyaltyAccount) {
    const account = user.value.loyaltyAccount
    const accountId = typeof account === 'string'
      ? parseInt(account.split('/').pop() || '0')
      : account.id

    if (accountId) {
      loadingTransactions.value = true
      try {
        loyaltyAccount.value = await loyaltyService.getAccountById(accountId)
        transactions.value = await loyaltyService.getTransactionsByAccount(accountId)
      } catch {
        // Loyalty data not available
      } finally {
        loadingTransactions.value = false
      }
    }
  }
})

function getTierLabel(tier: string): string {
  const labels: Record<string, string> = {
    bronze: 'Bronze',
    silver: 'Argent',
    gold: 'Or',
    platinum: 'Platine',
    diamond: 'Diamant'
  }
  return labels[tier] || tier
}

function getTierColor(tier: string): string {
  const colors: Record<string, string> = {
    bronze: 'bg-amber-700',
    silver: 'bg-gray-400',
    gold: 'bg-yellow-500',
    platinum: 'bg-gray-600',
    diamond: 'bg-blue-400'
  }
  return colors[tier] || 'bg-gray-400'
}

function getTransactionColor(type: string): string {
  if (type === 'earn' || type === 'bonus') return 'text-green-600'
  if (type === 'redeem' || type === 'expired') return 'text-red-600'
  return 'text-gray-600'
}
</script>

<template>
  <div>
    <!-- Back button -->
    <button @click="router.back()" class="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
      </svg>
      Retour aux utilisateurs
    </button>

    <!-- Loading -->
    <div v-if="usersStore.loading" class="flex items-center justify-center py-12">
      <LoadingSpinner size="lg" />
    </div>

    <!-- Error -->
    <div v-else-if="usersStore.error || !user" class="card p-6 text-center">
      <p class="text-red-600 mb-4">{{ usersStore.error || 'Utilisateur non trouvé' }}</p>
      <button @click="router.push('/users')" class="btn btn-primary">
        Retour aux utilisateurs
      </button>
    </div>

    <!-- User details -->
    <div v-else class="space-y-6">
      <!-- Header card -->
      <div class="card p-6">
        <div class="flex items-start gap-6">
          <div class="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
            <span class="text-2xl font-semibold text-primary-700">
              {{ user.firstName.charAt(0) }}{{ user.lastName.charAt(0) }}
            </span>
          </div>
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-2">
              <h2 class="text-2xl font-bold text-gray-900">
                {{ user.firstName }} {{ user.lastName }}
              </h2>
              <span
                :class="[
                  'badge',
                  isAdmin ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-700'
                ]"
              >
                {{ isAdmin ? 'Administrateur' : 'Utilisateur' }}
              </span>
            </div>
            <p class="text-gray-600">{{ user.email }}</p>
            <p v-if="user.phone" class="text-gray-500">{{ user.phone }}</p>
            <p class="text-sm text-gray-500 mt-2">
              Inscrit le {{ formatDate(user.createdAt) }}
            </p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Loyalty account -->
        <div class="card p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Compte fidélité</h3>

          <div v-if="loyaltyAccount">
            <!-- Tier badge -->
            <div class="flex items-center gap-3 mb-4">
              <div :class="['w-12 h-12 rounded-full flex items-center justify-center', getTierColor(loyaltyAccount.tier)]">
                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                </svg>
              </div>
              <div>
                <p class="font-semibold text-gray-900">{{ getTierLabel(loyaltyAccount.tier) }}</p>
                <p class="text-sm text-gray-500">Multiplicateur: x{{ loyaltyAccount.currentMultiplier }}</p>
              </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-3 gap-4 py-4 border-t border-b border-gray-200">
              <div class="text-center">
                <p class="text-2xl font-bold text-primary-600">{{ loyaltyAccount.points }}</p>
                <p class="text-xs text-gray-500">Points actuels</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold text-gray-900">{{ loyaltyAccount.totalPointsEarned }}</p>
                <p class="text-xs text-gray-500">Points gagnés</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold text-gray-900">{{ loyaltyAccount.totalPointsSpent }}</p>
                <p class="text-xs text-gray-500">Points dépensés</p>
              </div>
            </div>

            <!-- Upgrade progress -->
            <div v-if="loyaltyAccount.pointsToUpgrade && loyaltyAccount.nextTier" class="mt-4">
              <div class="flex items-center justify-between mb-1">
                <span class="text-sm text-gray-600">Prochain niveau: {{ getTierLabel(loyaltyAccount.nextTier) }}</span>
                <span class="text-sm font-medium text-gray-900">
                  {{ loyaltyAccount.points }} / {{ loyaltyAccount.upgradeCost }}
                </span>
              </div>
              <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                <div
                  class="h-full bg-primary-500 rounded-full"
                  :style="{ width: `${(loyaltyAccount.points / (loyaltyAccount.upgradeCost || 1)) * 100}%` }"
                />
              </div>
            </div>
          </div>

          <div v-else class="text-center py-6 text-gray-500">
            Pas de compte fidélité
          </div>
        </div>

        <!-- Transactions -->
        <div class="card p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Historique des points</h3>

          <div v-if="loadingTransactions" class="flex items-center justify-center py-6">
            <LoadingSpinner size="sm" />
          </div>

          <div v-else-if="transactions.length === 0" class="text-center py-6 text-gray-500">
            Aucune transaction
          </div>

          <div v-else class="space-y-3 max-h-80 overflow-y-auto">
            <div
              v-for="tx in transactions"
              :key="tx.id"
              class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0"
            >
              <div>
                <p class="text-sm font-medium text-gray-900">{{ tx.description }}</p>
                <p class="text-xs text-gray-500">{{ formatDateTime(tx.createdAt) }}</p>
              </div>
              <div class="text-right">
                <p :class="['font-semibold', getTransactionColor(tx.type)]">
                  {{ tx.type === 'earn' || tx.type === 'bonus' ? '+' : '-' }}{{ Math.abs(tx.points) }}
                </p>
                <span class="text-xs text-gray-500">
                  {{ TRANSACTION_TYPE_LABELS[tx.type] }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
