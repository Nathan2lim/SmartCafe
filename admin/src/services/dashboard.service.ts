import { ordersService } from './orders.service'
import { productsService } from './products.service'
import { extrasService } from './extras.service'
import type { DashboardStats, LowStockItem, Order } from '@/types'

export const dashboardService = {
  async getStats(): Promise<DashboardStats> {
    const [todayOrders, activeOrders, lowStockProducts, lowStockExtras] = await Promise.all([
      ordersService.getToday(),
      ordersService.getActive(),
      productsService.getLowStock(),
      extrasService.getLowStock()
    ])

    const revenueToday = todayOrders
      .filter(order => order.status !== 'cancelled')
      .reduce((sum, order) => sum + order.totalAmount, 0)

    return {
      ordersToday: todayOrders.length,
      revenueToday,
      lowStockAlerts: lowStockProducts.length + lowStockExtras.length,
      activeOrders: activeOrders.length
    }
  },

  async getLowStockItems(): Promise<LowStockItem[]> {
    const [products, extras] = await Promise.all([
      productsService.getLowStock(),
      extrasService.getLowStock()
    ])

    const items: LowStockItem[] = [
      ...products.map(p => ({
        id: p.id,
        name: p.name,
        type: 'product' as const,
        stockQuantity: p.stockQuantity ?? 0,
        lowStockThreshold: p.lowStockThreshold
      })),
      ...extras.map(e => ({
        id: e.id,
        name: e.name,
        type: 'extra' as const,
        stockQuantity: e.stockQuantity,
        lowStockThreshold: e.lowStockThreshold
      }))
    ]

    return items.sort((a, b) => a.stockQuantity - b.stockQuantity)
  },

  async getRecentOrders(limit: number = 5): Promise<Order[]> {
    const orders = await ordersService.getAll()
    return orders.slice(0, limit)
  }
}
