// Connection to the Laravel API. Without VITE_API_URL the site runs in demo mode on sample data.
const raw = import.meta.env.VITE_API_URL as string | undefined

export const API_URL = raw ? raw.replace(/\/+$/, '') : null
export const isDemo = !API_URL

const TOKEN_KEY = 'jm_admin_token'

export function getToken(): string | null {
  try {
    return localStorage.getItem(TOKEN_KEY)
  } catch {
    return null
  }
}

export function setToken(token: string | null) {
  try {
    if (token) localStorage.setItem(TOKEN_KEY, token)
    else localStorage.removeItem(TOKEN_KEY)
  } catch {
    /* storage unavailable */
  }
}

type RequestOptions = {
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
  body?: unknown
  form?: FormData
  auth?: boolean
}

export class ApiError extends Error {
  status: number
  constructor(message: string, status: number) {
    super(message)
    this.status = status
  }
}

export async function request<T>(path: string, opts: RequestOptions = {}): Promise<T> {
  if (!API_URL) throw new Error('Connect the backend (set VITE_API_URL) to use this')

  const headers: Record<string, string> = { Accept: 'application/json' }
  if (opts.body !== undefined) headers['Content-Type'] = 'application/json'
  if (opts.auth) {
    const token = getToken()
    if (token) headers.Authorization = `Bearer ${token}`
  }

  let res: Response
  try {
    res = await fetch(`${API_URL}${path}`, {
      method: opts.method ?? 'GET',
      headers,
      body: opts.form ?? (opts.body !== undefined ? JSON.stringify(opts.body) : undefined),
    })
  } catch {
    throw new Error('Could not reach the server. Check your connection and try again.')
  }

  const text = await res.text()
  let json: any = null
  try {
    json = text ? JSON.parse(text) : null
  } catch {
    /* not JSON */
  }

  if (!res.ok) {
    if (res.status === 401 && opts.auth) setToken(null)
    // Laravel validation errors: { message, errors: { field: [msg] } }
    const firstError = json?.errors ? (Object.values(json.errors)[0] as string[] | undefined)?.[0] : undefined
    throw new ApiError(firstError ?? json?.message ?? `Request failed (${res.status})`, res.status)
  }
  return json as T
}
