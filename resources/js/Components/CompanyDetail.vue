<script setup>
import { ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useLoginModal } from '@/Composables/useLoginModal'
import axios from 'axios'

const props = defineProps({
  company:  { type: Object, required: true },
  isSaved:  { type: Boolean, default: false },
  rating:   { type: Number, default: 0 },
  category: { type: Object, required: true },
  city:     { type: String, default: '' },
})
const emit = defineEmits(['close','toggleSave','rate'])

const page     = usePage()
const authUser = () => page.props.auth?.user
const { openLoginModal } = useLoginModal()

const copied = ref(false)

const glassdoor = ref(null)
const glassdoorLoading = ref(false)

async function loadGlassdoor() {
  glassdoor.value = null
  glassdoorLoading.value = true
  try {
    const { data } = await axios.get('/api/company-info', {
      params: { name: props.company.name, city: props.city },
    })
    glassdoor.value = data?.rating ? data : null
  } catch {
    glassdoor.value = null
  } finally {
    glassdoorLoading.value = false
  }
}

const applyOpen    = ref(false)
const applyMessage = ref('')
const applyCopied  = ref(false)

const suggestEmail   = ref('')
const suggestLoading = ref(false)
const suggestDone    = ref(false)
const suggestError   = ref('')

const DEFAULT_MESSAGE =
  'Gentile team,\n\nVi scrivo per esprimere il mio interesse a lavorare nella vostra azienda.\n\n' +
  'Allego il mio curriculum vitae e rimango a disposizione per un colloquio conoscitivo.\n\n' +
  'Cordiali saluti'

watch(() => props.company?.id, () => {
  copied.value       = false
  applyOpen.value    = false
  suggestDone.value  = false
  suggestError.value = ''
  suggestEmail.value = ''
  loadGlassdoor()
}, { immediate: true })

function indeedUrl() {
  return 'https://it.indeed.com/jobs?q=' + encodeURIComponent(props.company.name)
       + (props.city ? '&l=' + encodeURIComponent(props.city) : '')
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
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
      },
      body: JSON.stringify({ email: suggestEmail.value }),
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

