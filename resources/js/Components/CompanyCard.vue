<script setup>
// resources/js/Components/CompanyCard.vue
defineProps({
  c:        { type: Object, required: true },
  density:  { type: String, default: 'cozy' },
  active:   { type: Boolean, default: false },
  saved:    { type: Boolean, default: false },
  category: { type: Object, required: true },
})
const emit = defineEmits(['select', 'toggleSave'])
</script>

<template>
  <div
    :class="['sw-card', active && 'is-active', density === 'compact' && 'is-compact']"
    @click="$emit('select', c.id)"
  >
    <div class="sw-card-l">
      <div :class="['sw-card-cat', 'sw-cat-' + c.category]"><span>{{ category.icon }}</span></div>
    </div>
    <div class="sw-card-body">
      <div class="sw-card-row">
        <h4 class="sw-card-name">{{ c.name }}</h4>
        <button :class="['sw-save', saved && 'is-on']"
                @click.stop="$emit('toggleSave', c.id)"
                :title="saved ? 'Rimuovi dai salvati' : 'Salva'">
          <svg v-if="saved" width="14" height="14" viewBox="0 0 16 16"><path d="M4 2h8v12l-4-2.5L4 14V2Z" fill="currentColor"/></svg>
          <svg v-else width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M4 2h8v12l-4-2.5L4 14V2Z" stroke="currentColor" stroke-width="1.4"/></svg>
        </button>
      </div>
      <div class="sw-card-meta">
        <span>{{ category.label }}</span>
        <span class="sw-dot-sep">·</span>
        <span>{{ c.distance.toFixed(1).replace('.', ',') }} km</span>
        <span class="sw-dot-sep">·</span>
        <span>{{ c.size }} dip.</span>
      </div>
      <div v-if="density !== 'compact'" class="sw-card-addr">{{ c.address }}</div>
      <div class="sw-card-foot">
        <span v-if="c.hiring" class="sw-badge sw-badge-hiring">
          <span class="sw-badge-dot" />
          {{ c.jobs }} {{ c.jobs === 1 ? 'annuncio' : 'annunci' }} su Indeed
        </span>
        <span v-else class="sw-badge sw-badge-open">
          <span class="sw-badge-dot" />
          Aperta a candidature spontanee
        </span>
      </div>
    </div>
  </div>
</template>
