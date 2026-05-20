<script setup>
// resources/js/Components/CompanyDetail.vue
import { ref, watch } from 'vue'
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

const page     = usePage()
const authUser = () => page.props.auth?.user
const { openLoginModal } = useLoginModal()

const tab    = ref('info')
const copied = ref(false)

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
  tab.value          = 'info'
  copied.value       = false
  applyOpen.value    = false
  suggestDone.value  = false
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
        <button class="sw-btn-primary sw-btn-sm" @click="openJobs">Vedi annunci</button>
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

    <div class="sw-detail-tabs">
      <button :class="tab === 'info' && 'is-on'" @click="tab = 'info'">Informazioni</button>
      <button :class="tab === 'jobs' && 'is-on'"
              :disabled="!company.hiring"
              @click="company.hiring && openJobs()">
        Annunci <em v-if="company.hiring">{{ company.jobs }}</em>
      </button>
    </div>

    <div class="sw-detail-body">
      <div v-if="tab === 'info'" class="sw-info">
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

      <div v-else-if="tab === 'jobs'" class="sw-jobs">
        <div v-if="jobsLoading" class="sw-skel-wrap sw-skel-wrap-jobs">
          <div v-for="i in 4" :key="i" class="sw-skel sw-skel-job">
            <div class="sw-skel-body">
              <div class="sw-skel-l sw-skel-l-1" />
              <div class="sw-skel-l sw-skel-l-2" />
            </div>
          </div>
        </div>
        <div v-else-if="!jobs.length" class="sw-state">
          <h3>Nessun annuncio caricato</h3><p>Riprova tra qualche minuto.</p>
        </div>
        <ul v-else class="sw-job-list">
          <li v-for="(j, i) in jobs" :key="i" class="sw-job">
            <div class="sw-job-l">
              <h4>{{ j.title }}</h4>
              <div class="sw-job-meta">
                <span>{{ j.type }}</span>
                <span class="sw-dot-sep">·</span>
                <span>{{ j.salary }}</span>
                <span class="sw-dot-sep">·</span>
                <span class="sw-job-posted">{{ j.posted }}</span>
              </div>
            </div>
            <a class="sw-job-cta" :href="j.url" target="_blank" rel="noreferrer">
              Apri su Indeed
              <svg width="11" height="11" viewBox="0 0 12 12" fill="none">
                <path d="M3 9l6-6M5 3h4v4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
              </svg>
            </a>
          </li>
        </ul>
        <div v-if="!jobsLoading && jobs.length" class="sw-jobs-foot">
          Fonte: Indeed RSS · <a class="sw-link" href="#">Vedi tutti gli annunci →</a>
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
