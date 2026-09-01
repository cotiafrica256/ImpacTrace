import { defineStore } from 'pinia'
import api from '../api/client'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('meal_user') || 'null'),
    token: localStorage.getItem('meal_token') || null,
    selectedOrganizationId: Number(localStorage.getItem('meal_selected_org') || '0') || null,
  }),
  getters: {
    isAuthenticated: (state) => !!state.token,
    role: (state) => state.user?.role,
    // Convenience flags used all over the UI/router for role gating.
    isSuperAdmin: (state) => state.user?.role === 'super_admin',
    isEd: (state) => state.user?.role === 'ed',
    isMeo: (state) => state.user?.role === 'meo',
    isPo: (state) => state.user?.role === 'po',
    isFo: (state) => state.user?.role === 'fo',
    canManageProjects: (state) => ['ed', 'meo'].includes(state.user?.role),
    canReview: (state) => ['ed', 'meo', 'po'].includes(state.user?.role),
    selectedOrganization: (state) => state.user?.organizations?.find((org) => org.id === state.selectedOrganizationId) || null,
  },
  actions: {
    async login(email, password) {
      const { data } = await api.post('/login', { email, password })
      this.token = data.token
      this.user = data.user
      this.selectedOrganizationId = this.user?.role === 'super_admin' ? null : this.user?.organization_id || null
      localStorage.setItem('meal_token', data.token)
      localStorage.setItem('meal_user', JSON.stringify(data.user))
      localStorage.removeItem('meal_selected_org')
      if (this.selectedOrganizationId) {
        localStorage.setItem('meal_selected_org', String(this.selectedOrganizationId))
      }
    },
    setSelectedOrganization(organizationId) {
      this.selectedOrganizationId = organizationId ? Number(organizationId) : null
      if (this.selectedOrganizationId) {
        localStorage.setItem('meal_selected_org', String(this.selectedOrganizationId))
      } else {
        localStorage.removeItem('meal_selected_org')
      }
    },
    async logout() {
      try { await api.post('/logout') } catch (e) { /* ignore */ }
      this.token = null
      this.user = null
      this.selectedOrganizationId = null
      localStorage.removeItem('meal_token')
      localStorage.removeItem('meal_user')
      localStorage.removeItem('meal_selected_org')
    },
  },
})
