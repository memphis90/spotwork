<script setup>
// resources/js/Components/SearchBar.vue
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { useLoginModal } from '@/Composables/useLoginModal'


const props = defineProps({
  query:        { type: Object, required: true },
  categories:   { type: Array,  required: true },
  radii:        { type: Array,  required: true },
  loading:      { type: Boolean, default: false },
  mode:         { type: String, default: 'idle' },
})
const emit = defineEmits(['update:query', 'search'])

const page = usePage()
const user = () => page.props.auth?.user
const { openLoginModal } = useLoginModal()

const SUGGESTIONS = [
  'Milano, MI','Roma, RM','Torino, TO','Bologna, BO','Firenze, FI',
  'Napoli, NA','Genova, GE','Bergamo, BG','Brescia, BS','Padova, PD',
  'Verona, VR','Bari, BA',
]

const cityOpen    = ref(false)
const radiusOpen  = ref(false)
const catOpen     = ref(false)
const accountOpen = ref(false)
const cityRef     = ref(null)
const radiusRef   = ref(null)
const catRef      = ref(null)
const accountRef  = ref(null)
const kwInput     = ref('')

function update(patch) { emit('update:query', { ...props.query, ...patch }) }
function commit(extra)  { emit('search', { ...props.query, ...(extra || {}) }) }

function onDoc(e) {
  if (cityRef.value    && !cityRef.value.contains(e.target))    cityOpen.value = false
  if (radiusRef.value  && !radiusRef.value.contains(e.target))  radiusOpen.value = false
  if (catRef.value     && !catRef.value.contains(e.target))     catOpen.value = false
  if (accountRef.value && !accountRef.value.contains(e.target)) accountOpen.value = false
}
onMounted(() => document.addEventListener('mousedown', onDoc))
onBeforeUnmount(() => document.removeEventListener('mousedown', onDoc))

function addKeyword() {
  const kw = kwInput.value.trim().replace(/,+$/, '')
  if (!kw || (props.query.keywords || []).includes(kw)) return
  if ((props.query.keywords || []).length >= 5) return
  update({ keywords: [...(props.query.keywords || []), kw] })
  kwInput.value = ''
}
function removeKeyword(kw) {
  update({ keywords: (props.query.keywords || []).filter(k => k !== kw) })
}
function onKwKeydown(e) {
  if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); addKeyword(); commit() }
  if (e.key === 'Backspace' && !kwInput.value && (props.query.keywords || []).length) {
    removeKeyword(props.query.keywords.at(-1))
  }
}

function citySuggestions() {
  const q = (props.query.city || '').toLowerCase()
  return SUGGESTIONS.filter(s => s.toLowerCase().includes(q)).slice(0, 6)
}
function radiusLabel() {
  return props.radii.find(r => r.value === props.query.radius)?.label || 'Raggio'
}
function categoryObj() {
  return props.categories.find(c => c.id === props.query.category) || props.categories[0]
}
function userInitial() {
  return (user()?.name || '?')[0].toUpperCase()
}
function logout() {
  router.post('/logout')
}
</script>

