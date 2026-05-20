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
        <span class="sw-mfield-label">Città</span>
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
      <div class="sw-mchips">
        <button
          v-for="c in ['Milano, MI','Roma, RM','Torino, TO','Bologna, BO','Firenze, FI','Napoli, NA']"
          :key="c"
          :class="['sw-chip', local.city === c && 'is-on']"
          @click="local.city = c"
        >{{ c }}</button>
      </div>

      <!-- Radius -->
      <div class="sw-mfield" style="margin-top:22px;">
        <span class="sw-mfield-label">Raggio</span>
        <div class="sw-mradio">
          <button
            v-for="r in radii"
            :key="r.value"
            :class="['sw-mradio-opt', local.radius === r.value && 'is-on']"
            @click="local.radius = r.value"
          >{{ r.label }}</button>
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
