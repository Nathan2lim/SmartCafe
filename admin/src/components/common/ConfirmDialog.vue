<script setup lang="ts">
import { useConfirm } from '@/composables/useConfirm'

const { isOpen, options, handleConfirm, handleCancel } = useConfirm()

function getButtonClass(type: string | undefined): string {
  if (type === 'danger') return 'btn btn-danger'
  if (type === 'info') return 'btn btn-primary'
  return 'btn btn-warning'
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900/50" @click="handleCancel" />

        <!-- Dialog -->
        <div class="flex min-h-full items-center justify-center p-4">
          <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
            <!-- Icon -->
            <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 rounded-full"
              :class="{
                'bg-red-100': options.type === 'danger',
                'bg-amber-100': options.type === 'warning',
                'bg-blue-100': options.type === 'info'
              }"
            >
              <svg v-if="options.type === 'danger'" class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              <svg v-else class="w-6 h-6" :class="options.type === 'info' ? 'text-blue-600' : 'text-amber-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>

            <!-- Content -->
            <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">
              {{ options.title }}
            </h3>
            <p class="text-sm text-gray-600 text-center mb-6">
              {{ options.message }}
            </p>

            <!-- Actions -->
            <div class="flex gap-3">
              <button
                @click="handleCancel"
                class="btn btn-secondary flex-1"
              >
                {{ options.cancelText }}
              </button>
              <button
                @click="handleConfirm"
                :class="[getButtonClass(options.type), 'flex-1']"
              >
                {{ options.confirmText }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
