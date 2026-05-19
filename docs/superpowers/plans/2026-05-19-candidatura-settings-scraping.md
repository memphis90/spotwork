# Candidatura, Settings & Email Scraping — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Flusso candidatura spontanea con modale auth-aware, pagina settings (messaggio + CV), scraping email aziende via ScraperAPI, e contributo email utente.

**Architecture:** Settings + candidatura sono features auth-only servite via Inertia. Lo scraping email è un job asincrono schedulato settimanalmente. Il contributo utente è un endpoint REST JSON che scrive direttamente su `companies.email`. Tutti e tre convergono sul campo `company.email` che alimenta il mailto del modale.

**Tech Stack:** Laravel 11, Inertia.js, Vue 3 Composition API, Laravel Queues (sync driver in dev), ScraperAPI free tier, PHPUnit/Pest.

---

## File Map

| File | Azione |
|------|--------|
| `database/migrations/2026_05_19_000002_add_email_scraping_to_companies.php` | Crea |
| `database/migrations/2026_05_19_000003_add_settings_to_users.php` | Crea |
| `app/Models/User.php` | Modifica — `$fillable` |
| `app/Http/Middleware/HandleInertiaRequests.php` | Modifica — shared props |
| `app/Http/Controllers/SettingsController.php` | Crea |
| `app/Http/Controllers/CompanyController.php` | Crea |
| `app/Services/EmailScraperService.php` | Crea |
| `app/Jobs/ScrapeCompanyEmailsJob.php` | Crea |
| `app/Console/Commands/ScrapeCompanyEmailsCommand.php` | Crea |
| `config/services.php` | Modifica — scraperapi key |
| `routes/web.php` | Modifica — settings + suggest-email routes |
| `routes/console.php` | Modifica — scheduler |
| `resources/js/Pages/Settings.vue` | Crea |
| `resources/js/Components/CompanyDetail.vue` | Modifica — modale + suggest email |
| `resources/js/Components/SearchBar.vue` | Modifica — menu utente |
| `resources/css/spotwork.css` | Modifica — st-* e sw-apply-* classes |
| `tests/Feature/SettingsTest.php` | Crea |
| `tests/Feature/CompanyEmailTest.php` | Crea |
| `tests/Unit/EmailScraperServiceTest.php` | Crea |

---

## Task 1: DB Migrations

**Files:**
- Create: `database/migrations/2026_05_19_000002_add_email_scraping_to_companies.php`
- Create: `database/migrations/2026_05_19_000003_add_settings_to_users.php`

- [ ] **Step 1: Crea migration email_scraped_at**

```php
<?php
// database/migrations/2026_05_19_000002_add_email_scraping_to_companies.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->timestamp('email_scraped_at')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('email_scraped_at');
        });
    }
};
```

- [ ] **Step 2: Crea migration settings utente**

```php
<?php
// database/migrations/2026_05_19_000003_add_settings_to_users.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('application_message')->nullable()->after('email');
            $table->string('cv_path')->nullable()->after('application_message');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['application_message', 'cv_path']);
        });
    }
};
```

- [ ] **Step 3: Esegui migrations**

```bash
php artisan migrate
```

Expected: `Migrating: 2026_05_19_000002...` e `2026_05_19_000003...` entrambe OK.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/
git commit -m "feat: add email_scraped_at to companies and settings fields to users"
```

---

## Task 2: User Model + Inertia Shared Props

**Files:**
- Modify: `app/Models/User.php`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`

- [ ] **Step 1: Aggiorna User `$fillable`**

```php
// app/Models/User.php — sostituisci la riga $fillable
protected $fillable = ['name', 'email', 'password', 'application_message', 'cv_path'];
```

- [ ] **Step 2: Esponi cv_path e application_message negli shared props**

```php
// app/Http/Middleware/HandleInertiaRequests.php — metodo share()
public function share(Request $request): array
{
    $user = $request->user();
    return [
        ...parent::share($request),
        'auth'  => [
            'user' => $user ? [
                'id'                  => $user->id,
                'name'                => $user->name,
                'email'               => $user->email,
                'cv_path'             => $user->cv_path,
                'application_message' => $user->application_message,
            ] : null,
        ],
        'flash' => [
            'success' => fn () => $request->session()->get('success'),
            'error'   => fn () => $request->session()->get('error'),
        ],
    ];
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Models/User.php app/Http/Middleware/HandleInertiaRequests.php
git commit -m "feat: expose cv_path and application_message in Inertia shared props"
```

---

## Task 3: SettingsController + Routes

