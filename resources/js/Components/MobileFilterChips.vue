<script setup>
import { computed } from 'vue'

const props = defineProps({
  companies: { type: Array, required: true },
  saved:     { type: Set, required: true },
  filter:    { type: String, required: true },
  sheetSnap: { type: String, required: true },
})
const emit = defineEmits(['update:filter'])

const hiringCount = computed(() => props.companies.filter(c => c.hiring).length)
const savedCount  = computed(() => props.companies.filter(c => props.saved.has(c.id)).length)
</script>

<template>
  <div v-if="sheetSnap !== 'full'" class="sw-mfilter-bar">
    <button :class="['sw-mfilter', filter === 'all' && 'is-on']" @click="$emit('update:filter', 'all')">
      Tutte <em>{{ companies.length }}</em>
    </button>
    <button :class="['sw-mfilter', filter === 'hiring' && 'is-on']" @click="$emit('update:filter', 'hiring')">
      <span class="sw-tab-dot sw-tab-dot-green" />
      Assumono <em>{{ hiringCount }}</em>
    </button>
    <button :class="['sw-mfilter', filter === 'saved' && 'is-on']" @click="$emit('update:filter', 'saved')">
      <span class="sw-tab-dot sw-tab-dot-ink" />
      Salvate <em>{{ savedCount }}</em>
    </button>
  </div>
</template>
