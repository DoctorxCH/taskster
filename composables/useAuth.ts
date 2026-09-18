export interface User {
  id: string
  name: string
  email: string
  company_id: string | null
  company_role: string | null
  company_name?: string
  company_plan?: string
  company_settings?: any
  is_superadmin: boolean
  is_pro: boolean
}

export const useAuth = () => {
  const user = useState<User | null>('auth_user', () => null)
  const token = useState<string | null>('auth_token', () => null)
  const loading = useState<boolean>('auth_loading', () => true)

  const initAuth = async () => {
    if (import.meta.client) {
      const savedToken = localStorage.getItem('taskster_token')
      if (savedToken) {
        token.value = savedToken
        try {
          const res = await $fetch<{ user: User }>('/api/auth/me', {
            headers: { Authorization: `Bearer ${savedToken}` }
          })
          user.value = res.user
        } catch {
          localStorage.removeItem('taskster_token')
          token.value = null
          user.value = null
        }
      }
      loading.value = false
    }
  }

  const setAuth = (newToken: string, newUser: User) => {
    token.value = newToken
    user.value = newUser
    if (import.meta.client) {
      localStorage.setItem('taskster_token', newToken)
    }
  }

  const logout = () => {
    token.value = null
    user.value = null
    if (import.meta.client) {
      localStorage.removeItem('taskster_token')
      navigateTo('/login')
    }
  }

  const authHeaders = () => {
    return token.value ? { Authorization: `Bearer ${token.value}` } : {}
  }

  return {
    user,
    token,
    loading,
    initAuth,
    setAuth,
    logout,
    authHeaders
  }
}
