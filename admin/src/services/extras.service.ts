import api from './api'
import type { Extra, ExtraCreate, ExtraUpdate, PaginatedResponse } from '@/types'
import { extractMembers } from '@/types'

export const extrasService = {
  async getAll(): Promise<Extra[]> {
    const response = await api.get<PaginatedResponse<Extra> | Extra[]>('/extras')
    return extractMembers(response.data)
  },

  async getById(id: number): Promise<Extra> {
    const response = await api.get<Extra>(`/extras/${id}`)
    return response.data
  },

  async getLowStock(): Promise<Extra[]> {
    const response = await api.get<PaginatedResponse<Extra> | Extra[]>('/extras/low-stock')
    return extractMembers(response.data)
  },

  async create(extra: ExtraCreate): Promise<Extra> {
    const response = await api.post<Extra>('/extras', extra)
    return response.data
  },

  async update(id: number, extra: ExtraUpdate): Promise<Extra> {
    const response = await api.patch<Extra>(`/extras/${id}`, extra, {
      headers: { 'Content-Type': 'application/merge-patch+json' }
    })
    return response.data
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/extras/${id}`)
  },

  async restock(id: number, quantity: number): Promise<Extra> {
    const response = await api.post<Extra>(`/extras/${id}/restock`, { quantity })
    return response.data
  }
}
