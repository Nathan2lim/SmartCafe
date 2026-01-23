<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { dashboardService } from '@/services/dashboard.service'
import { formatPrice } from '@/utils/formatters'
import type { DashboardStats, LowStockItem, Order } from '@/types'
import StatCard from '@/components/dashboard/StatCard.vue'
import RecentOrders from '@/components/dashboard/RecentOrders.vue'
import LowStockAlerts from '@/components/dashboard/LowStockAlerts.vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const stats = ref<DashboardStats | null>(null)
const recentOrders = ref<Order[]>([])
const lowStockItems = ref<LowStockItem[]>([])
const loading = ref(true)
const error = ref<string | null>(null)

onMounted(async () => {
  try {
    const [statsData, ordersData, stockData] = await Promise.all([
      dashboardService.getStats(),
      dashboardService.getRecentOrders(5),
      dashboardService.getLowStockItems()
    ])
    stats.value = statsData
    recentOrders.value = ordersData
    lowStockItems.value = stockData
  } catch (err: unknown) {
    const axiosError = err as { response?: { data?: { message?: string } } }
    error.value = axiosError.response?.data?.message || 'Erreur lors du chargement du tableau de bord'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <div v-if="loading" class="flex items-center justify-center py-12">
      <LoadingSpinner size="lg" />
    </div>

    <div v-else-if="error" class="card p-6 text-center">
      <p class="text-red-600">{{ error }}</p>
      <button @click="$router.go(0)" class="btn btn-primary mt-4">
        Réessayer
      </button>
    </div>

    <div v-else class="space-y-6">
      <!-- Stats cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          title="Commandes du jour"
          :value="stats?.ordersToday ?? 0"
          icon="orders"
        />
        <StatCard
          title="Chiffre d'affaires"
          :value="formatPrice(stats?.revenueToday ?? 0)"
          icon="revenue"
        />
        <StatCard
          title="Alertes stock"
          :value="stats?.lowStockAlerts ?? 0"
          icon="stock"
        />
        <StatCard
          title="Commandes actives"
          :value="stats?.activeOrders ?? 0"
          icon="active"
        />
      </div>

      <!-- Content -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <RecentOrders :orders="recentOrders" />
        <LowStockAlerts :items="lowStockItems" />
      </div>
    </div>
  </div>
</template>
