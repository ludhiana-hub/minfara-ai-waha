# CLAUDE.md — MinFara AI CMS Frontend

> Baca file ini setiap kali memulai session baru.
> Ikuti semua konvensi di sini TANPA pengecualian.

---

## 0. FIRST THING: Baca Repo Laravel

Sebelum mengerjakan APAPUN, Claude WAJIB membaca struktur repo Laravel yang ada
agar memahami konteks backend sepenuhnya.

Jalankan perintah berikut secara berurutan:

```bash
# 1. Lihat seluruh struktur folder repo Laravel
find ../minfara-bot -type f -name "*.php" | sort

# 2. Baca semua routes
cat ../minfara-bot/routes/web.php
cat ../minfara-bot/routes/api.php 2>/dev/null || echo "No api.php yet"

# 3. Baca semua Controllers
cat ../minfara-bot/app/Http/Controllers/*.php

# 4. Baca semua Models
cat ../minfara-bot/app/Models/*.php

# 5. Baca migrations untuk struktur DB
ls ../minfara-bot/database/migrations/
cat ../minfara-bot/database/migrations/*.php

# 6. Baca semua Blade views untuk memahami UI yang sudah ada
find ../minfara-bot/resources/views -name "*.blade.php" | xargs cat

# 7. Baca konfigurasi environment (tanpa value sensitif)
cat ../minfara-bot/.env.example 2>/dev/null || cat ../minfara-bot/.env | grep -v "=.*" | head -50

# 8. Baca composer.json untuk dependencies yang dipakai
cat ../minfara-bot/composer.json

# 9. Baca config WAHA / services
cat ../minfara-bot/config/services.php 2>/dev/null
cat ../minfara-bot/config/app.php
```

Setelah membaca semua file di atas, Claude harus:
- Memahami semua endpoint yang ada (dari routes)
- Memahami struktur database (dari migrations + models)
- Memahami logika bisnis (dari controllers)
- Memetakan apa yang perlu dibuatkan endpoint API baru di Laravel

---

## 1. Identitas Project

| Item | Value |
|------|-------|
| Project name | MinFara AI CMS |
| Repo frontend | `minfara-cms/` (Next.js) |
| Repo backend | `minfara-bot/` (Laravel) |
| Deployment | Docker via Dokploy |
| Domain | mitfara.com (atau subdomain CMS) |

---

## 2. Tech Stack Frontend

```
Next.js 15          → App Router (WAJIB, bukan Pages Router)
TypeScript          → strict mode ON, NO any kecuali terpaksa
Tailwind CSS v4     → utility-first, NO inline style kecuali dynamic value
shadcn/ui           → komponen utama, install via CLI
React Query v5      → semua data fetching, NO useEffect untuk fetch
Axios               → HTTP client, instance di lib/api.ts
Zustand             → global state (auth, sidebar, settings)
Framer Motion       → animasi page transition & micro-interaction
Lucide React        → icon set utama
Space Mono          → font monospace untuk label/kode
Outfit              → font utama UI
```

---

## 3. Design System — WAJIB IKUTI

### Warna (sesuai mockup yang sudah disetujui)

```ts
// Warna ini TIDAK boleh diganti tanpa konfirmasi
export const colors = {
  bg: {
    base:    '#070810',  // background paling dalam
    surface: '#0b0d1a',  // card / panel
    elevated:'#0e1020',  // hover state
    border:  '#12152a',  // semua border
    border2: '#1a1f35',  // border lebih terang
  },
  accent: {
    ai:      '#6c63ff',  // purple — AI mode, brand color
    live:    '#00e676',  // green — WAHA connected, aktif
    info:    '#4fc3f7',  // blue — info, stats
    warn:    '#ff9800',  // orange — warning, FAQ mode
  },
  text: {
    primary:  '#e4e6f0', // heading utama
    secondary:'#a0a8c8', // body text
    muted:    '#3d4463', // label, hint
    ghost:    '#21253a', // sangat redup, dekoratif
  }
}
```

### Typography

```ts
// globals.css atau tailwind config
fontFamily: {
  sans: ['Outfit', 'sans-serif'],
  mono: ['Space Mono', 'monospace'],
}

// Penggunaan:
// - font-mono → semua kode, label teknis, timestamp, nomor WA
// - font-sans → semua UI text biasa
```

### Animasi wajib

```ts
// Setiap halaman baru → fadeUp stagger untuk cards
// Setiap baris table → slideR dengan delay bertahap
// Stat cards → scan line effect (CSS animation)
// Status dot (WAHA) → pulse + ring-out
// Ticker bar di topbar → horizontal scroll loop
// Thinking dots → blink stagger untuk AI loading state
```

---

## 4. Struktur Folder Next.js

