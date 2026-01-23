import api from './api'
import type { User, UserUpdate, PaginatedResponse } from '@/types'
import { extractMembers } from '@/types'

export const usersService = {
  async getAll(): Promise<User[]> {
    const response = await api.get<PaginatedResponse<User> | User[]>('/users')
    return extractMembers(response.data)
  },

  async getById(id: number): Promise<User> {
    const response = await api.get<User>(`/users/${id}`)
    return response.data
  },

  async update(id: number, user: UserUpdate): Promise<User> {
    const response = await api.patch<User>(`/users/${id}`, user, {
      headers: { 'Content-Type': 'application/merge-patch+json' }
    })
    return response.data
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/users/${id}`)
  }
}
