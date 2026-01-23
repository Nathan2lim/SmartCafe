import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { User, UserUpdate } from '@/types'
import { usersService } from '@/services/users.service'

export const useUsersStore = defineStore('users', () => {
  const users = ref<User[]>([])
  const currentUser = ref<User | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchUsers(): Promise<void> {
    loading.value = true
    error.value = null

    try {
      users.value = await usersService.getAll()
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      error.value = axiosError.response?.data?.message || 'Erreur lors du chargement des utilisateurs'
    } finally {
      loading.value = false
    }
  }

  async function fetchUser(id: number): Promise<void> {
    loading.value = true
    error.value = null

    try {
      currentUser.value = await usersService.getById(id)
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      error.value = axiosError.response?.data?.message || 'Erreur lors du chargement de l\'utilisateur'
    } finally {
      loading.value = false
    }
  }

  async function updateUser(id: number, user: UserUpdate): Promise<User | null> {
    error.value = null

    try {
      const updatedUser = await usersService.update(id, user)
      const index = users.value.findIndex(u => u.id === id)
      if (index !== -1) {
        users.value[index] = updatedUser
      }
      if (currentUser.value?.id === id) {
        currentUser.value = updatedUser
      }
      return updatedUser
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      error.value = axiosError.response?.data?.message || 'Erreur lors de la mise à jour de l\'utilisateur'
      return null
    }
  }

  async function deleteUser(id: number): Promise<boolean> {
    error.value = null

    try {
      await usersService.delete(id)
      users.value = users.value.filter(u => u.id !== id)
      if (currentUser.value?.id === id) {
        currentUser.value = null
      }
      return true
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      error.value = axiosError.response?.data?.message || 'Erreur lors de la suppression de l\'utilisateur'
      return false
    }
  }

  function getUserById(id: number): User | undefined {
    return users.value.find(u => u.id === id)
  }

  return {
    users,
    currentUser,
    loading,
    error,
    fetchUsers,
    fetchUser,
    updateUser,
    deleteUser,
    getUserById
  }
})
