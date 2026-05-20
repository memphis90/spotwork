<script setup>
import { usePage } from '@inertiajs/vue3'
import { useLoginModal } from '@/Composables/useLoginModal'

defineEmits(['open-search'])

const page = usePage()
const user = () => page.props.auth?.user
const { openLoginModal } = useLoginModal()
</script>

<template>
  <div class="sw-mdock">
    <button class="sw-mdock-pill" @click="$emit('open-search')">
      <svg width="17" height="17" viewBox="0 0 16 16" fill="none">
        <circle cx="7" cy="7" r="4.5" stroke="currentColor" stroke-width="1.6"/>
        <path d="M11 11l3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
      </svg>
      <span class="sw-mdock-pill-text">
        <b>Cerca aziende vicino a te</b>
        <span>Inserisci città, raggio e categoria</span>
      </span>
      <span class="sw-mdock-pill-chev" aria-hidden="true">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
          <path d="M5 12l5-4-5-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
    </button>

    <div class="sw-mdock-bar">
      <div class="sw-mdock-brand">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M12 22s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12Z" fill="currentColor"/>
          <circle cx="12" cy="10" r="2.6" fill="var(--sw-bg)"/>
        </svg>
        <b>spotwork</b>
      </div>
      <div class="sw-mdock-actions">
        <template v-if="user()">
          <a href="/saved" class="sw-mdock-iconbtn" title="Salvati">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M4 2h8a1 1 0 0 1 1 1v10.5l-5-3-5 3V3a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
            </svg>
          </a>
        </template>
        <template v-else>
          <button class="sw-mdock-iconbtn" @click="openLoginModal()" aria-label="Accedi">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <circle cx="8" cy="6" r="2.6" stroke="currentColor" stroke-width="1.4"/>
              <path d="M3 14c.8-2.5 2.8-4 5-4s4.2 1.5 5 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
            </svg>
          </button>
        </template>
      </div>
    </div>
  </div>
</template>