**Files:**
- Create: `app/Http/Controllers/SettingsController.php`
- Create: `tests/Feature/SettingsTest.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Scrivi i test failing**

```php
<?php
// tests/Feature/SettingsTest.php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_settings(): void
    {
        $this->get('/settings')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_settings(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/settings')->assertOk();
    }

    public function test_user_can_update_application_message(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->patch('/settings/message', ['message' => 'Ciao, mi candido.'])
            ->assertRedirect();
        $this->assertSame('Ciao, mi candido.', $user->fresh()->application_message);
    }

    public function test_message_max_length_is_enforced(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->patch('/settings/message', ['message' => str_repeat('a', 2001)])
            ->assertSessionHasErrors('message');
    }

    public function test_user_can_upload_cv(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('mycv.pdf', 1024, 'application/pdf');

        $this->actingAs($user)
            ->post('/settings/cv', ['cv' => $file])
            ->assertRedirect();

        $this->assertNotNull($user->fresh()->cv_path);
        Storage::disk('local')->assertExists($user->fresh()->cv_path);
    }

    public function test_cv_upload_rejects_non_pdf(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream');

        $this->actingAs($user)
            ->post('/settings/cv', ['cv' => $file])
            ->assertSessionHasErrors('cv');
    }

    public function test_user_can_delete_cv(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        Storage::disk('local')->put('cvs/1/test.pdf', 'content');
        $user->update(['cv_path' => 'cvs/1/test.pdf']);

        $this->actingAs($user)
            ->delete('/settings/cv')
            ->assertRedirect();

        $this->assertNull($user->fresh()->cv_path);
        Storage::disk('local')->assertMissing('cvs/1/test.pdf');
    }
}
```

- [ ] **Step 2: Verifica che i test falliscano**

```bash
php artisan test --filter SettingsTest
```

Expected: tutti FAIL con "404 Not Found" (route non ancora definita).

- [ ] **Step 3: Crea SettingsController**

```php
<?php
// app/Http/Controllers/SettingsController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index(): \Inertia\Response
    {
        return Inertia::render('Settings');
    }

    public function updateMessage(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate(['message' => 'required|string|max:2000']);
        $request->user()->update(['application_message' => $request->message]);
        return back()->with('success', 'Messaggio aggiornato.');
    }

    public function uploadCv(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'cv' => 'required|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $user = $request->user();

        if ($user->cv_path) {
            Storage::disk('local')->delete($user->cv_path);
        }

        $filename = $request->file('cv')->getClientOriginalName();
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        $path     = $request->file('cv')->storeAs("cvs/{$user->id}", $filename, 'local');

        $user->update(['cv_path' => $path]);
        return back()->with('success', 'CV caricato.');
    }

    public function deleteCv(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        if ($user->cv_path) {
            Storage::disk('local')->delete($user->cv_path);
            $user->update(['cv_path' => null]);
        }
        return back()->with('success', 'CV rimosso.');
    }

    public function downloadCv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = $request->user();
        abort_unless(
            $user->cv_path && Storage::disk('local')->exists($user->cv_path),
            404
        );
        return Storage::disk('local')->download($user->cv_path);
    }
}
```

- [ ] **Step 4: Aggiungi routes in web.php**

Nel gruppo `Route::middleware('auth')->group(function () {` esistente, aggiungi:

```php
use App\Http\Controllers\SettingsController;

// dentro il gruppo auth esistente:
Route::get('/settings',             [SettingsController::class, 'index'])->name('settings');
Route::patch('/settings/message',   [SettingsController::class, 'updateMessage'])->name('settings.message');
Route::post('/settings/cv',         [SettingsController::class, 'uploadCv'])->name('settings.cv.upload');
Route::delete('/settings/cv',       [SettingsController::class, 'deleteCv'])->name('settings.cv.delete');
Route::get('/settings/cv/download', [SettingsController::class, 'downloadCv'])->name('settings.cv.download');
```

- [ ] **Step 5: Verifica che i test passino**

```bash
php artisan test --filter SettingsTest
```

Expected: 7/7 PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/SettingsController.php routes/web.php tests/Feature/SettingsTest.php
git commit -m "feat: settings controller with message and CV upload"
```

---

## Task 4: Settings.vue + CSS

**Files:**
- Create: `resources/js/Pages/Settings.vue`
- Modify: `resources/css/spotwork.css`

- [ ] **Step 1: Crea Settings.vue**

```vue
<script setup>
// resources/js/Pages/Settings.vue
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref } from 'vue'
import { useForm, usePage, router, Head } from '@inertiajs/vue3'

defineOptions({ layout: AppLayout })

const page  = usePage()
const user  = page.props.auth.user

const messageForm = useForm({ message: user.application_message || '' })
const cvForm      = useForm({ cv: null })
const cvDragOver  = ref(false)
const cvInput     = ref(null)

function saveMessage() {
  messageForm.patch('/settings/message')
}

function handleCvSelect(e) {
  const file = e.target.files[0]
  if (file) submitCv(file)
}

function handleDrop(e) {
  cvDragOver.value = false
  const file = e.dataTransfer.files[0]
  if (file) submitCv(file)
}

function submitCv(file) {
  cvForm.cv = file
  cvForm.post('/settings/cv', { forceFormData: true })
}

function deleteCv() {
  if (!confirm('Rimuovere il CV salvato?')) return
  router.delete('/settings/cv')
}

function cvFilename() {
  return user.cv_path ? user.cv_path.split('/').pop() : null
}
</script>

<template>
  <Head title="Impostazioni" />
  <div class="st-page">
    <header class="st-header">
      <a href="/" class="st-back" aria-label="Torna alla mappa">
        <svg width="18" height="18" viewBox="0 0 16 16" fill="none">
          <path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="1.6"
                stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </a>
      <h1 class="st-title">Impostazioni</h1>
    </header>

    <div class="st-body">

      <!-- messaggio candidatura -->
      <section class="st-section">
        <h2 class="st-section-title">Messaggio di candidatura</h2>
        <p class="st-section-desc">
          Testo precompilato quando invii una candidatura spontanea.
          Puoi modificarlo prima di ogni invio.
        </p>
        <textarea
          class="st-textarea"
          v-model="messageForm.message"
          placeholder="Gentile team,&#10;&#10;Vi scrivo per esprimere il mio interesse..."
          rows="8"
        />
        <div v-if="messageForm.errors.message" class="st-error">
          {{ messageForm.errors.message }}
        </div>
        <div class="st-section-foot">
          <span v-if="messageForm.wasSuccessful" class="st-feedback-ok">Salvato ✓</span>
          <button class="sw-btn-primary" @click="saveMessage"
                  :disabled="messageForm.processing">
            Salva
          </button>
        </div>
      </section>

      <!-- curriculum vitae -->
      <section class="st-section">
        <h2 class="st-section-title">Curriculum vitae</h2>
        <p class="st-section-desc">
          Usato come promemoria quando invii candidature. Allegalo manualmente all'email.
        </p>

        <div v-if="user.cv_path" class="st-cv-info">
          <svg width="20" height="20" viewBox="0 0 16 16" fill="none">
            <rect x="3" y="1" width="10" height="14" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
            <path d="M5 5h6M5 8h6M5 11h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
          </svg>
          <span class="st-cv-name">{{ cvFilename() }}</span>
          <a href="/settings/cv/download" class="st-cv-btn">Scarica</a>
          <button class="st-cv-btn st-cv-btn--danger" @click="deleteCv">Rimuovi</button>
        </div>

        <template v-else>
          <div
            class="st-upload-zone"
            :class="{ 'is-over': cvDragOver }"
            @dragover.prevent="cvDragOver = true"
            @dragleave="cvDragOver = false"
            @drop.prevent="handleDrop"
            @click="cvInput.click()"
          >
            <svg width="28" height="28" viewBox="0 0 16 16" fill="none">
              <path d="M8 11V3M5 6l3-3 3 3" stroke="currentColor" stroke-width="1.4"
                    stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M2 12h12" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
            </svg>
            <p class="st-upload-label">
              Trascina il CV qui o
              <span class="st-upload-link">clicca per selezionare</span>
            </p>
            <p class="st-upload-hint">PDF, DOC, DOCX · max 5 MB</p>
            <input
              ref="cvInput"
              type="file"
              accept=".pdf,.doc,.docx"
              style="display:none"
              @change="handleCvSelect"
            />
          </div>
          <div v-if="cvForm.processing" class="st-uploading">Caricamento in corso…</div>
          <div v-if="cvForm.errors.cv" class="st-error">{{ cvForm.errors.cv }}</div>
        </template>
      </section>

    </div>
  </div>
</template>
```

- [ ] **Step 2: Aggiungi CSS in spotwork.css**

Appendi in fondo al file `resources/css/spotwork.css`:

```css
/* ───── Settings page (st-*) ───── */
.st-page { max-width: 640px; margin: 0 auto; padding: 24px 16px 64px; }

.st-header {
  display: flex; align-items: center; gap: 12px; margin-bottom: 32px;
}
.st-back {
  color: var(--sw-ink-muted); display: flex; padding: 4px;
  border-radius: 6px; transition: color .15s;
}
.st-back:hover { color: var(--sw-ink); }
.st-title { font-size: 1.25rem; font-weight: 600; color: var(--sw-ink); }

.st-body { display: flex; flex-direction: column; gap: 32px; }

.st-section {
  background: var(--sw-surface);
  border: 1px solid var(--sw-border);
  border-radius: 12px;
  padding: 24px;
  display: flex; flex-direction: column; gap: 12px;
}
.st-section-title { font-size: .9rem; font-weight: 600; color: var(--sw-ink); }
.st-section-desc  { font-size: .8rem; color: var(--sw-ink-muted); line-height: 1.5; }

.st-textarea {
  width: 100%; padding: 10px 12px;
  border: 1px solid var(--sw-border); border-radius: 8px;
  background: var(--sw-bg); color: var(--sw-ink);
  font-size: .85rem; line-height: 1.5; resize: vertical;
  font-family: inherit;
  transition: border-color .15s;
}
.st-textarea:focus { outline: none; border-color: var(--sw-accent); }

.st-section-foot {
  display: flex; align-items: center; justify-content: flex-end; gap: 12px;
}
.st-feedback-ok { font-size: .8rem; color: #22c55e; }
.st-error { font-size: .8rem; color: #ef4444; }

.st-cv-info {
  display: flex; align-items: center; gap: 10px;
  padding: 12px; background: var(--sw-bg);
  border: 1px solid var(--sw-border); border-radius: 8px;
  color: var(--sw-ink);
}
.st-cv-name { flex: 1; font-size: .85rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.st-cv-btn {
  font-size: .78rem; padding: 4px 10px;
  border: 1px solid var(--sw-border); border-radius: 6px;
  background: var(--sw-surface); color: var(--sw-ink);
  cursor: pointer; text-decoration: none; white-space: nowrap;
  transition: border-color .15s, color .15s;
}
.st-cv-btn:hover { border-color: var(--sw-accent); color: var(--sw-accent); }
.st-cv-btn--danger:hover { border-color: #ef4444; color: #ef4444; }

.st-upload-zone {
  border: 2px dashed var(--sw-border); border-radius: 10px;
  padding: 32px 16px; text-align: center; cursor: pointer;
  color: var(--sw-ink-muted);
  transition: border-color .15s, background .15s;
}
.st-upload-zone:hover,
.st-upload-zone.is-over {
  border-color: var(--sw-accent); background: color-mix(in srgb, var(--sw-accent) 5%, transparent);
}
.st-upload-label { font-size: .85rem; margin: 8px 0 4px; color: var(--sw-ink); }
.st-upload-link  { color: var(--sw-accent); text-decoration: underline; }
.st-upload-hint  { font-size: .75rem; color: var(--sw-ink-muted); }
.st-uploading    { font-size: .8rem; color: var(--sw-ink-muted); text-align: center; }
```

- [ ] **Step 3: Build frontend e verifica visivamente**

```bash
npm run build
```

Naviga a `/settings` (dopo login) e verifica:
- Sezione messaggio con textarea + pulsante Salva funzionante
- Sezione CV con upload zone → caricamento → mostra nome file + Scarica + Rimuovi
- Rimuovi elimina il file e mostra di nuovo la upload zone

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/Settings.vue resources/css/spotwork.css
git commit -m "feat: settings page with message editor and CV upload"
```

---

## Task 5: SearchBar — Menu Utente

**Files:**
- Modify: `resources/js/Components/SearchBar.vue`

- [ ] **Step 1: Sostituisci voci menu nel template**

In `resources/js/Components/SearchBar.vue`, trova il blocco `sw-account-menu` e sostituisci:

```html
<!-- PRIMA (rimuovere): -->
<a href="/saved" class="sw-account-item">Salvati</a>
<a href="/account/alerts" class="sw-account-item">Alert</a>
<button class="sw-account-item sw-account-item--danger" @click="logout">Esci</button>

<!-- DOPO: -->
<a href="/settings" class="sw-account-item">Impostazioni</a>
<button class="sw-account-item sw-account-item--danger" @click="logout">Esci</button>
```

- [ ] **Step 2: Build e verifica**

```bash
npm run build
```

Apri il menu utente (loggato): deve mostrare solo "Impostazioni" e "Esci".

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/SearchBar.vue
git commit -m "feat: replace Alert/Saved with Settings in user menu"
```

---

## Task 6: Modale "Invia Candidatura"

**Files:**
- Modify: `resources/js/Components/CompanyDetail.vue`
- Modify: `resources/css/spotwork.css`

- [ ] **Step 1: Aggiorna script in CompanyDetail.vue**

Sostituisci la sezione `<script setup>` con:

```js
<script setup>
// resources/js/Components/CompanyDetail.vue
import { ref, watch, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useLoginModal } from '@/Composables/useLoginModal'

const props = defineProps({
  company:     { type: Object, required: true },
  isSaved:     { type: Boolean, default: false },
  jobs:        { type: Array, default: () => [] },
  jobsLoading: { type: Boolean, default: false },
  category:    { type: Object, required: true },
})
const emit = defineEmits(['close','toggleSave','loadJobs'])

const page    = usePage()
const authUser = () => page.props.auth?.user
const { openLoginModal } = useLoginModal()

const tab     = ref('info')
const copied  = ref(false)

// apply modal state
const applyOpen    = ref(false)
const applyMessage = ref('')
const applyCopied  = ref(false)

// suggest email state
const suggestEmail   = ref('')
const suggestLoading = ref(false)
const suggestDone    = ref(false)
const suggestError   = ref('')

const DEFAULT_MESSAGE =
  'Gentile team,\n\nVi scrivo per esprimere il mio interesse a lavorare nella vostra azienda.\n\n' +
  'Allego il mio curriculum vitae e rimango a disposizione per un colloquio conoscitivo.\n\n' +
  'Cordiali saluti'

watch(() => props.company?.id, () => {
  tab.value     = 'info'
  copied.value  = false
  applyOpen.value = false
  suggestDone.value = false
  suggestError.value = ''
  suggestEmail.value = ''
})

function openJobs() {
  tab.value = 'jobs'
  emit('loadJobs', props.company.id)
}

async function share() {
  const text    = [props.company.name, props.company.address].filter(Boolean).join('\n')
  const mapsUrl = `https://www.google.com/maps/search/${encodeURIComponent(props.company.name + ' ' + props.company.address)}`
  if (navigator.share) {
    await navigator.share({ title: props.company.name, text, url: mapsUrl }).catch(() => {})
  } else {
    await navigator.clipboard.writeText(text + '\n' + mapsUrl)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
  }
}

function handleApply() {
  if (!authUser()) { openLoginModal(); return }
  applyMessage.value = authUser().application_message || DEFAULT_MESSAGE
  applyOpen.value = true
}

function applyMailto() {
  const subject = `Candidatura spontanea — ${props.company.name}`
  const href    = `mailto:${props.company.email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(applyMessage.value)}`
  window.open(href)
}

async function copyApplyText() {
  await navigator.clipboard.writeText(applyMessage.value)
  applyCopied.value = true
  setTimeout(() => { applyCopied.value = false }, 2000)
}

async function submitSuggestEmail() {
  suggestLoading.value = true
  suggestError.value   = ''
  try {
    const res = await fetch(`/companies/${props.company.id}/suggest-email`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
      body:    JSON.stringify({ email: suggestEmail.value }),
    })
    const data = await res.json()
    if (!res.ok) throw new Error(data.message || 'Errore')
    suggestDone.value = true
  } catch (e) {
    suggestError.value = e.message
  } finally {
    suggestLoading.value = false
  }
}
</script>
```

- [ ] **Step 2: Aggiorna template — pulsante candidatura**

Nel template, trova il blocco `sw-status-open` e sostituisci il pulsante:

```html
<!-- PRIMA: -->
<button class="sw-btn-secondary sw-btn-sm">Invia candidatura</button>

