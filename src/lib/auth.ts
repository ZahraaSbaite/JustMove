import { getToken, request, setToken } from '@/lib/backend'

export interface AdminUser {
  id: number
  name: string
  email: string
}
export interface Session {
  user: AdminUser
}

const listeners = new Set<(s: Session | null) => void>()
const emit = (s: Session | null) => listeners.forEach((fn) => fn(s))

export async function getSession(): Promise<Session | null> {
  if (!getToken()) return null
  try {
    const user = await request<AdminUser>('/admin/me', { auth: true })
    return { user }
  } catch {
    setToken(null)
    return null
  }
}

export async function signIn(email: string, password: string) {
  const { token, user } = await request<{ token: string; user: AdminUser }>('/admin/login', {
    method: 'POST',
    body: { email, password },
  })
  setToken(token)
  emit({ user })
}

export async function signOut() {
  try {
    await request('/admin/logout', { method: 'POST', auth: true })
  } catch {
    /* token already invalid */
  }
  setToken(null)
  emit(null)
}

export function onAuthChange(fn: (s: Session | null) => void) {
  listeners.add(fn)
  return () => {
    listeners.delete(fn)
  }
}
