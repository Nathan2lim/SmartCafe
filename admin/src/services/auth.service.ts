import api from './api'
import type { LoginCredentials, User } from '@/types'

export interface LoginResponse {
  token: string
}

export const authService = {
  async login(credentials: LoginCredentials): Promise<LoginResponse> {
    const response = await api.post<LoginResponse>('/login', credentials, {
      withCredentials: true
    })
    return response.data
  },

  async logout(): Promise<void> {
    try {
      await api.post('/token/revoke', {}, { withCredentials: true })
    } finally {
      localStorage.removeItem('token')
    }
  },

  async getCurrentUser(): Promise<User> {
    const response = await api.get('/auth/me')
    return response.data
  },

  async refreshToken(): Promise<string> {
    const response = await api.post('/token/refresh', {}, {
      withCredentials: true
    })
    return response.data.token
  },

  isAuthenticated(): boolean {
    return !!localStorage.getItem('token')
  },

  getToken(): string | null {
    return localStorage.getItem('token')
  },

  setToken(token: string): void {
    localStorage.setItem('token', token)
  }
}
