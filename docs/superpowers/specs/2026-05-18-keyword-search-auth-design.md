# Design: Keyword Search, Auth & Account — Spotwork

**Date:** 2026-05-18
**Status:** approved

## Overview

Two additions to Spotwork:
1. Keyword tag search (job-first flow via SerpAPI Google Jobs)
2. User accounts via Laravel Breeze with server-side saved companies, saved jobs, and email alerts

---

## 1. Architecture

### Dual search modes

```
No keywords → Overpass (OSM) → companies in area → map
Keywords    → SerpAPI Google Jobs → matching jobs → companies geocoded → map
```

The backend routes conditionally on the presence of `keywords[]`. The frontend response shape is identical in both cases — `{lat, lon, companies[]}`.

### New stack

- **Laravel Breeze** (Inertia/Vue stack — already in project) for auth
- **serpapi/google-search-results** via Composer for Google Jobs API
- **Laravel Scheduler + Queue** for email alerts
- **Mailpit** in local dev for email testing

---

## 2. SearchBar UI

Three-zone flex layout:

```
[Logo]  [City | Radius | Category | Keywords input]  [Account]
```

**Keywords zone** (rightmost field in the search pill):
- Chip-style input: type → Enter or comma → chip added
- Chips: `php ×` `laravel ×` inline, removable
- Implemented in Vue with no external library (input + `ref([])`)
- When ≥1 chip: search icon changes color to signal job-first mode
- Max 5 keywords per query

**Account zone (far right):**
- Unauthenticated: minimal `Accedi` button
- Authenticated: avatar with initial + dropdown (Salvati, Alert, Esci)

**Mobile:** keywords collapse to a second row below main fields; account stays as icon.

---

## 3. Backend — Keyword flow

### New `JobSearchService`

Wraps SerpAPI Google Jobs:

```
JobSearchService::search(lat, lon, radius, keywords[])
  → SerpAPI: engine=google_jobs, q=join(keywords), location=city, radius
  → results: [{title, company_name, location, ...}, ...]
  → group by company_name
  → geocode companies without lat/lon (via existing GeocodingService)
  → return same companies[] shape as Overpass flow
```

Cache key: `jobs_search:{slug(keywords)}:{lat}:{lon}:{radius}` — TTL 1h.

### `SearchController::search()` routing

```php
'keywords'   => 'sometimes|array|max:5',
'keywords.*' => 'string|max:50',

if ($request->filled('keywords')) {
    $companies = $this->jobSearch->search($lat, $lon, $radius, $request->keywords);
} else {
    $raw       = $this->overpass->search($lat, $lon, $radius, $request->category);
    $companies = $this->transform($raw);
}
```

---

## 4. Database Schema

```sql
-- Internal company registry (both OSM and SerpAPI sources)
companies (
  id            bigint PK,
  osm_id        bigint nullable unique,
  name          varchar(255),
  lat           decimal(10,7),
  lon           decimal(10,7),
  category      varchar(50),
  address       varchar(255) nullable,
  source        enum('osm','serpapi'),
  created_at, updated_at
)

-- User saved companies
saved_companies (
  id            bigint PK,
  user_id       bigint FK users,
  company_id    bigint FK companies,
  saved_at      timestamp,
  UNIQUE(user_id, company_id)
)

-- User saved job listings
saved_jobs (
  id            bigint PK,
  user_id       bigint FK users,
  company_id    bigint FK companies,
  job_url       varchar(500),
  job_title     varchar(255),
  saved_at      timestamp,
  UNIQUE(user_id, job_url)
)

-- Email alert subscriptions
job_alerts (
  id            bigint PK,
  user_id       bigint FK users,
  keywords      json,
  city          varchar(100),
  radius        int,
  last_sent_at  timestamp nullable,
  created_at, updated_at
)
```

Company upsert strategy: match on `osm_id` if present, else on `name + ST_Distance(lat/lon) < 100m`.

---

## 5. Email Alerts

Laravel Scheduler runs daily at 08:00:

```
foreach job_alerts as alert:
  search(alert.keywords, alert.city, alert.radius)
  filter jobs newer than alert.last_sent_at
  if new_jobs > 0: send email digest → update last_sent_at
```

Mailpit in local dev. Production: any SMTP (Mailgun, Resend, etc.).

---

## 6. Auth

Laravel Breeze — Inertia/Vue variant (matches existing stack). Provides:
- Register / Login / Password reset
- Email verification (optional for beta)
- Profile page (reuse for saved items)

Saved companies currently in `localStorage` migrate to DB on first login (merge strategy: union of localStorage + DB).

---

## 7. Out of scope

- Social login (Google/GitHub) — future
- Company claiming/verification — future
- Public company profiles — future