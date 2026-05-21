<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  open:       { type: Boolean, required: true },
  query:      { type: Object, required: true },
  categories: { type: Array, required: true },
  radii:      { type: Array, required: true },
})
const emit = defineEmits(['search', 'close', 'update:query'])

const local = ref({ ...props.query })
watch(() => props.open, v => { if (v) local.value = { ...props.query } })

const kwInput = ref('')

function addKeyword() {
  const kw = kwInput.value.trim().replace(/,+$/, '')
  if (!kw || (local.value.keywords || []).includes(kw)) return
  if ((local.value.keywords || []).length >= 5) return
  local.value = { ...local.value, keywords: [...(local.value.keywords || []), kw] }
  kwInput.value = ''
}
function removeKeyword(kw) {
  local.value = { ...local.value, keywords: (local.value.keywords || []).filter(k => k !== kw) }
}
function onKwKeydown(e) {
  if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); addKeyword() }
  if (e.key === 'Backspace' && !kwInput.value && (local.value.keywords || []).length) {
    removeKeyword(local.value.keywords.at(-1))
  }
}

function commit() {
  emit('update:query', { ...local.value })
  emit('search', { ...local.value })
  emit('close')
}
</script>

<template>
  <div v-if="open" class="sw-msearch" role="dialog" aria-modal="true">
    <div class="sw-msearch-head">
      <button class="sw-mback" @click="$emit('close')" aria-label="Indietro">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
          <path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
      <div class="sw-msearch-title">Cerca</div>
    </div>

    <div class="sw-msearch-body">
      <!-- City -->
      <label class="sw-mfield">
        <span class="sw-mfield-label">Location</span>
        <div class="sw-mfield-inner">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M8 14s5-4.5 5-8.5A5 5 0 1 0 3 5.5C3 9.5 8 14 8 14Z" stroke="currentColor" stroke-width="1.4"/>
            <circle cx="8" cy="5.5" r="1.8" stroke="currentColor" stroke-width="1.4"/>
          </svg>
          <input
            class="sw-input"
            placeholder="es. Milano, MI"
            :value="local.city"
            @input="e => local.city = e.target.value"
            @keydown.enter="commit"
          />
        </div>
      </label>

      <!-- City quick-picks -->
<!--      <div class="sw-mchips">
        <button
          v-for="c in ['Milano, MI','Roma, RM','Torino, TO','Bologna, BO','Firenze, FI','Napoli, NA']"
          :key="c"
          :class="['sw-chip', local.city === c && 'is-on']"
          @click="local.city = c"
        >{{ c }}</button>
      </div>-->

      <!-- Radius -->
      <div class="sw-mfield" style="margin-top:22px;">
        <div class="sw-mfield-label-row">
          <span class="sw-mfield-label">Raggio</span>
          <span class="sw-mfield-value">{{ radii.find(r => r.value === local.radius)?.label }}</span>
        </div>
        <div class="sw-mrange-wrap">
          <input
            type="range"
            class="sw-mrange"
            :min="0"
            :max="radii.length - 1"
            :value="radii.findIndex(r => r.value === local.radius)"
            :style="`--sw-range-pct: ${radii.findIndex(r => r.value === local.radius) / (radii.length - 1) * 100}%`"
            @input="e => { local.radius = radii[+e.target.value].value }"
          />
          <div class="sw-mrange-ticks">
            <span v-for="r in radii" :key="r.value">{{ r.label }}</span>
          </div>
        </div>
      </div>

      <!-- Category -->
      <div class="sw-mfield">
        <span class="sw-mfield-label">Categoria</span>
        <div class="sw-mcatgrid">
          <button
            v-for="c in categories"
            :key="c.id"
            :class="['sw-mcat', local.category === c.id && 'is-on']"
            @click="local.category = c.id"
          >
            <span class="sw-mcat-ic">{{ c.icon }}</span>
            <span class="sw-mcat-l">{{ c.label }}</span>
          </button>
        </div>
      </div>

      <!-- Keywords -->
      <div class="sw-mfield" style="margin-top:22px;">
        <span class="sw-mfield-label">Parole chiave</span>
        <div class="sw-mfield-inner sw-kw-wrap">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" style="flex-shrink:0;">
            <path d="M2 5h12M2 8h8M2 11h5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
          </svg>
          <span v-for="kw in (local.keywords || [])" :key="kw" class="sw-kw-chip">
            {{ kw }}
            <button class="sw-kw-remove" @click.prevent="removeKeyword(kw)" aria-label="Rimuovi">×</button>
          </span>
          <input
            v-if="(local.keywords || []).length < 5"
            class="sw-kw-input"
            :placeholder="(local.keywords || []).length === 0 ? 'es. php laravel' : ''"
            v-model="kwInput"
            @keydown="onKwKeydown"
            @blur="addKeyword"
          />
        </div>
      </div>
    </div>

    <div class="sw-msearch-foot">
      <button class="sw-btn-primary sw-btn-lg" @click="commit">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
          <circle cx="7" cy="7" r="4.5" stroke="currentColor" stroke-width="1.6"/>
          <path d="M11 11l3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
        </svg>
        Mostra aziende
      </button>
    </div>
  </div>
</template>