<!-- DOPO: -->
<button class="sw-btn-secondary sw-btn-sm" @click="handleApply">Invia candidatura</button>
```

- [ ] **Step 3: Aggiungi modale candidatura nel template (prima del tag </template>)**

```html
    <!-- Apply modal -->
    <Teleport to="body">
      <div v-if="applyOpen" class="sw-apply-backdrop" @click.self="applyOpen = false">
        <div class="sw-apply-modal">
          <div class="sw-apply-head">
            <h3>Candidatura — {{ company.name }}</h3>
            <button class="sw-detail-close" @click="applyOpen = false" aria-label="Chiudi">
              <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                <path d="M4 4l8 8M12 4l-8 8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
              </svg>
            </button>
          </div>

          <div v-if="authUser() && !authUser().cv_path" class="sw-apply-warning">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M8 2l6 12H2L8 2Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
              <path d="M8 7v3M8 11.5v.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
            </svg>
            Nessun CV caricato.
            <a href="/settings" class="sw-apply-warning-link">Vai in Impostazioni →</a>
            poi allegalo manualmente.
          </div>

          <textarea
            class="sw-apply-textarea"
            v-model="applyMessage"
            rows="10"
            placeholder="Scrivi il tuo messaggio..."
          />

          <div class="sw-apply-foot">
            <button class="sw-btn-secondary sw-btn-sm" @click="applyOpen = false">Annulla</button>
            <template v-if="company.email">
              <button class="sw-btn-primary sw-btn-sm" @click="applyMailto">
                Apri client email
              </button>
            </template>
            <template v-else>
              <button class="sw-btn-secondary sw-btn-sm" @click="copyApplyText">
                {{ applyCopied ? 'Copiato!' : 'Copia testo' }}
              </button>
              <span class="sw-apply-noemail">
                Email non disponibile — verifica sul sito aziendale
              </span>
            </template>
          </div>
        </div>
      </div>
    </Teleport>
