<script setup>
import { ref } from 'vue'

const STORAGE_KEY = 'sw_cookie_consent'
const show = ref(!localStorage.getItem(STORAGE_KEY))

function accept() {
  localStorage.setItem(STORAGE_KEY, '1')
  show.value = false
}
</script>

<template>
  <Teleport to="body">
    <div v-if="show" class="sw-cookie">
      <p class="sw-cookie-text">
        Usiamo cookie tecnici per il funzionamento dell'app (nessun cookie di profilazione).
        <a href="/cookie" class="sw-link">Cookie policy</a> ·
        <a href="/privacy" class="sw-link">Privacy policy</a>
      </p>
      <button class="sw-cookie-btn" @click="accept">OK, capito</button>
    </div>
  </Teleport>
</template>
