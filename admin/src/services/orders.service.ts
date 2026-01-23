import api from './api'
import type { Order, OrderStatus, PaginatedResponse } from '@/types'
import { extractMembers } from '@/types'

export const ordersService = {
  async getAll(status?: OrderStatus): Promise<Order[]> {
    const params = status ? { status } : {}
    const response = await api.get<PaginatedResponse<Order> | Order[]>('/orders', { params })
    return extractMembers(response.data)
  },

  async getById(id: number): Promise<Order> {
    const response = await api.get<Order>(`/orders/${id}`)
    return response.data
  },

  async getToday(): Promise<Order[]> {
    const today = new Date().toISOString().split('T')[0]
    const response = await api.get<PaginatedResponse<Order> | Order[]>('/orders', {
      params: { 'createdAt[after]': today }
    })
    return extractMembers(response.data)
  },

  async getActive(): Promise<Order[]> {
    const response = await api.get<PaginatedResponse<Order> | Order[]>('/orders', {
      params: { 'status[]': ['pending', 'confirmed', 'preparing', 'ready'] }
    })
    return extractMembers(response.data)
  },

  async updateStatus(id: number, status: OrderStatus): Promise<Order> {
    const response = await api.patch<Order>(`/orders/${id}`, { status }, {
      headers: { 'Content-Type': 'application/merge-patch+json' }
    })
    return response.data
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/orders/${id}`)
  }
}
