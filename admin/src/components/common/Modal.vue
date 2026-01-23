<script setup lang="ts">
defineProps<{
  title: string
  size?: 'sm' | 'md' | 'lg' | 'xl'
}>()

const emit = defineEmits<{
  close: []
}>()

function getSizeClass(size: string | undefined): string {
  if (size === 'sm') return 'max-w-sm'
  if (size === 'lg') return 'max-w-lg'
  if (size === 'xl') return 'max-w-xl'
  return 'max-w-md'
}
</script>

<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 overflow-y-auto">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-gray-900/50" @click="emit('close')" />

      <!-- Dialog -->
      <div class="flex min-h-full items-center justify-center p-4">
        <div :class="['relative bg-white rounded-xl shadow-xl w-full', getSizeClass(size)]">
          <!-- Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">{{ title }}</h3>
            <button
              @click="emit('close')"
              class="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Content -->
          <div class="p-6">
            <slot />
          </div>

          <!-- Footer -->
          <div v-if="$slots.footer" class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-xl">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
