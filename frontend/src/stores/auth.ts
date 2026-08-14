import {computed, ref} from 'vue'
import {defineStore} from 'pinia'

import { authService } from '@/services/authService'

import type {AuthUser, LoginPayload} from '@/types/auth'

const TOKEN_KEY = 'token'

export const useAuthStore = defineStore('auth', () => {
    const token = ref<string | null>(localStorage.getItem(TOKEN_KEY) || null)
    
    const user = ref<AuthUser | null>(null)
    const initialized = ref(false)

    const isAuthenticated = computed(
      () => !!token.value && !!user.value,
    )

    const permissions = computed(
        () => user.value?.permissions ?? []
    )

    async function login(payload:LoginPayload) {
        const response = await authService.login(payload)

        token.value = response.data.data.token
        user.value = response.data.data.user

        localStorage.setItem(
            TOKEN_KEY,
            token.value
        )
    }

    async function fetchMe() {
        const response = await authService.me()
        
        user.value = response.data.data.user
    }
    
    async function initialize() {
        if (!token.value){
            initialized.value = true
            return
        }   

        try {
            await fetchMe()
        } catch (error) {
            clearAuth()
        } finally {
            initialized.value = true
        }
    }

    async function logout() {
      try {
        await authService.logout()
      } finally {
        clearAuth()
      }
    }

    function clearAuth() {
        token.value = null
        user.value = null

        localStorage.removeItem(TOKEN_KEY)
    }

    function can(permission: string): boolean {
      return permissions.value.includes(permission)
    }

    return {
        token,
        user,
        initialized,
        isAuthenticated,
        permissions,
        login,
        fetchMe,
        initialize,
        logout,
        clearAuth,
        can,
    }
})