```
minfara-cms/
├── app/
│   ├── (auth)/
│   │   └── login/
│   │       └── page.tsx
│   ├── (dashboard)/
│   │   ├── layout.tsx          ← sidebar + topbar + ticker
│   │   ├── page.tsx            ← Dashboard
│   │   ├── faq/
│   │   │   ├── page.tsx        ← list + bulk actions + drag reorder
│   │   │   └── [id]/
│   │   │       └── page.tsx    ← edit form
│   │   ├── konfigurasi/
│   │   │   └── page.tsx        ← bot settings, API key, mode
│   │   ├── log/
│   │   │   └── page.tsx        ← full log + filter + SSE realtime
│   │   └── test/
│   │       └── page.tsx        ← form test kirim pesan WA
│   ├── globals.css
│   └── layout.tsx              ← root layout + fonts + providers
│
├── components/
│   ├── ui/                     ← shadcn/ui (jangan edit manual)
│   ├── layout/
│   │   ├── Sidebar.tsx
│   │   ├── Topbar.tsx
│   │   └── TickerBar.tsx       ← status ticker loop
│   ├── dashboard/
│   │   ├── StatCard.tsx
│   │   ├── WahaStatusCard.tsx
│   │   ├── LogTable.tsx
│   │   └── ActivityFeed.tsx
│   ├── faq/
│   │   ├── FaqTable.tsx        ← dengan drag reorder (dnd-kit)
│   │   ├── FaqForm.tsx
│   │   └── FaqBulkActions.tsx
│   ├── log/
│   │   ├── LogFilter.tsx
│   │   └── LogStream.tsx       ← SSE consumer
│   └── shared/
│       ├── ModeBadge.tsx       ← ai | faq | end_chat badge
│       ├── StatusDot.tsx
│       └── EmptyState.tsx
│
├── lib/
│   ├── api.ts                  ← axios instance + interceptors
│   ├── queryClient.ts          ← React Query config
│   └── utils.ts
│
├── hooks/
│   ├── useWahaStatus.ts        ← polling 10 detik
│   ├── useLogs.ts              ← React Query + SSE
│   ├── useFaq.ts
│   └── useAuth.ts
│
├── store/
│   ├── authStore.ts            ← Zustand auth state
│   └── uiStore.ts              ← sidebar collapse, theme
│
├── types/
│   ├── faq.ts
│   ├── log.ts
│   ├── waha.ts
│   └── api.ts
│
├── CLAUDE.md                   ← file ini
├── .env.local
├── tailwind.config.ts
├── next.config.ts
└── docker/
    ├── Dockerfile
    └── .dockerignore
```

---

## 5. Backend API (Laravel)

### Base URL

```ts
// .env.local
NEXT_PUBLIC_API_URL=http://localhost:8000
// production:
NEXT_PUBLIC_API_URL=https://api.mitfara.com
```

### Axios instance (lib/api.ts)

```ts
import axios from 'axios'

const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL + '/api',
  withCredentials: true,  // untuk Sanctum cookie auth
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  }
})

// Request interceptor → tambah CSRF token
api.interceptors.request.use(config => {
  const token = document.cookie
    .split('; ')
    .find(row => row.startsWith('XSRF-TOKEN='))
    ?.split('=')[1]
  if (token) config.headers['X-XSRF-TOKEN'] = decodeURIComponent(token)
  return config
})

// Response interceptor → handle 401 → redirect login
api.interceptors.response.use(
  res => res,
  err => {
    if (err.response?.status === 401) {
      window.location.href = '/login'
    }
    return Promise.reject(err)
  }
)

export default api
```

### Endpoint yang akan dibuat di Laravel (berdasarkan hasil baca repo)

Claude WAJIB menyesuaikan daftar ini setelah membaca routes Laravel:

```
# Auth
POST   /api/login
POST   /api/logout
GET    /api/me

# FAQ
GET    /api/faq              → list semua FAQ
POST   /api/faq              → create
GET    /api/faq/{id}         → detail
PUT    /api/faq/{id}         → update
DELETE /api/faq/{id}         → delete
POST   /api/faq/bulk-toggle  → toggle aktif/nonaktif bulk
POST   /api/faq/reorder      → update urutan drag-drop

# Log Percakapan
GET    /api/log              → list dengan pagination & filter
GET    /api/log/stream       → SSE endpoint (real-time)
GET    /api/log/stats        → stats hari ini

# WAHA
GET    /api/waha/status      → status koneksi + nomor
POST   /api/waha/send        → kirim pesan manual (test)

# Konfigurasi
GET    /api/config           → semua setting
PUT    /api/config           → update setting
```

---

## 6. Konvensi Kode

### TypeScript

```ts
// ✅ BENAR
interface FaqItem {
  id: number
  question: string
  answer: string
  is_active: boolean
  order: number
  mode: 'faq' | 'ai'
  created_at: string
}

// ❌ SALAH — jangan pakai any
const data: any = await api.get('/faq')
```

### React Query

