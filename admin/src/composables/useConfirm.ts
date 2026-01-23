import { ref } from 'vue'

interface ConfirmOptions {
  title: string
  message: string
  confirmText?: string
  cancelText?: string
  type?: 'danger' | 'warning' | 'info'
}

const isOpen = ref(false)
const options = ref<ConfirmOptions>({
  title: '',
  message: ''
})
let resolvePromise: ((value: boolean) => void) | null = null

export function useConfirm() {
  function confirm(opts: ConfirmOptions): Promise<boolean> {
    options.value = {
      ...opts,
      confirmText: opts.confirmText ?? 'Confirmer',
      cancelText: opts.cancelText ?? 'Annuler',
      type: opts.type ?? 'warning'
    }
    isOpen.value = true

    return new Promise(resolve => {
      resolvePromise = resolve
    })
  }

  function handleConfirm(): void {
    isOpen.value = false
    resolvePromise?.(true)
    resolvePromise = null
  }

  function handleCancel(): void {
    isOpen.value = false
    resolvePromise?.(false)
    resolvePromise = null
  }

  return {
    isOpen,
    options,
    confirm,
    handleConfirm,
    handleCancel
  }
}
