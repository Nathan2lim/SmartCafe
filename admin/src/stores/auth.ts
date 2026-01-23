import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User, LoginCredentials } from '@/types'
import { authService } from '@/services/auth.service'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('token'))
  const loading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isAdmin = computed(() => user.value?.roles.includes('ROLE_ADMIN') ?? false)
  const fullName = computed(() => user.value ? `${user.value.firstName} ${user.value.lastName}` : '')

  async function login(credentials: LoginCredentials): Promise<boolean> {
    loading.value = true
    error.value = null

    try {
      // Step 1: Get token from login
      const response = await authService.login(credentials)
      token.value = response.token
      authService.setToken(response.token)

      // Step 2: Fetch user with the new token
      user.value = await authService.getCurrentUser()

      // Step 3: Check admin role
      if (!user.value.roles.includes('ROLE_ADMIN')) {
        await logout()
        error.value = 'Accès non autorisé. Compte administrateur requis.'
        return false
      }

      return true
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      error.value = axiosError.response?.data?.message || 'Identifiants invalides'
      return false
    } finally {
      loading.value = false
    }
  }

  async function logout(): Promise<void> {
    try {
      await authService.logout()
    } finally {
      user.value = null
      token.value = null
    }
  }

  async function fetchUser(): Promise<void> {
    if (!token.value) return

    loading.value = true
    try {
      user.value = await authService.getCurrentUser()

      if (!user.value.roles.includes('ROLE_ADMIN')) {
        await logout()
        error.value = 'Accès non autorisé. Compte administrateur requis.'
      }
    } catch {
      await logout()
    } finally {
      loading.value = false
    }
  }

  async function checkAuth(): Promise<boolean> {
    if (!token.value) return false
    if (user.value) return isAdmin.value

    await fetchUser()
    return isAdmin.value
  }

  return {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    isAdmin,
    fullName,
    login,
    logout,
    fetchUser,
    checkAuth
  }
})