```

- [ ] **Step 4: Aggiungi CSS in spotwork.css**

```css
/* ───── Apply modal (sw-apply-*) ───── */
.sw-apply-backdrop {
  position: fixed; inset: 0; z-index: 900;
  background: rgba(0,0,0,.45);
  display: flex; align-items: center; justify-content: center;
  padding: 16px;
}
.sw-apply-modal {
  background: var(--sw-surface);
  border: 1px solid var(--sw-border);
  border-radius: 14px;
  width: 100%; max-width: 540px;
  display: flex; flex-direction: column; gap: 16px;
  padding: 20px;
  box-shadow: 0 8px 32px rgba(0,0,0,.18);
}
.sw-apply-head {
  display: flex; align-items: center; justify-content: space-between;
}
.sw-apply-head h3 { font-size: .95rem; font-weight: 600; color: var(--sw-ink); }

.sw-apply-warning {
  display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
  font-size: .8rem; color: #b45309;
  background: #fef9c3; border: 1px solid #fde68a;
  border-radius: 8px; padding: 10px 12px;
}
.sw-apply-warning-link { color: #b45309; font-weight: 600; text-decoration: underline; }

.sw-apply-textarea {
  width: 100%; padding: 10px 12px;
  border: 1px solid var(--sw-border); border-radius: 8px;
  background: var(--sw-bg); color: var(--sw-ink);
  font-size: .83rem; line-height: 1.55; resize: vertical;
  font-family: inherit;
  transition: border-color .15s;
}
.sw-apply-textarea:focus { outline: none; border-color: var(--sw-accent); }

.sw-apply-foot {
  display: flex; align-items: center; justify-content: flex-end; gap: 8px; flex-wrap: wrap;
}
.sw-apply-noemail { font-size: .75rem; color: var(--sw-ink-muted); flex: 1; }
```

- [ ] **Step 5: Build e verifica**

```bash
npm run build
```

Test manuale:
- Utente guest clicca "Invia candidatura" → apre login modal ✓
- Utente loggato senza CV → modale con warning giallo ✓
- Utente loggato con CV → modale senza warning ✓
- Azienda con email → pulsante "Apri client email" → apre mailto ✓
- Azienda senza email → pulsante "Copia testo" + nota ✓

- [ ] **Step 6: Commit**

```bash
git add resources/js/Components/CompanyDetail.vue resources/css/spotwork.css
git commit -m "feat: apply candidatura modal with cv check and mailto"
```

---

## Task 7: Contributo Email Utente

**Files:**
- Create: `app/Http/Controllers/CompanyController.php`
- Create: `tests/Feature/CompanyEmailTest.php`
- Modify: `routes/web.php`
- Modify: `resources/js/Components/CompanyDetail.vue`

- [ ] **Step 1: Scrivi test failing**

```php
<?php
// tests/Feature/CompanyEmailTest.php
namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyEmailTest extends TestCase
{
    use RefreshDatabase;

    private function makeCompany(array $attrs = []): Company
    {
        return Company::create(array_merge([
            'name'     => 'Acme Srl',
            'lat'      => 45.4641,
            'lon'      => 9.1896,
            'category' => 'all',
            'source'   => 'osm',
        ], $attrs));
    }

    public function test_guest_cannot_suggest_email(): void
    {
        $company = $this->makeCompany();
        $this->postJson("/companies/{$company->id}/suggest-email", ['email' => 'test@example.com'])
             ->assertUnauthorized();
    }

    public function test_authenticated_user_can_suggest_email(): void
    {
        $user    = User::factory()->create();
        $company = $this->makeCompany();

        $this->actingAs($user)
             ->postJson("/companies/{$company->id}/suggest-email", ['email' => 'info@acme.it'])
             ->assertOk()
             ->assertJson(['message' => 'Grazie! Email aggiunta.']);

        $this->assertSame('info@acme.it', $company->fresh()->email);
    }

    public function test_suggest_email_does_not_overwrite_existing(): void
    {
        $user    = User::factory()->create();
        $company = $this->makeCompany(['email' => 'existing@acme.it']);

        $this->actingAs($user)
             ->postJson("/companies/{$company->id}/suggest-email", ['email' => 'new@acme.it'])
             ->assertUnprocessable();

        $this->assertSame('existing@acme.it', $company->fresh()->email);
    }

    public function test_suggest_email_validates_format(): void
    {
        $user    = User::factory()->create();
        $company = $this->makeCompany();

        $this->actingAs($user)
             ->postJson("/companies/{$company->id}/suggest-email", ['email' => 'notanemail'])
             ->assertUnprocessable();
    }
}
```

- [ ] **Step 2: Verifica test fallano**

```bash
php artisan test --filter CompanyEmailTest
```

Expected: tutti FAIL (route non definita).

- [ ] **Step 3: Crea CompanyController**

```php
<?php
// app/Http/Controllers/CompanyController.php
namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function suggestEmail(Request $request, Company $company): \Illuminate\Http\JsonResponse
    {
        $request->validate(['email' => 'required|email|max:255']);

        if ($company->email) {
            return response()->json(['message' => 'Email già presente.'], 422);
        }

        $company->update([
            'email'            => $request->email,
            'email_scraped_at' => now(),
        ]);

        return response()->json(['message' => 'Grazie! Email aggiunta.']);
    }
}
```

- [ ] **Step 4: Aggiungi route in web.php**

```php
use App\Http\Controllers\CompanyController;

