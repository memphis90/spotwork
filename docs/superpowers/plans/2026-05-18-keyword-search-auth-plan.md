# Implementation Plan: Keyword Search + Auth + Account

**Date:** 2026-05-18
**Spec:** docs/superpowers/specs/2026-05-18-keyword-search-auth-design.md
**Status:** ready

---

## Phase 0: Documentation Discovery — COMPLETE

**Findings:**
- Breeze NOT installed. SerpAPI NOT installed. predis IS installed (^3.4).
- Existing migrations: users, cache, jobs tables only.
- `config/services.php` exists — ready for serpapi key.
- Frontend: Vue 3 + Inertia + Tailwind + Leaflet. No auth pages.
- `ApiService` wraps Guzzle — new `JobSearchService` should use it directly (same pattern as OverpassService).

**SerpAPI Google Jobs call pattern:**
```php
use SerpApi\GoogleSearch;
$search = new GoogleSearch(['api_key' => config('services.serpapi.key')]);
$results = $search->get_json([
    'engine' => 'google_jobs',
    'q'      => 'php laravel',
    'location' => 'Milano, Italy',
    'hl' => 'it', 'gl' => 'it',
]);
// $results['jobs_results'] => [{title, company_name, location, via, detected_extensions}, ...]
```

**Anti-patterns:**
- Do NOT use `tbm=jobs` (deprecated Google Search param, not the Jobs engine)
- Do NOT use phpredis extension — predis is installed, set `REDIS_CLIENT=predis` in .env
- Do NOT invent methods on `ApiService` — `JobSearchService` uses SerpAPI SDK directly

---

## Phase 1: Foundation & Dependencies

**Goal:** Install packages, configure env, fix Redis.

### Tasks

1. **Install Laravel Breeze (Inertia/Vue)**
   ```bash
   composer require laravel/breeze --dev
   php artisan breeze:install vue
   npm install
   ```
   Verify: `resources/js/Pages/Auth/Login.vue` exists.

2. **Install SerpAPI SDK**
   ```bash
   composer require serpapi/google-search-results
   ```
   Verify: `vendor/serpapi/` exists.

3. **Add to `.env`** (user must do manually):
   ```
   SERPAPI_KEY=your_key_here
   REDIS_CLIENT=predis
   ```

4. **Add to `config/services.php`** — append serpapi block:
   ```php
   'serpapi' => [
       'key' => env('SERPAPI_KEY'),
   ],
   ```

### Verification checklist
- [ ] `php artisan route:list` shows auth routes (login, register, etc.)
- [ ] `resources/js/Pages/Auth/` directory exists with Login.vue, Register.vue
- [ ] `vendor/serpapi/` directory exists
- [ ] `php artisan cache:clear` succeeds (predis fix)

---

## Phase 2: Database Migrations & Models

**Goal:** Create 4 new tables + Eloquent models.

### Tasks

1. **Create migrations** (in this order):
   ```bash
   php artisan make:migration create_companies_table
   php artisan make:migration create_saved_companies_table
   php artisan make:migration create_saved_jobs_table
   php artisan make:migration create_job_alerts_table
   ```

2. **`companies` table schema:**
   ```php
   $table->id();
   $table->unsignedBigInteger('osm_id')->nullable()->unique();
   $table->string('name');
   $table->decimal('lat', 10, 7);
   $table->decimal('lon', 10, 7);
   $table->string('category', 50)->default('all');
   $table->string('address')->nullable();
   $table->string('source')->default('osm'); // osm | serpapi
   $table->timestamps();
   ```

3. **`saved_companies` table schema:**
   ```php
   $table->id();
   $table->foreignId('user_id')->constrained()->cascadeOnDelete();
   $table->foreignId('company_id')->constrained()->cascadeOnDelete();
   $table->timestamp('saved_at')->useCurrent();
   $table->unique(['user_id', 'company_id']);
   ```

4. **`saved_jobs` table schema:**
   ```php
   $table->id();
   $table->foreignId('user_id')->constrained()->cascadeOnDelete();
   $table->foreignId('company_id')->constrained()->cascadeOnDelete();
   $table->string('job_url', 500);
   $table->string('job_title');
   $table->timestamp('saved_at')->useCurrent();
   $table->unique(['user_id', 'job_url']);
   ```

5. **`job_alerts` table schema:**
   ```php
   $table->id();
   $table->foreignId('user_id')->constrained()->cascadeOnDelete();
   $table->json('keywords');
   $table->string('city', 100);
   $table->unsignedInteger('radius');
   $table->timestamp('last_sent_at')->nullable();
   $table->timestamps();
   ```

6. **Create models:** `Company`, `SavedCompany`, `SavedJob`, `JobAlert`
   - `Company`: hasMany SavedCompany, SavedJob
   - `User`: hasMany SavedCompany, SavedJob, JobAlert (via relationships)
   - `JobAlert`: casts `keywords` as `array`

7. **Run migrations:**
   ```bash
   php artisan migrate
   ```

