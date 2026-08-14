import api from './api'

import type { ApiResponse, LoginPayload, MeData, LoginData } from '@/types/auth'

export const authService = {
    login(payload: LoginPayload) {
        return api.post<ApiResponse<LoginData>>('/login', payload)
    },

    me() {
        return api.get<ApiResponse<MeData>>('/me')
    },

    logout() {
        return api.post<ApiResponse<unknown[]>>('/logout')
    },
}