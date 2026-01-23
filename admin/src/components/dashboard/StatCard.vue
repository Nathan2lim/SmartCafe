<script setup lang="ts">
defineProps<{
  title: string
  value: string | number
  icon: 'orders' | 'revenue' | 'stock' | 'active'
  trend?: {
    value: number
    isPositive: boolean
  }
}>()

function getIconBgClass(icon: string): string {
  if (icon === 'revenue') return 'bg-green-100 text-green-600'
  if (icon === 'stock') return 'bg-amber-100 text-amber-600'
  if (icon === 'active') return 'bg-purple-100 text-purple-600'
  return 'bg-blue-100 text-blue-600'
}
</script>

<template>
  <div class="card p-6">
    <div class="flex items-start justify-between">
      <div>
        <p class="text-sm font-medium text-gray-500">{{ title }}</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ value }}</p>
        <div v-if="trend" class="mt-2 flex items-center gap-1 text-sm">
          <svg
            :class="trend.isPositive ? 'text-green-500' : 'text-red-500'"
            class="w-4 h-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              v-if="trend.isPositive"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M5 10l7-7m0 0l7 7m-7-7v18"
            />
            <path
              v-else
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M19 14l-7 7m0 0l-7-7m7 7V3"
            />
          </svg>
          <span :class="trend.isPositive ? 'text-green-600' : 'text-red-600'">
            {{ trend.value }}%
          </span>
          <span class="text-gray-500">vs hier</span>
        </div>
      </div>
      <div :class="['p-3 rounded-xl', getIconBgClass(icon)]">
        <svg v-if="icon === 'orders'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
        <svg v-else-if="icon === 'revenue'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <svg v-else-if="icon === 'stock'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
      </div>
    </div>
  </div>
</template>