### Verification checklist
- [ ] `php artisan migrate:status` shows all 4 new tables as "Ran"
- [ ] `php artisan tinker` → `Company::count()` returns 0 (table exists)

---

## Phase 3: Backend — JobSearchService

**Goal:** New service that calls SerpAPI Google Jobs and returns the same company shape as Overpass.

### Tasks

1. **Create `app/Services/JobSearchService.php`**

   Pattern: same constructor style as `OverpassService`.
   
   ```php
   class JobSearchService {
       public function search(float $lat, float $lon, int $radius, array $keywords): array
       // 1. Build q string: implode(' ', $keywords)
       // 2. Reverse-geocode lat/lon to city name (use Nominatim reverse endpoint via ApiService)
       // 3. Call SerpAPI: engine=google_jobs, q, location="{city}, Italy", radius_km
       // 4. Group $results['jobs_results'] by company_name
       // 5. For each company group: geocode company location string → lat/lon
       // 6. Upsert into companies table (match on name + ~100m proximity)
       // 7. Return array of company shapes: {id, name, lat, lon, distance, category='all',
       //    address, website=null, phone=null, size=null, hiring=true, jobs=count}
       // Cache key: "jobsearch:{slug(q)}:{lat}:{lon}:{radius}" TTL 3600
   }
   ```

2. **Reverse geocode helper** — add `reverse(float $lat, float $lon): string` to `GeocodingService`:
   ```
   GET https://nominatim.openstreetmap.org/reverse?lat={lat}&lon={lon}&format=json
   Returns: result['address']['city'] ?? result['address']['town'] ?? result['display_name']
   ```

3. **Update `AppServiceProvider::register()`** — bind `JobSearchService`:
   ```php
   $this->app->bind(JobSearchService::class, fn() => new JobSearchService(
       app(ApiService::class),
       app(GeocodingService::class),
   ));
   ```

4. **Update `SearchController`:**
   - Inject `JobSearchService` in constructor
   - Add to validation: `'keywords' => 'sometimes|array|max:5', 'keywords.*' => 'string|max:50'`
   - Routing logic:
     ```php
     if ($request->filled('keywords')) {
         $companies = $this->jobSearch->search($lat, $lon, (int)$request->radius, $request->keywords);
     } else {
         $raw = $this->overpass->search(...);
         $companies = $this->transform($raw);
     }
     ```

### Verification checklist
- [ ] `GET /api/search?city=Milano&radius=5000&keywords[]=php&keywords[]=laravel` returns JSON with `companies[]`
- [ ] Each company has: `id, name, lat, lon, distance, hiring=true, jobs>=1`
- [ ] Cache entry written (check `php artisan tinker` → `Cache::has(...)`)

---

## Phase 4: Backend — Account API

**Goal:** Auth-protected endpoints for saved companies, saved jobs, alerts.

### Tasks

1. **Create `app/Http/Controllers/AccountController.php`** with methods:
   - `savedCompanies()` — return user's saved companies
   - `saveCompany(Request $r)` — upsert company + create saved_companies row
   - `unsaveCompany(int $companyId)` — delete saved_companies row
   - `savedJobs()` — return user's saved jobs
   - `saveJob(Request $r)` — upsert company + create saved_jobs row
   - `unsaveJob(int $jobId)` — delete row
   - `alerts()` — list job_alerts
   - `createAlert(Request $r)` — create job_alert
   - `deleteAlert(int $id)` — delete row

2. **Add to `routes/api.php`** (auth:sanctum middleware group):
   ```php
   Route::middleware('auth:sanctum')->group(function () {
       Route::get('/account/companies', [AccountController::class, 'savedCompanies']);
       Route::post('/account/companies', [AccountController::class, 'saveCompany']);
       Route::delete('/account/companies/{id}', [AccountController::class, 'unsaveCompany']);
       Route::get('/account/jobs', [AccountController::class, 'savedJobs']);
       Route::post('/account/jobs', [AccountController::class, 'saveJob']);
       Route::delete('/account/jobs/{id}', [AccountController::class, 'unsaveJob']);
       Route::get('/account/alerts', [AccountController::class, 'alerts']);
       Route::post('/account/alerts', [AccountController::class, 'createAlert']);
       Route::delete('/account/alerts/{id}', [AccountController::class, 'deleteAlert']);
   });
   ```

3. **localStorage migration on login** — in `AuthenticatedSessionController::store()` (Breeze file), after login success, return Inertia response with flag `migrate_saved: true`. Frontend handles the merge on next load.

### Verification checklist
- [ ] `php artisan route:list` shows all 9 account routes
- [ ] `POST /api/account/companies` without auth → 401
- [ ] `POST /api/account/companies` with auth → 201

---

## Phase 5: Frontend — SearchBar Keyword Input

**Goal:** Add keyword chip input to SearchBar. Update useSpotwork.js.

### Tasks

1. **Update `useSpotwork.js`:**
   - Add `keywords` to reactive query: `const query = reactive({ city, radius, category, keywords: [] })`
   - Pass `keywords` in axios params: `params: { ...query }` (already spreading, no change needed)