<template>
  <div class="sw-detail">
    <div class="sw-detail-head">
      <button class="sw-detail-close" @click="$emit('close')" aria-label="Chiudi">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
          <path d="M4 4l8 8M12 4l-8 8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
        </svg>
      </button>
      <div :class="['sw-detail-cat', 'sw-cat-' + company.category]"><span>{{ category.icon }}</span></div>
      <div class="sw-detail-title">
        <div class="sw-detail-cat-label">{{ category.label }}</div>
        <h2>{{ company.name }}</h2>
      </div>
      <button class="sw-save sw-save-lg" @click="share" :title="copied ? 'Copiato!' : 'Condividi'">
        <svg v-if="copied" width="18" height="18" viewBox="0 0 16 16" fill="none">
          <path d="M3 8l4 4 6-7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <svg v-else width="18" height="18" viewBox="0 0 16 16" fill="none">
          <circle cx="12" cy="4" r="2" stroke="currentColor" stroke-width="1.4"/>
          <circle cx="4" cy="8" r="2" stroke="currentColor" stroke-width="1.4"/>
          <circle cx="12" cy="12" r="2" stroke="currentColor" stroke-width="1.4"/>
          <path d="M6 7l4-2M6 9l4 2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
        </svg>
      </button>
      <button :class="['sw-save sw-save-lg', isSaved && 'is-on']"
              @click="$emit('toggleSave', company.id)"
              :title="isSaved ? 'Rimuovi dai salvati' : 'Salva'">
        <svg v-if="isSaved" width="18" height="18" viewBox="0 0 16 16"><path d="M4 2h8v12l-4-2.5L4 14V2Z" fill="currentColor"/></svg>
        <svg v-else width="18" height="18" viewBox="0 0 16 16" fill="none"><path d="M4 2h8v12l-4-2.5L4 14V2Z" stroke="currentColor" stroke-width="1.4"/></svg>
      </button>
    </div>

    <div class="sw-detail-status">
      <div v-if="company.hiring" class="sw-status-card sw-status-hiring">
        <div class="sw-status-num">{{ company.jobs }}</div>
        <div class="sw-status-text">
          <b>annunci attivi su Indeed</b>
          <span>Aggiornato di recente</span>
        </div>
        <a class="sw-btn-primary sw-btn-sm" :href="indeedUrl()" target="_blank" rel="noreferrer">Vedi annunci</a>
      </div>
      <div v-else class="sw-status-card sw-status-open">
        <div class="sw-status-num">✉</div>
        <div class="sw-status-text">
          <b>Nessun annuncio attivo</b>
          <span>Aperta a candidature spontanee — invia il CV all'azienda</span>
        </div>
        <button class="sw-btn-secondary sw-btn-sm" @click="handleApply">Invia candidatura</button>
      </div>
    </div>

    <div class="sw-detail-body">
      <div class="sw-detail-ratings">
        <div class="sw-stars sw-stars-detail">
          <span v-for="n in 5" :key="n"
                :class="['sw-star', 'sw-star-btn', n <= rating && 'is-on']"
                :title="n + ' stelle'"
                @click="$emit('rate', company.id, rating === n ? 0 : n)">★</span>
          <span v-if="rating" class="sw-stars-label">{{ rating }}/5</span>
          <span v-else class="sw-stars-label sw-stars-label-empty">Valuta</span>
        </div>

        <a v-if="glassdoor" class="sw-glassdoor-badge"
           :href="glassdoor.url" target="_blank" rel="noreferrer">
          <span class="sw-glassdoor-logo">G</span>
          <span class="sw-glassdoor-rating">{{ glassdoor.rating }}</span>
          <span class="sw-glassdoor-reviews">{{ glassdoor.reviews.toLocaleString('it') }} rec.</span>
        </a>
        <span v-else-if="glassdoorLoading" class="sw-glassdoor-loading">…</span>
      </div>

      <div class="sw-info">
        <div class="sw-info-row">
          <div class="sw-info-label">Indirizzo</div>
          <div class="sw-info-val">
            {{ company.address }}
            <a class="sw-link" target="_blank" rel="noreferrer"
               :href="'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(company.address)">
              Indicazioni →
            </a>
          </div>
        </div>
        <div v-if="company.website" class="sw-info-row">
          <div class="sw-info-label">Sito web</div>
          <div class="sw-info-val"><a class="sw-link" target="_blank" rel="noreferrer" :href="company.website.startsWith('http') ? company.website : 'https://' + company.website">{{ company.website }}</a></div>
        </div>
        <div v-if="company.email" class="sw-info-row">
          <div class="sw-info-label">Email</div>
          <div class="sw-info-val"><a class="sw-link" :href="'mailto:' + company.email">{{ company.email }}</a></div>
        </div>
        <div class="sw-info-row">
          <div class="sw-info-label">Telefono</div>
          <div class="sw-info-val"><a class="sw-link" :href="'tel:' + company.phone">{{ company.phone }}</a></div>
        </div>
        <div class="sw-info-row">
          <div class="sw-info-label">Dipendenti</div>
          <div class="sw-info-val">{{ company.size }}</div>
        </div>
        <div class="sw-info-row">
          <div class="sw-info-label">Distanza</div>
          <div class="sw-info-val">{{ company.distance.toFixed(1).replace('.', ',') }} km dal centro</div>
        </div>
        <div class="sw-info-row">
          <div class="sw-info-label">Fonte</div>
          <div class="sw-info-val">OpenStreetMap · Overpass API</div>
        </div>

        <div v-if="!company.email" class="sw-suggest-email">
          <template v-if="!suggestDone">
            <p class="sw-suggest-email-label">Conosci l'email di questa azienda?</p>
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
              >{{ suggestLoading ? '…' : 'Suggerisci' }}</button>
            </div>
            <p v-if="suggestError" class="sw-suggest-email-error">{{ suggestError }}</p>
          </template>
          <p v-else class="sw-suggest-email-ok">Grazie! Email aggiunta ✓</p>
        </div>
      </div>

    </div>
  </div>

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
</template>
