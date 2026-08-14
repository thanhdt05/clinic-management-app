export interface Role {
    id: number
    name: string
    display_name: string | null
}

export interface AuthUser {
    id: number
    name:string
    email: string
    is_active: boolean

    role: Role
    permissions: string[]
}

export interface LoginPayload {
    email: string
    password: string
}

export interface LoginData {
    user: AuthUser
    token: string
}

export interface MeData {
    user: AuthUser
}

export interface ApiResponse<T> {
    success: boolean
    message: string
    data: T
}

export interface ApiErrorResponse<T = Record<string, string[]>> {
    success: false
    message: string
    errors?: T
}