```ts
// ✅ BENAR — semua fetch pakai React Query
const { data: faqs, isLoading } = useQuery({
  queryKey: ['faq'],
  queryFn: () => api.get<FaqItem[]>('/faq').then(r => r.data),
})

// ❌ SALAH — jangan pakai useEffect untuk fetch
useEffect(() => {
  fetch('/api/faq').then(...)
}, [])
```

### Komponen

```tsx
// ✅ BENAR — selalu typed props
interface StatCardProps {
  label: string
  value: number | string
  trend?: 'up' | 'down' | 'neutral'
  trendValue?: string
  icon: React.ReactNode
  accentColor?: string
}

export function StatCard({ label, value, trend, icon }: StatCardProps) {
  // ...
}
```

### Tailwind

```tsx
// ✅ BENAR — gunakan class, bukan style kecuali untuk dynamic value
<div className="bg-[#0b0d1a] border border-[#12152a] rounded-[14px] p-4">

// ✅ OK untuk dynamic value saja
<div style={{ height: `${percentage}%` }}>

// ❌ SALAH — jangan hardcode style untuk hal yang bisa pakai class
<div style={{ background: '#0b0d1a', borderRadius: '14px' }}>
```

---

## 7. Real-time: SSE untuk Log

```ts
// hooks/useLogs.ts
import { useEffect, useState } from 'react'
import { LogItem } from '@/types/log'

export function useLogStream() {
  const [logs, setLogs] = useState<LogItem[]>([])

  useEffect(() => {
    const es = new EventSource(
      `${process.env.NEXT_PUBLIC_API_URL}/api/log/stream`,
      { withCredentials: true }
    )

    es.onmessage = (e) => {
      const newLog: LogItem = JSON.parse(e.data)
      setLogs(prev => [newLog, ...prev].slice(0, 100)) // max 100 entries
    }

    es.onerror = () => es.close()

    return () => es.close()
  }, [])

  return logs
}
```

---

## 8. WAHA Status Polling

```ts
// hooks/useWahaStatus.ts
export function useWahaStatus() {
  return useQuery({
    queryKey: ['waha-status'],
    queryFn: () => api.get('/waha/status').then(r => r.data),
    refetchInterval: 10_000,     // polling 10 detik
    staleTime: 8_000,
  })
}
```

---

## 9. Docker Setup

### Dockerfile (minfara-cms/docker/Dockerfile)

```dockerfile
FROM node:20-alpine AS base
WORKDIR /app

FROM base AS deps
COPY package*.json ./
RUN npm ci

FROM base AS builder
COPY --from=deps /app/node_modules ./node_modules
COPY . .
RUN npm run build

FROM base AS runner
ENV NODE_ENV=production
COPY --from=builder /app/.next/standalone ./
COPY --from=builder /app/.next/static ./.next/static
COPY --from=builder /app/public ./public
EXPOSE 3000
CMD ["node", "server.js"]
```

### docker-compose.yml (root repo)

```yaml
version: '3.8'
services:
  cms-frontend:
    build:
      context: ./minfara-cms
      dockerfile: docker/Dockerfile
    ports:
      - "3000:3000"
    environment:
      - NEXT_PUBLIC_API_URL=http://bot-backend:8000
    depends_on:
      - bot-backend

  bot-backend:
    build: ./minfara-bot
    ports:
      - "8000:8000"
    # ... config Laravel yang sudah ada
```

---

## 10. Checklist Sebelum Commit

```
[ ] TypeScript: tidak ada error (npx tsc --noEmit)
[ ] Semua fetch pakai React Query, bukan useEffect
[ ] Tidak ada hardcoded URL (pakai NEXT_PUBLIC_API_URL)
[ ] Komponen baru punya typed props
[ ] Animasi sesuai design system (fadeUp, slideR, pulse, scan)
[ ] Warna sesuai palet di section 3 (JANGAN ganti sembarangan)
[ ] Font: Outfit untuk UI, Space Mono untuk label/kode/timestamp
[ ] Dark theme konsisten: bg-[#070810] untuk base
[ ] ModeBadge dipakai untuk semua tampilan mode (ai/faq/end_chat)
[ ] Error state ditangani di setiap query
[ ] Loading skeleton ada di setiap halaman
```

---

## 11. Cara Claude Harus Bekerja

1. **Selalu baca repo Laravel dulu** (lihat section 0) sebelum membuat apapun
2. **Jangan asumsi** struktur API — baca dari routes yang ada
3. **Jika endpoint belum ada** di Laravel, buatkan sekalian di Laravel + konsumsi di Next.js
4. **Tanya sebelum refactor besar** — jangan ubah arsitektur tanpa konfirmasi
5. **Satu task = satu branch** (jika pakai git)
6. **Setiap komponen baru** → cek dulu apakah shadcn/ui punya yang cocok
7. **Jangan install package baru** tanpa bilang ke user

---

*Last updated: 30 Mei 2026 · MinFara AI CMS*
