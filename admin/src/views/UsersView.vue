<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUsersStore } from '@/stores/users'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { formatDate } from '@/utils/formatters'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import EmptyState from '@/components/common/EmptyState.vue'

const router = useRouter()
const usersStore = useUsersStore()
const toast = useToast()
const confirmDialog = useConfirm()

const searchQuery = ref('')

const filteredUsers = computed(() => {
  if (!searchQuery.value) return usersStore.users

  const query = searchQuery.value.toLowerCase()
  return usersStore.users.filter(u =>
    u.email.toLowerCase().includes(query) ||
    u.firstName.toLowerCase().includes(query) ||
    u.lastName.toLowerCase().includes(query)
  )
})

onMounted(() => {
  usersStore.fetchUsers()
})

function viewUser(id: number) {
  router.push(`/users/${id}`)
}

async function handleDelete(id: number) {
  const user = usersStore.getUserById(id)
  if (!user) return

  const confirmed = await confirmDialog.confirm({
    title: 'Supprimer l\'utilisateur',
    message: `Êtes-vous sûr de vouloir supprimer ${user.firstName} ${user.lastName} ?`,
    confirmText: 'Supprimer',
    type: 'danger'
  })

  if (!confirmed) return

  const success = await usersStore.deleteUser(id)
  if (success) {
    toast.success('Utilisateur supprimé')
  } else {
    toast.error(usersStore.error || 'Erreur lors de la suppression')
  }
}

function isAdmin(roles: string[]): boolean {
  return roles.includes('ROLE_ADMIN')
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between gap-4 mb-6">
      <!-- Search -->
      <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Rechercher un utilisateur..."
          class="input pl-10 w-80"
        />
      </div>
    </div>

    <!-- Loading -->
    <div v-if="usersStore.loading && usersStore.users.length === 0" class="flex items-center justify-center py-12">
      <LoadingSpinner size="lg" />
    </div>

    <!-- Empty state -->
    <EmptyState
      v-else-if="filteredUsers.length === 0"
      :title="searchQuery ? 'Aucun résultat' : 'Aucun utilisateur'"
      :description="searchQuery ? 'Aucun utilisateur ne correspond à votre recherche.' : 'Il n\'y a pas encore d\'utilisateurs.'"
      icon="users"
    />

    <!-- Users table -->
    <div v-else class="card overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rôle</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inscrit le</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr
            v-for="user in filteredUsers"
            :key="user.id"
            class="hover:bg-gray-50 cursor-pointer"
            @click="viewUser(user.id)"
          >
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                  <span class="text-sm font-medium text-primary-700">
                    {{ user.firstName.charAt(0) }}{{ user.lastName.charAt(0) }}
                  </span>
                </div>
                <div>
                  <p class="font-medium text-gray-900">{{ user.firstName }} {{ user.lastName }}</p>
                  <p v-if="user.phone" class="text-sm text-gray-500">{{ user.phone }}</p>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 text-gray-600">{{ user.email }}</td>
            <td class="px-6 py-4">
              <span
                :class="[
                  'badge',
                  isAdmin(user.roles) ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-700'
                ]"
              >
                {{ isAdmin(user.roles) ? 'Admin' : 'Utilisateur' }}
              </span>
            </td>
            <td class="px-6 py-4 text-gray-600">{{ formatDate(user.createdAt) }}</td>
            <td class="px-6 py-4" @click.stop>
              <div class="flex items-center justify-end gap-2">
                <button
                  @click="viewUser(user.id)"
                  class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                  title="Voir"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
                <button
                  v-if="!isAdmin(user.roles)"
                  @click="handleDelete(user.id)"
                  class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                  title="Supprimer"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
