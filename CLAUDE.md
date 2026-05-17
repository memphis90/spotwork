# Project Instructions

## Commands

```bash
# Dev (all-in-one)
composer run dev

# Test
php artisan test
php artisan test --filter TestName

# Lint & Format
./vendor/bin/pint

# Build frontend
npm run build
```

## Architecture

- `app/Services/` — business logic, no direct DB from controllers
- `app/Http/Controllers/` — thin controllers, delegate to services
- `resources/js/Pages/` — Inertia Vue pages
- `resources/js/Components/` — shared Vue components
- `resources/js/Layouts/` — page layouts

## Key Decisions

- Inertia.js per SSR-like DX senza API REST separata
- Redis per cache geocoding/overpass (TTL configurabili per endpoint)
- Services layer per isolare API esterne (Nominatim, Indeed RSS, Overpass)
