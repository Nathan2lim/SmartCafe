<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'

defineEmits<{
  'toggle-sidebar': []
}>()

const route = useRoute()

const pageTitle = computed(() => {
  const titles: Record<string, string> = {
    dashboard: 'Tableau de bord',
    orders: 'Commandes',
    'order-details': 'Détails commande',
    products: 'Produits',
    extras: 'Extras',
    stock: 'Gestion du stock',
    loyalty: 'Programme fidélité',
    users: 'Utilisateurs',
    'user-details': 'Détails utilisateur'
  }
  return titles[route.name as string] || 'SmartCafe Admin'
})
</script>

<template>
  <header class="sticky top-0 z-30 bg-white border-b border-gray-200">
    <div class="h-16 px-4 flex items-center gap-4">
      <!-- Mobile menu button -->
      <button
        @click="$emit('toggle-sidebar')"
        class="lg:hidden p-2 -ml-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>

      <!-- Page title -->
      <h1 class="text-xl font-semibold text-gray-900">{{ pageTitle }}</h1>

      <div class="flex-1" />

      <!-- Current time -->
      <div class="hidden sm:flex items-center gap-2 text-sm text-gray-500">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{{ new Date().toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' }) }}</span>
      </div>
    </div>
  </header>
</template>