<template>
  <div :class="['sw-search', mode === 'idle' ? 'sw-search-idle' : 'sw-search-compact']">
    <div class="sw-search-inner">

      <div class="sw-brand">
        <img src="@img/sw_full.jpg" alt="Spotwork" class="sw-brand-logo" />
      </div>

      <div class="sw-search-fields">
        <!-- city -->
        <div class="sw-field sw-field-city" ref="cityRef">
          <svg class="sw-field-ic" width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M8 14s5-4.5 5-8.5A5 5 0 1 0 3 5.5C3 9.5 8 14 8 14Z" stroke="currentColor" stroke-width="1.4"/>
            <circle cx="8" cy="5.5" r="1.8" stroke="currentColor" stroke-width="1.4"/>
          </svg>
          <input class="sw-input" placeholder="Città o comune"
                 :value="query.city"
                 @input="e => { update({ city: e.target.value }); cityOpen = true }"
                 @focus="cityOpen = true"
                 @keydown.enter="cityOpen = false; commit()"
                 @keydown.escape="cityOpen = false" />
          <div v-if="cityOpen && citySuggestions().length" class="sw-suggest">
            <button v-for="s in citySuggestions()" :key="s" class="sw-suggest-item"
                    @mousedown.prevent="update({ city: s }); cityOpen = false; commit({ city: s })">
              <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                <path d="M8 14s5-4.5 5-8.5A5 5 0 1 0 3 5.5C3 9.5 8 14 8 14Z" stroke="currentColor" stroke-width="1.4"/>
              </svg>
              <span>{{ s }}</span>
            </button>
          </div>
        </div>

        <!-- radius -->
        <div class="sw-field sw-field-radius" ref="radiusRef">
          <svg class="sw-field-ic" width="16" height="16" viewBox="0 0 16 16" fill="none">
            <circle cx="8" cy="8" r="5.5" stroke="currentColor" stroke-width="1.4"/>
            <circle cx="8" cy="8" r="1.4" fill="currentColor"/>
          </svg>
          <button class="sw-select" @click="radiusOpen = !radiusOpen">
            <span>{{ radiusLabel() }}</span>
            <svg width="10" height="10" viewBox="0 0 10 10"><path d="M2 4l3 3 3-3" stroke="currentColor" stroke-width="1.4" fill="none"/></svg>
          </button>
          <div v-if="radiusOpen" class="sw-menu">
            <button v-for="r in radii" :key="r.value"
                    :class="['sw-menu-item', r.value === query.radius && 'is-on']"
                    @click="update({ radius: r.value }); radiusOpen = false">
              {{ r.label }}
            </button>
          </div>
        </div>

        <!-- category -->
        <div class="sw-field sw-field-cat" ref="catRef">
          <span class="sw-field-ic sw-field-glyph">{{ categoryObj().icon }}</span>
          <button class="sw-select" @click="catOpen = !catOpen">
            <span>{{ categoryObj().label }}</span>
            <svg width="10" height="10" viewBox="0 0 10 10"><path d="M2 4l3 3 3-3" stroke="currentColor" stroke-width="1.4" fill="none"/></svg>
          </button>
          <div v-if="catOpen" class="sw-menu sw-menu-wide">
            <button v-for="c in categories" :key="c.id"
                    :class="['sw-menu-item', c.id === query.category && 'is-on']"
                    @click="update({ category: c.id }); catOpen = false">
              <span class="sw-menu-glyph">{{ c.icon }}</span>
              <span>{{ c.label }}</span>
            </button>
          </div>
        </div>

        <!-- keywords -->
        <div class="sw-field sw-field-keywords">
          <svg class="sw-field-ic" width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M2 5h12M2 8h8M2 11h5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
          </svg>
          <div class="sw-kw-wrap">
            <span v-for="kw in (query.keywords || [])" :key="kw" class="sw-kw-chip">
              {{ kw }}
              <button class="sw-kw-remove" @mousedown.prevent="removeKeyword(kw)" aria-label="Rimuovi">×</button>
            </span>
            <input
              v-if="(query.keywords || []).length < 5"
              class="sw-kw-input"
              :placeholder="(query.keywords || []).length === 0 ? 'es. php laravel' : ''"
              v-model="kwInput"
              @keydown="onKwKeydown"
              @blur="addKeyword"
            />
          </div>
        </div>

        <button class="sw-btn-primary" :disabled="loading" @click="commit()">
          <span v-if="loading" class="sw-spin" aria-hidden="true" />
          <svg v-else width="14" height="14" viewBox="0 0 16 16" fill="none">
            <circle cx="7" cy="7" r="4.5" stroke="currentColor" stroke-width="1.6"/>
            <path d="M11 11l3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
          </svg>
          <span>{{ loading ? 'Cerco…' : 'Cerca' }}</span>
        </button>
      </div>

      <!-- account zone -->
      <div class="sw-account" ref="accountRef">
        <template v-if="user()">
          <a href="/saved" class="sw-icon-btn" title="Salvati">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M4 2h8a1 1 0 0 1 1 1v10.5l-5-3-5 3V3a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
            </svg>
          </a>
          <a href="/account/alerts" class="sw-icon-btn" title="Alert lavoro">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M8 2a5 5 0 0 1 5 5v2.5l1.5 2H1.5L3 9.5V7a5 5 0 0 1 5-5Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
              <path d="M6.5 13.5a1.5 1.5 0 0 0 3 0" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
            </svg>
          </a>
        </template>
        <template v-else>
          <button class="sw-icon-btn" @click="openLoginModal()" title="Salvati">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M4 2h8a1 1 0 0 1 1 1v10.5l-5-3-5 3V3a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
            </svg>
          </button>
          <button class="sw-icon-btn" @click="openLoginModal()" title="Alert lavoro">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M8 2a5 5 0 0 1 5 5v2.5l1.5 2H1.5L3 9.5V7a5 5 0 0 1 5-5Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
              <path d="M6.5 13.5a1.5 1.5 0 0 0 3 0" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
            </svg>
          </button>
        </template>
        <template v-if="user()">
          <button class="sw-avatar" @click="accountOpen = !accountOpen" :title="user().name">
            {{ userInitial() }}
          </button>
          <div v-if="accountOpen" class="sw-account-menu">
            <a href="/saved" class="sw-account-item">Salvati</a>
            <a href="/account/alerts" class="sw-account-item">Alert</a>
            <button class="sw-account-item sw-account-item--danger" @click="logout">Esci</button>
          </div>
        </template>
        <template v-else>
          <button class="sw-icon-btn" @click="openLoginModal()" title="Accedi">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <circle cx="8" cy="5.5" r="2.5" stroke="currentColor" stroke-width="1.4"/>
              <path d="M2.5 14c0-3 2.5-5 5.5-5s5.5 2 5.5 5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
            </svg>
          </button>
        </template>
      </div>

    </div>
  </div>
</template>