// nel gruppo auth esistente:
Route::post('/companies/{company}/suggest-email', [CompanyController::class, 'suggestEmail'])
    ->name('companies.suggest-email');
```

- [ ] **Step 5: Verifica test passano**

```bash
php artisan test --filter CompanyEmailTest
```

Expected: 4/4 PASS.

- [ ] **Step 6: Aggiungi UI in CompanyDetail.vue — tab info**

Nel template di CompanyDetail, dentro `<div v-if="tab === 'info'" class="sw-info">`, dopo l'ultimo `sw-info-row`:

```html
<!-- suggest email — visibile solo se company.email è vuoto -->
<div v-if="!company.email" class="sw-suggest-email">
  <template v-if="!suggestDone">
    <p class="sw-suggest-email-label">
      Conosci l'email di questa azienda?
    </p>
    <div class="sw-suggest-email-form">
      <input
        class="sw-suggest-email-input"
        type="email"
        placeholder="info@azienda.it"
        v-model="suggestEmail"
        @keydown.enter="submitSuggestEmail"
      />
      <button
        class="sw-btn-secondary sw-btn-sm"
        :disabled="suggestLoading || !suggestEmail"
        @click="submitSuggestEmail"
      >
        {{ suggestLoading ? '…' : 'Suggerisci' }}
      </button>
    </div>
    <p v-if="suggestError" class="sw-suggest-email-error">{{ suggestError }}</p>
  </template>
  <p v-else class="sw-suggest-email-ok">Grazie! Email aggiunta ✓</p>