2. **Update `SearchBar.vue`** — add keyword field to `.sw-search-fields`:
   ```
   [City] | [Radius] | [Category] | [Keywords chip input]
   ```
   - New `.sw-field.sw-field-keywords` after category field
   - Internal state: `const keywordInput = ref('')`, chips from `props.query.keywords`
   - On Enter/comma: push to keywords array, clear input
   - Each chip: span with text + `×` button that splices the array
   - Emit `update:query` with new keywords array on change

3. **Add CSS to `spotwork.css`:**
   ```css
   .sw-field-keywords { flex: 1; min-width: 180px; }
   .sw-keyword-chips { display: flex; flex-wrap: wrap; gap: 4px; align-items: center; }
   .sw-chip-tag { display:inline-flex; align-items:center; gap:4px; padding: 2px 8px;
     background: var(--sw-ink); color: var(--sw-bg); border-radius: 999px; font-size:12px; }
   .sw-chip-tag button { background:none; border:none; color:inherit; cursor:pointer; padding:0; }
   ```

### Verification checklist
- [ ] Typing in keyword field + Enter adds a chip
- [ ] Clicking × removes the chip
- [ ] Search with chips calls `/api/search?keywords[]=php&keywords[]=laravel`
- [ ] Search without chips calls `/api/search` with no keywords param (Overpass flow)

---

## Phase 6: Frontend — Account UI

**Goal:** Account button in SearchBar + saved items page.

### Tasks

1. **Update `SearchBar.vue`** — add account zone (far right, outside `.sw-search-fields` pill):
   ```vue
   <div class="sw-account">
     <template v-if="$page.props.auth.user">
       <button class="sw-avatar" @click="showAccount = !showAccount">
         {{ $page.props.auth.user.name[0].toUpperCase() }}
       </button>
       <div v-if="showAccount" class="sw-account-dropdown">
         <a href="/account">Salvati</a>
         <a href="/account/alerts">Alert</a>
         <button @click="logout">Esci</button>
       </div>
     </template>
     <template v-else>
       <a href="/login" class="sw-btn-login">Accedi</a>
     </template>
   </div>
   ```

2. **Create `resources/js/Pages/Account/Index.vue`** — lists saved companies + saved jobs in two tabs. Uses Inertia shared props for auth user.

3. **Create `resources/js/Pages/Account/Alerts.vue`** — lists job alerts, form to create new alert.

4. **Add web routes** in `routes/web.php`:
   ```php
   Route::middleware('auth')->group(function () {
       Route::get('/account', fn() => Inertia::render('Account/Index'));
       Route::get('/account/alerts', fn() => Inertia::render('Account/Alerts'));
   });
   ```

5. **Add CSS** for `.sw-account`, `.sw-avatar`, `.sw-account-dropdown`, `.sw-btn-login`.

### Verification checklist
- [ ] Unauthenticated: "Accedi" link visible in SearchBar
- [ ] Login → avatar with initial appears
- [ ] `/account` renders saved companies list (empty for new user)

---

## Phase 7: Email Alerts

**Goal:** Daily scheduler sends digest emails for active alerts.

### Tasks

1. **Create `app/Mail/JobAlertDigest.php`** — Mailable with: alert keywords, new jobs list, unsubscribe link.

2. **Create `app/Console/Commands/SendJobAlerts.php`:**
   ```php
   // For each JobAlert:
   //   search(alert->keywords, alert->city, alert->radius)
   //   filter companies where jobs newer than last_sent_at (use created_at on company)
   //   if results > 0: Mail::to(user)->send(new JobAlertDigest(...))
   //   update last_sent_at = now()
   ```

3. **Register in `routes/console.php`:**
   ```php
   Schedule::command('alerts:send')->dailyAt('08:00');
   ```

4. **Verify Mailpit works in dev:** `MAIL_MAILER=smtp MAIL_HOST=localhost MAIL_PORT=1025` in .env.

### Verification checklist
- [ ] `php artisan alerts:send` runs without error
- [ ] Mailpit UI shows received email at `http://localhost:8025`

---

## Phase 8: Final Verification

### End-to-end checks

1. **Overpass flow (no keywords):**
   - Search "Milano" → companies appear on map from OSM
   - Click company → sidebar opens
   
2. **Job-first flow (with keywords):**
   - Add "php" chip → search → companies with jobs appear on map
   - Each company has `hiring: true`, `jobs >= 1`

3. **Auth flow:**
   - Register → redirected to home, avatar visible
   - Save company → appears in `/account`
   - Logout → "Accedi" appears

4. **Alert flow:**
   - Create alert for "php" in Milano
   - Run `php artisan alerts:send` manually
   - Email appears in Mailpit

### Grep checks (anti-pattern verification)
```bash
# No tbm=jobs usage
grep -r "tbm=jobs" app/
# No hardcoded API keys
grep -r "SERPAPI" app/ --include="*.php"
# All account routes are auth-protected
grep -A5 "account" routes/api.php
```