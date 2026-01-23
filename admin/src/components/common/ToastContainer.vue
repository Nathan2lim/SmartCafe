<script setup lang="ts">
import { useToast } from '@/composables/useToast'

const { toasts, remove } = useToast()

function getToastClasses(type: string): string {
  if (type === 'success') return 'bg-green-50 border-green-200 text-green-800'
  if (type === 'error') return 'bg-red-50 border-red-200 text-red-800'
  if (type === 'warning') return 'bg-amber-50 border-amber-200 text-amber-800'
  return 'bg-blue-50 border-blue-200 text-blue-800'
}

function getIconColor(type: string): string {
  if (type === 'success') return 'text-green-500'
  if (type === 'error') return 'text-red-500'
  if (type === 'warning') return 'text-amber-500'
  return 'text-blue-500'
}
</script>

<template>
  <div class="fixed top-4 right-4 z-50 space-y-2 max-w-sm w-full">
    <TransitionGroup name="toast">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="['flex items-start gap-3 p-4 rounded-lg border shadow-lg', getToastClasses(toast.type)]"
      >
        <!-- Icon -->
        <div :class="getIconColor(toast.type)">
          <svg v-if="toast.type === 'success'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <svg v-else-if="toast.type === 'error'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
          <svg v-else-if="toast.type === 'warning'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>

        <!-- Message -->
        <p class="flex-1 text-sm font-medium">{{ toast.message }}</p>

        <!-- Close button -->
        <button
          @click="remove(toast.id)"
          class="text-current opacity-50 hover:opacity-100 transition-opacity"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}
</style>
