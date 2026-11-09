import { demoCategories, demoProducts, demoSettings } from '@/lib/demo-data'
import { isDemo, request } from '@/lib/backend'
import type {
  Category,
  CheckoutCustomer,
  Gender,
  Order,
  OrderStatus,
  PaymentMethod,
  PaymentStatus,
  Product,
  StoreSettings,
} from '@/types'

const sortVariants = (p: Product): Product => ({
  ...p,
  styles: p.styles ?? [],
  product_variants: [...p.product_variants].sort((a, b) => a.sort - b.sort),
})

// ─── Storefront ─────────────────────────────────────────────────────────────

export async function getSettings(): Promise<StoreSettings> {
  if (isDemo) return demoSettings
  return request<StoreSettings>('/settings')
}

export async function getCategories(): Promise<Category[]> {
  if (isDemo) return demoCategories
  return request<Category[]>('/categories')
}

export async function getProducts(opts: { gender?: Gender; featured?: boolean; limit?: number } = {}) {
  if (isDemo) {
    let list = demoProducts
    if (opts.gender) list = list.filter((p) => p.gender === opts.gender)
    if (opts.featured) list = list.filter((p) => p.is_featured)
    return list.slice(0, opts.limit ?? list.length)
  }
  const params = new URLSearchParams()
  if (opts.gender) params.set('gender', opts.gender)
  if (opts.featured) params.set('featured', '1')
  if (opts.limit) params.set('limit', String(opts.limit))
  const qs = params.toString()
  const data = await request<Product[]>(`/products${qs ? `?${qs}` : ''}`)
  return data.map(sortVariants)
}

export async function getProduct(slug: string): Promise<Product | null> {
  if (isDemo) return demoProducts.find((p) => p.slug === slug) ?? null
  try {
    return sortVariants(await request<Product>(`/products/${encodeURIComponent(slug)}`))
  } catch (e) {
    if ((e as { status?: number }).status === 404) return null
    throw e
  }
}

// Product ids most often ordered together with this one. Empty in demo mode;
// recommendations still work without it.
export async function getBoughtTogether(productId: string): Promise<string[]> {
  if (isDemo) return []
  try {
    const rows = await request<{ product_id: string }[]>(`/products/${encodeURIComponent(productId)}/bought-together`)
    return rows.map((r) => r.product_id)
  } catch {
    return []
  }
}

export async function placeOrder(input: {
  customer: CheckoutCustomer
  items: { variant_id: string; quantity: number }[]
  payment_method: PaymentMethod
  payment_reference?: string
}): Promise<{ id: string; order_number: number }> {
  if (isDemo) {
    await new Promise((r) => setTimeout(r, 600))
    return { id: 'demo', order_number: 1001 }
  }
  return request<{ id: string; order_number: number }>('/orders', {
    method: 'POST',
    body: {
      customer: input.customer,
      items: input.items,
      payment_method: input.payment_method,
      payment_reference: input.payment_reference ?? null,
    },
  })
}

export async function getOrder(id: string): Promise<Order | null> {
  if (isDemo) return null
  try {
    return await request<Order>(`/orders/${encodeURIComponent(id)}`)
  } catch (e) {
    if ((e as { status?: number }).status === 404) return null
    throw e
  }
}

// ─── Admin ──────────────────────────────────────────────────────────────────

export const ORDERS_PAGE_SIZE = 30

export async function adminListOrders(opts: { status: OrderStatus | 'all'; search: string; page: number }) {
  const params = new URLSearchParams({ status: opts.status, search: opts.search, page: String(opts.page) })
  return request<{ orders: Order[]; count: number }>(`/admin/orders?${params}`, { auth: true })
}

export async function adminGetOrder(id: string) {
  return request<Order>(`/admin/orders/${encodeURIComponent(id)}`, { auth: true })
}

export async function adminUpdateOrder(id: string, patch: { status?: OrderStatus; payment_status?: PaymentStatus }) {
  await request(`/admin/orders/${encodeURIComponent(id)}`, { method: 'PATCH', body: patch, auth: true })
}

export async function adminOrderStats() {
  return request<{ today: number; revenue: number; pending: number; checkTransfer: number }>('/admin/orders/stats', {
    auth: true,
  })
}

export async function adminListProducts() {
  const data = await request<Product[]>('/admin/products', { auth: true })
  return data.map(sortVariants)
}

export async function adminGetProduct(id: string) {
  return sortVariants(await request<Product>(`/admin/products/${encodeURIComponent(id)}`, { auth: true }))
}

export type VariantDraft = { id?: string; size: string; color: string; stock: number }
export type ProductDraft = Omit<Product, 'id' | 'created_at' | 'product_variants'> & {
  product_variants: VariantDraft[]
}

export async function adminSaveProduct(id: string | null, draft: ProductDraft) {
  const saved = await request<{ id: string }>(id ? `/admin/products/${encodeURIComponent(id)}` : '/admin/products', {
    method: id ? 'PUT' : 'POST',
    body: draft,
    auth: true,
  })
  return saved.id
}

export async function adminDeleteProduct(id: string) {
  await request(`/admin/products/${encodeURIComponent(id)}`, { method: 'DELETE', auth: true })
}

export async function adminUploadImage(file: File) {
  const form = new FormData()
  form.append('file', file)
  const { url } = await request<{ url: string }>('/admin/uploads', { method: 'POST', form, auth: true })
  return url
}

export async function adminSaveSettings(s: StoreSettings) {
  await request('/admin/settings', { method: 'PUT', body: s, auth: true })
}
