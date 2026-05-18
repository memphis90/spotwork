<script setup>
// resources/js/Components/SearchBar.vue
import { ref, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  query:        { type: Object, required: true },
  categories:   { type: Array,  required: true },
  radii:        { type: Array,  required: true },
  loading:      { type: Boolean, default: false },
  mode:         { type: String, default: 'idle' },
  resultsCount: { type: [Number, null], default: null },
})
const emit = defineEmits(['update:query', 'search'])

const SUGGESTIONS = [
  'Milano, MI','Roma, RM','Torino, TO','Bologna, BO','Firenze, FI',
  'Napoli, NA','Genova, GE','Bergamo, BG','Brescia, BS','Padova, PD',
  'Verona, VR','Bari, BA',
]

const cityOpen   = ref(false)
const radiusOpen = ref(false)
const catOpen    = ref(false)
const cityRef    = ref(null)
const radiusRef  = ref(null)
const catRef     = ref(null)

function update(patch) { emit('update:query', { ...props.query, ...patch }) }
function commit(extra)  { emit('search', { ...props.query, ...(extra || {}) }) }

function onDoc(e) {
  if (cityRef.value   && !cityRef.value.contains(e.target))   cityOpen.value = false
  if (radiusRef.value && !radiusRef.value.contains(e.target)) radiusOpen.value = false
  if (catRef.value    && !catRef.value.contains(e.target))    catOpen.value = false
}
onMounted(() => document.addEventListener('mousedown', onDoc))
onBeforeUnmount(() => document.removeEventListener('mousedown', onDoc))

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
</script>

<template>
  <div :class="['sw-search', mode === 'idle' ? 'sw-search-idle' : 'sw-search-compact']">
    <div class="sw-search-inner">
      <div class="sw-brand">
        <svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M12 22s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12Z" fill="#0e1014"/>
          <circle cx="12" cy="10" r="2.6" fill="#fafaf7"/>
        </svg>
        <div class="sw-brand-text">
          <b>spotwork</b>
          <span>Aziende che assumono vicino a te</span>
        </div>
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

        <button class="sw-btn-primary" :disabled="loading" @click="commit()">
          <span v-if="loading" class="sw-spin" aria-hidden="true" />
          <svg v-else width="14" height="14" viewBox="0 0 16 16" fill="none">
            <circle cx="7" cy="7" r="4.5" stroke="currentColor" stroke-width="1.6"/>
            <path d="M11 11l3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
          </svg>
          <span>{{ loading ? 'Cerco…' : 'Cerca' }}</span>
        </button>
      </div>

      <div v-if="mode === 'results' && resultsCount != null" class="sw-search-meta">
        <span class="sw-meta-dot" />
        <span>{{ resultsCount }} aziende trovate</span>
      </div>
    </div>
  </div>
</template>
