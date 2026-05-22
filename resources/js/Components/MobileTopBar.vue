<script setup>
import { usePage } from '@inertiajs/vue3'
import { useLoginModal } from '@/Composables/useLoginModal'

const props = defineProps({
  query:      { type: Object, required: true },
  mode:       { type: String, required: true },
  loading:    { type: Boolean, default: false },
  categories: { type: Array, required: true },
  radii:      { type: Array, required: true },
})
defineEmits(['open-search', 'open-settings'])

const page = usePage()
const user = () => page.props.auth?.user
const { openLoginModal } = useLoginModal()

function catLabel() {
  return props.categories.find(c => c.id === props.query.category)?.label || ''
}
function radiusLabel() {
  return props.radii.find(r => r.value === props.query.radius)?.label || ''
}
function userInitial() {
  return (user()?.name || '?')[0].toUpperCase()
}
</script>

<template>
  <header v-if="mode !== 'idle'" class="sw-mtop">
    <div class="sw-mtop-brand">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M12 22s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12Z" fill="#0e1014"/>
        <circle cx="12" cy="10" r="2.6" fill="#fafaf7"/>
      </svg>
    </div>

    <button class="sw-mpill" @click="$emit('open-search')">
      <span v-if="loading" class="sw-spin" aria-hidden="true" />
      <svg v-else width="15" height="15" viewBox="0 0 16 16" fill="none">
        <circle cx="7" cy="7" r="4.5" stroke="currentColor" stroke-width="1.6"/>
        <path d="M11 11l3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
      </svg>
      <span class="sw-mpill-summary">
        <b>{{ query.city }}</b>
        <span class="sw-mpill-sub">{{ radiusLabel() }} · {{ catLabel() }}</span>
      </span>
      <span class="sw-mpill-edit" aria-hidden="true">
        <svg width="13" height="13" viewBox="0 0 16 16" fill="none">
          <path d="M3 13.5V11l7-7 2.5 2.5-7 7H3Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
        </svg>
      </span>
    </button>

    <button v-if="user()" class="sw-mtop-icon" style="font-size:.78rem;font-weight:700;background:var(--sw-accent);color:#fff;border:0;"
            @click="$emit('open-settings')" :aria-label="`Account di ${user().name}`">
      {{ userInitial() }}
    </button>
    <button v-else class="sw-mtop-icon" @click="openLoginModal()" aria-label="Accedi">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
        <circle cx="8" cy="6" r="2.6" stroke="currentColor" stroke-width="1.4"/>
        <path d="M3 14c.8-2.5 2.8-4 5-4s4.2 1.5 5 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
      </svg>
    </button>
  </header>
</template>
