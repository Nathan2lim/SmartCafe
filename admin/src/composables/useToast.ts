import { ref } from 'vue'

export interface Toast {
  id: number
  type: 'success' | 'error' | 'warning' | 'info'
  message: string
  duration?: number
}

const toasts = ref<Toast[]>([])
let nextId = 0

export function useToast() {
  function add(toast: Omit<Toast, 'id'>): number {
    const id = nextId++
    const duration = toast.duration ?? 5000

    toasts.value.push({ ...toast, id })

    if (duration > 0) {
      setTimeout(() => remove(id), duration)
    }

    return id
  }

  function remove(id: number): void {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index !== -1) {
      toasts.value.splice(index, 1)
    }
  }

  function success(message: string, duration?: number): number {
    return add({ type: 'success', message, duration })
  }

  function error(message: string, duration?: number): number {
    return add({ type: 'error', message, duration })
  }

  function warning(message: string, duration?: number): number {
    return add({ type: 'warning', message, duration })
  }

  function info(message: string, duration?: number): number {
    return add({ type: 'info', message, duration })
  }

  function clear(): void {
    toasts.value = []
  }

  return {
    toasts,
    add,
    remove,
    success,
    error,
    warning,
    info,
    clear
  }
}
