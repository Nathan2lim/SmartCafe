// User types
export interface User {
  id: number
  email: string
  firstName: string
  lastName: string
  phone?: string
  roles: string[]
  loyaltyAccount?: LoyaltyAccount | string
  createdAt: string
  updatedAt?: string
}

export interface UserCreate {
  email: string
  password: string
  firstName: string
  lastName: string
  phone?: string
}

export interface UserUpdate {
  email?: string
  firstName?: string
  lastName?: string
  phone?: string
  roles?: string[]
}

// Auth types
export interface LoginCredentials {
  email: string
  password: string
}

// Product types
export interface Product {
  id: number
  name: string
  description?: string
  price: number
  category: string
  available: boolean
  alaCarte: boolean
  imageUrl?: string
  stockQuantity?: number
  lowStockThreshold: number
  lowStock?: boolean  // API returns 'lowStock' not 'isLowStock'
  availableExtras?: ProductExtra[]
  createdAt: string
  updatedAt?: string
}

export interface ProductCreate {
  name: string
  description?: string
  price: number
  category: string
  available?: boolean
  alaCarte?: boolean
  imageUrl?: string
  stockQuantity?: number
  lowStockThreshold?: number
}

export interface ProductUpdate extends Partial<ProductCreate> {}

// Extra types
export interface Extra {
  id: number
  name: string
  description?: string
  price: number
  stockQuantity: number
  lowStockThreshold: number
  available: boolean
  lowStock?: boolean  // API returns 'lowStock' not 'isLowStock'
  createdAt: string
  updatedAt?: string
}

export interface ExtraCreate {
  name: string
  description?: string
  price: number
  stockQuantity?: number
  lowStockThreshold?: number
  available?: boolean
}

export interface ExtraUpdate extends Partial<ExtraCreate> {}

// ProductExtra junction
export interface ProductExtra {
  id: number
  product: Product | string
  extra: Extra | string
  maxQuantity: number
}

// Order types
export type OrderStatus = 'pending' | 'confirmed' | 'preparing' | 'ready' | 'delivered' | 'cancelled'

export const ORDER_STATUS_LABELS: Record<OrderStatus, string> = {
  pending: 'En attente',
  confirmed: 'Confirmée',
  preparing: 'En préparation',
  ready: 'Prête',
  delivered: 'Livrée',
  cancelled: 'Annulée'
}

export const ORDER_STATUS_TRANSITIONS: Record<OrderStatus, OrderStatus[]> = {
  pending: ['confirmed', 'cancelled'],
  confirmed: ['preparing', 'cancelled'],
  preparing: ['ready', 'cancelled'],
  ready: ['delivered'],
  delivered: [],
  cancelled: []
}

export interface Order {
  id: number
  orderNumber: string
  customer: User | string
  status: OrderStatus
  items: OrderItem[]
  totalAmount: number
  notes?: string
  tableNumber?: string
  createdAt: string
  updatedAt?: string
  confirmedAt?: string
  readyAt?: string
  deliveredAt?: string
}

export interface OrderItem {
  id: number
  product: Product | string
  quantity: number
  unitPrice: number
  specialInstructions?: string
  extras?: OrderItemExtra[]
  subtotal: number
}

export interface OrderItemExtra {
  id: number
  extra: Extra | string
  quantity: number
  unitPrice: number
  subtotal: number
}

// Loyalty types
export type LoyaltyTier = 'bronze' | 'silver' | 'gold' | 'platinum' | 'diamond'

export const LOYALTY_TIER_CONFIG: Record<LoyaltyTier, { multiplier: number; upgradePoints: number | null; nextTier: LoyaltyTier | null }> = {
  bronze: { multiplier: 1.0, upgradePoints: 50, nextTier: 'silver' },
  silver: { multiplier: 1.1, upgradePoints: 150, nextTier: 'gold' },
  gold: { multiplier: 1.25, upgradePoints: 250, nextTier: 'platinum' },
  platinum: { multiplier: 1.75, upgradePoints: 500, nextTier: 'diamond' },
  diamond: { multiplier: 2.0, upgradePoints: null, nextTier: null }
}

export interface LoyaltyAccount {
  id: number
  user: User | string
  points: number
  totalPointsEarned: number
  totalPointsSpent: number
  tier: LoyaltyTier
  upgradeCost?: number
  pointsToUpgrade?: number
  nextTier?: LoyaltyTier
  canUpgrade: boolean
  currentMultiplier: number
  createdAt: string
  updatedAt?: string
}

export type RewardType = 'free_product' | 'discount_amount' | 'discount_percent' | 'free_extra' | 'double_points'

export const REWARD_TYPE_LABELS: Record<RewardType, string> = {
  free_product: 'Produit gratuit',
  discount_amount: 'Réduction (€)',
  discount_percent: 'Réduction (%)',
  free_extra: 'Extra gratuit',
  double_points: 'Points doublés'
}

export interface LoyaltyReward {
  id: number
  name: string
  description?: string
  pointsCost: number
  type: RewardType
  discountValue?: number
  discountPercent?: number
  freeProduct?: Product | string
  requiredTier?: LoyaltyTier
  active: boolean
  stockQuantity?: number
  isAvailable: boolean
  createdAt: string
  updatedAt?: string
}

export interface LoyaltyRewardCreate {
  name: string
  description?: string
  pointsCost: number
  type: RewardType
  discountValue?: number
  discountPercent?: number
  freeProduct?: string
  requiredTier?: LoyaltyTier
  active?: boolean
  stockQuantity?: number
}

export interface LoyaltyRewardUpdate extends Partial<LoyaltyRewardCreate> {}

export type LoyaltyTransactionType = 'earn' | 'redeem' | 'bonus' | 'expired' | 'adjustment'

export const TRANSACTION_TYPE_LABELS: Record<LoyaltyTransactionType, string> = {
  earn: 'Gains',
  redeem: 'Utilisation',
  bonus: 'Bonus',
  expired: 'Expirés',
  adjustment: 'Ajustement'
}

export interface LoyaltyTransaction {
  id: number
  account: LoyaltyAccount | string
  type: LoyaltyTransactionType
  points: number
  description: string
  relatedOrder?: Order | string
  redeemedReward?: LoyaltyReward | string
  createdAt: string
}

// API Response types
export interface PaginatedResponse<T> {
  'hydra:member'?: T[]
  'hydra:totalItems'?: number
  'hydra:view'?: {
    'hydra:first'?: string
    'hydra:last'?: string
    'hydra:next'?: string
    'hydra:previous'?: string
  }
  // Alternative format (API Platform without hydra prefix)
  member?: T[]
  totalItems?: number
  view?: {
    first?: string
    last?: string
    next?: string
    previous?: string
  }
}

// Helper to extract members from paginated response
export function extractMembers<T>(response: PaginatedResponse<T> | T[]): T[] {
  if (Array.isArray(response)) {
    return response
  }
  return response.member ?? response['hydra:member'] ?? []
}

// Dashboard types
export interface DashboardStats {
  ordersToday: number
  revenueToday: number
  lowStockAlerts: number
  activeOrders: number
}

export interface LowStockItem {
  id: number
  name: string
  type: 'product' | 'extra'
  stockQuantity: number
  lowStockThreshold: number
}
