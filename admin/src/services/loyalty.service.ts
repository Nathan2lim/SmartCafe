import api from './api'
import type {
  LoyaltyAccount,
  LoyaltyReward,
  LoyaltyRewardCreate,
  LoyaltyRewardUpdate,
  LoyaltyTransaction,
  PaginatedResponse
} from '@/types'
import { extractMembers } from '@/types'

export const loyaltyService = {
  // Rewards
  async getRewards(): Promise<LoyaltyReward[]> {
    const response = await api.get<PaginatedResponse<LoyaltyReward> | LoyaltyReward[]>('/loyalty/rewards')
    return extractMembers(response.data)
  },

  async getRewardById(id: number): Promise<LoyaltyReward> {
    const response = await api.get<LoyaltyReward>(`/loyalty/rewards/${id}`)
    return response.data
  },

  async createReward(reward: LoyaltyRewardCreate): Promise<LoyaltyReward> {
    const response = await api.post<LoyaltyReward>('/loyalty/rewards', reward)
    return response.data
  },

  async updateReward(id: number, reward: LoyaltyRewardUpdate): Promise<LoyaltyReward> {
    const response = await api.patch<LoyaltyReward>(`/loyalty/rewards/${id}`, reward, {
      headers: { 'Content-Type': 'application/merge-patch+json' }
    })
    return response.data
  },

  async deleteReward(id: number): Promise<void> {
    await api.delete(`/loyalty/rewards/${id}`)
  },

  // Accounts
  async getAccounts(): Promise<LoyaltyAccount[]> {
    const response = await api.get<PaginatedResponse<LoyaltyAccount> | LoyaltyAccount[]>('/loyalty_accounts')
    return extractMembers(response.data)
  },

  async getAccountById(id: number): Promise<LoyaltyAccount> {
    const response = await api.get<LoyaltyAccount>(`/loyalty_accounts/${id}`)
    return response.data
  },

  // Transactions
  async getTransactions(): Promise<LoyaltyTransaction[]> {
    const response = await api.get<PaginatedResponse<LoyaltyTransaction> | LoyaltyTransaction[]>('/loyalty_transactions')
    return extractMembers(response.data)
  },

  async getTransactionsByAccount(accountId: number): Promise<LoyaltyTransaction[]> {
    const response = await api.get<PaginatedResponse<LoyaltyTransaction> | LoyaltyTransaction[]>('/loyalty_transactions', {
      params: { account: accountId }
    })
    return extractMembers(response.data)
  }
}
