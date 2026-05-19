<script setup>
// resources/js/Components/CompanyDetail.vue
import { ref, watch, computed } from 'vue'

const props = defineProps({
  company:     { type: Object, required: true },
  isSaved:     { type: Boolean, default: false },
  jobs:        { type: Array, default: () => [] },
  jobsLoading: { type: Boolean, default: false },
  category:    { type: Object, required: true },
})
const emit = defineEmits(['close','toggleSave','loadJobs'])

const tab = ref('info')
const copied = ref(false)

watch(() => props.company?.id, () => { tab.value = 'info'; copied.value = false })

function openJobs() {
  tab.value = 'jobs'
  emit('loadJobs', props.company.id)
}

async function share() {
  const text = [props.company.name, props.company.address].filter(Boolean).join('\n')
  const mapsUrl = `https://www.google.com/maps/search/${encodeURIComponent(props.company.name + ' ' + props.company.address)}`
  if (navigator.share) {
    await navigator.share({ title: props.company.name, text, url: mapsUrl }).catch(() => {})
  } else {
    await navigator.clipboard.writeText(text + '\n' + mapsUrl)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
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
        <button class="sw-btn-secondary sw-btn-sm">Invia candidatura</button>
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
        <div class="sw-info-row">
          <div class="sw-info-label">Sito web</div>
          <div class="sw-info-val"><a class="sw-link" target="_blank" rel="noreferrer" :href="'https://' + company.website">{{ company.website }}</a></div>
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
</template>