</div>
```

- [ ] **Step 7: Aggiungi CSS in spotwork.css**

```css
/* ───── Suggest email (CompanyDetail) ───── */
.sw-suggest-email {
  margin-top: 8px; padding: 12px;
  background: var(--sw-bg); border: 1px dashed var(--sw-border);
  border-radius: 8px;
}
.sw-suggest-email-label { font-size: .78rem; color: var(--sw-ink-muted); margin-bottom: 8px; }
.sw-suggest-email-form  { display: flex; gap: 6px; }
.sw-suggest-email-input {
  flex: 1; padding: 6px 10px; font-size: .82rem;
  border: 1px solid var(--sw-border); border-radius: 6px;
  background: var(--sw-surface); color: var(--sw-ink);
}
.sw-suggest-email-input:focus { outline: none; border-color: var(--sw-accent); }
.sw-suggest-email-error { font-size: .75rem; color: #ef4444; margin-top: 6px; }
.sw-suggest-email-ok    { font-size: .8rem;  color: #22c55e; }
```

- [ ] **Step 8: Build e verifica**

```bash
npm run build
```

Test manuale su un'azienda senza email: il form appare nel tab Info. Inserisci email → "Grazie! Email aggiunta ✓". Ricarica → email compare nel dettaglio.

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/CompanyController.php routes/web.php \
        tests/Feature/CompanyEmailTest.php \
        resources/js/Components/CompanyDetail.vue resources/css/spotwork.css
git commit -m "feat: user email suggestion for companies"
```

---

## Task 8: EmailScraperService + Config

**Files:**
- Modify: `config/services.php`
- Create: `app/Services/EmailScraperService.php`
- Create: `tests/Unit/EmailScraperServiceTest.php`

- [ ] **Step 1: Aggiungi ScraperAPI in services.php**

```php
// config/services.php — aggiungi dentro l'array return:
'scraperapi' => [
    'key' => env('SCRAPERAPI_KEY'),
],
```

- [ ] **Step 2: Aggiungi in .env**

```
SCRAPERAPI_KEY=your_key_here
```

(Ottieni la chiave su https://scraperapi.com — free tier 1000 req/mese)

- [ ] **Step 3: Scrivi test failing**

```php
<?php
// tests/Unit/EmailScraperServiceTest.php
namespace Tests\Unit;

use App\Services\EmailScraperService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class EmailScraperServiceTest extends TestCase
{
    private function makeService(array $responses): EmailScraperService
    {
        $mock    = new MockHandler($responses);
        $client  = new Client(['handler' => HandlerStack::create($mock)]);
        return new EmailScraperService($client);
    }

    public function test_extracts_email_from_homepage(): void
    {
        $service = $this->makeService([
            new Response(200, [], '<html>Contatti: <a href="mailto:info@acme.it">info@acme.it</a></html>'),
        ]);

        $result = $service->scrape('https://www.acme.it');
        $this->assertSame('info@acme.it', $result);
    }

    public function test_tries_contact_page_if_homepage_has_no_email(): void
    {
        $service = $this->makeService([
            new Response(200, [], '<html>No email here</html>'),
            new Response(200, [], '<html>Email: info@acme.it</html>'),
        ]);

        $result = $service->scrape('https://www.acme.it');
        $this->assertSame('info@acme.it', $result);
    }

    public function test_returns_null_if_no_email_found(): void
    {
        $service = $this->makeService([
            new Response(200, [], '<html>No email</html>'),
            new Response(200, [], '<html>No email</html>'),
            new Response(200, [], '<html>No email</html>'),
            new Response(200, [], '<html>No email</html>'),
        ]);

        $result = $service->scrape('https://www.acme.it');
        $this->assertNull($result);
    }

    public function test_skips_noreply_emails(): void
    {
        $service = $this->makeService([
            new Response(200, [], '<html>noreply@acme.it</html>'),
            new Response(200, [], '<html>info@acme.it</html>'),
        ]);

        $result = $service->scrape('https://www.acme.it');
        $this->assertSame('info@acme.it', $result);
    }

    public function test_handles_network_errors_gracefully(): void
    {
        $service = $this->makeService([
            new \GuzzleHttp\Exception\ConnectException('timeout', new \GuzzleHttp\Psr7\Request('GET', 'test')),
            new \GuzzleHttp\Exception\ConnectException('timeout', new \GuzzleHttp\Psr7\Request('GET', 'test')),
            new \GuzzleHttp\Exception\ConnectException('timeout', new \GuzzleHttp\Psr7\Request('GET', 'test')),
            new \GuzzleHttp\Exception\ConnectException('timeout', new \GuzzleHttp\Psr7\Request('GET', 'test')),
        ]);

        $result = $service->scrape('https://www.acme.it');
        $this->assertNull($result);
    }
}
```

- [ ] **Step 4: Verifica test fallano**

```bash
php artisan test --filter EmailScraperServiceTest
```

Expected: FAIL (classe non esiste).

- [ ] **Step 5: Crea EmailScraperService**

```php
<?php
// app/Services/EmailScraperService.php
namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class EmailScraperService
{
    private const PATHS = ['', '/contatti', '/contact', '/chi-siamo'];
    private const PATTERN = '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/';
    private const SKIP    = ['noreply', 'no-reply', 'donotreply'];

    public function __construct(private Client $client) {}

    public function scrape(string $website): ?string
    {
        $base = rtrim($website, '/');
        if (!str_starts_with($base, 'http')) {
            $base = 'https://' . $base;
        }

        foreach (self::PATHS as $path) {
            $url = 'https://api.scraperapi.com?' . http_build_query([
                'api_key' => config('services.scraperapi.key'),
                'url'     => $base . $path,
            ]);

            try {
                $html = (string) $this->client->get($url, ['timeout' => 15])->getBody();
                if (preg_match_all(self::PATTERN, $html, $matches)) {
                    foreach ($matches[0] as $email) {
                        $skip = false;
                        foreach (self::SKIP as $word) {
                            if (str_contains(strtolower($email), $word)) { $skip = true; break; }
                        }
                        if (!$skip) return $email;
                    }
                }
            } catch (GuzzleException) {
                continue;
            }
        }

        return null;
    }
}
```

- [ ] **Step 6: Verifica test passano**

```bash
php artisan test --filter EmailScraperServiceTest
```

Expected: 5/5 PASS.

- [ ] **Step 7: Registra in AppServiceProvider**

```php
// app/Providers/AppServiceProvider.php — dentro register():
$this->app->bind(\App\Services\EmailScraperService::class, fn() =>
    new \App\Services\EmailScraperService(new \GuzzleHttp\Client(['timeout' => 15]))
);
```

- [ ] **Step 8: Commit**

```bash
git add config/services.php app/Services/EmailScraperService.php \
        app/Providers/AppServiceProvider.php tests/Unit/EmailScraperServiceTest.php
git commit -m "feat: EmailScraperService with ScraperAPI integration"
```

---

## Task 9: ScrapeCompanyEmailsJob

**Files:**
- Create: `app/Jobs/ScrapeCompanyEmailsJob.php`

- [ ] **Step 1: Crea il Job**

```php
<?php
// app/Jobs/ScrapeCompanyEmailsJob.php
namespace App\Jobs;

use App\Models\Company;
use App\Services\EmailScraperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScrapeCompanyEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 60;

    public function __construct(public Company $company) {}

    public function handle(EmailScraperService $scraper): void
    {
        $email = $scraper->scrape($this->company->website);

        $this->company->update([
            'email'            => $email,
            'email_scraped_at' => now(),
        ]);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Jobs/ScrapeCompanyEmailsJob.php
git commit -m "feat: ScrapeCompanyEmailsJob queued job"
```

---

## Task 10: Artisan Command + Scheduler

**Files:**
- Create: `app/Console/Commands/ScrapeCompanyEmailsCommand.php`
- Modify: `routes/console.php`

- [ ] **Step 1: Crea il command**

```php
<?php
// app/Console/Commands/ScrapeCompanyEmailsCommand.php
namespace App\Console\Commands;

use App\Jobs\ScrapeCompanyEmailsJob;
use App\Models\Company;
use Illuminate\Console\Command;

class ScrapeCompanyEmailsCommand extends Command
{
    protected $signature   = 'companies:scrape-emails {--limit=200}';
    protected $description = 'Dispatch email scraping jobs for companies without email';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $companies = Company::query()
            ->whereNotNull('website')
            ->whereNull('email')
            ->where(function ($q) {
                $q->whereNull('email_scraped_at')
                  ->orWhere('email_scraped_at', '<', now()->subDays(30));
            })
            ->leftJoin('saved_companies', 'companies.id', '=', 'saved_companies.company_id')
            ->groupBy('companies.id')
            ->orderByRaw('COUNT(saved_companies.id) DESC')
            ->select('companies.*')
            ->limit($limit)
            ->get();

        foreach ($companies as $company) {
            ScrapeCompanyEmailsJob::dispatch($company);
        }

        $this->info("Dispatched {$companies->count()} jobs.");
        return self::SUCCESS;
    }
}
```

- [ ] **Step 2: Verifica il command funziona**

```bash
php artisan companies:scrape-emails --limit=1
```

Expected: `Dispatched 0 jobs.` (nessuna azienda con website nel DB di dev) oppure un numero se ci sono aziende.

- [ ] **Step 3: Aggiungi scheduler in console.php**

```php
<?php
// routes/console.php — sostituisci contenuto
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('companies:scrape-emails')->weekly();
```

- [ ] **Step 4: Verifica scheduler registrato**

```bash
php artisan schedule:list
```

Expected: `companies:scrape-emails` appare con frequenza `Weekly`.

- [ ] **Step 5: Commit finale**

```bash
git add app/Console/Commands/ScrapeCompanyEmailsCommand.php routes/console.php
git commit -m "feat: companies:scrape-emails command with weekly scheduler"
```

---

## Test Suite Completo

Esegui tutti i test prima di considerare il lavoro terminato:

```bash
php artisan test --filter "SettingsTest|CompanyEmailTest|EmailScraperServiceTest"
```

Expected: tutti PASS, zero failure.

---

## Note Operative

- **Queue driver in dev**: il driver default è `sync`, quindi i job girano in-process. Per testare lo scraping in background: `QUEUE_CONNECTION=database` in `.env` + `php artisan queue:work`.
- **Storage**: i CV sono in `storage/app/private/cvs/{userId}/`. Non esposto pubblicamente, servito solo via `/settings/cv/download`.
- **ScraperAPI free tier**: 1.000 req/mese. Con `--limit=200` e 4 path per azienda = 800 req/run. Non superare un run/mese con il piano gratuito.
- **CSRF per fetch**: il `submitSuggestEmail` usa il meta tag `csrf-token` presente nel `<head>` via `app.blade.php`.
