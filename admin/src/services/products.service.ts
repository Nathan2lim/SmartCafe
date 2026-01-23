import api from './api'
import type { Product, ProductCreate, ProductUpdate, PaginatedResponse } from '@/types'
import { extractMembers } from '@/types'

export const productsService = {
  async getAll(): Promise<Product[]> {
    const response = await api.get<PaginatedResponse<Product> | Product[]>('/products')
    return extractMembers(response.data)
  },

  async getById(id: number): Promise<Product> {
    const response = await api.get<Product>(`/products/${id}`)
    return response.data
  },

  async getLowStock(): Promise<Product[]> {
    const response = await api.get<PaginatedResponse<Product> | Product[]>('/products/low-stock')
    return extractMembers(response.data)
  },

  async create(product: ProductCreate): Promise<Product> {
    const response = await api.post<Product>('/products', product)
    return response.data
  },

  async update(id: number, product: ProductUpdate): Promise<Product> {
    const response = await api.patch<Product>(`/products/${id}`, product, {
      headers: { 'Content-Type': 'application/merge-patch+json' }
    })
    return response.data
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/products/${id}`)
  },

  async getCategories(): Promise<string[]> {
    const products = await this.getAll()
    const categories = new Set(products.map(p => p.category))
    return Array.from(categories).sort()
  }
}
