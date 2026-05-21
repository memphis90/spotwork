<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
defineOptions({ layout: AppLayout })
</script>

<template>
  <Head title="Privacy Policy" />
  <div class="sw-policy-page">
    <div class="sw-policy-wrap">
      <h1>Privacy Policy</h1>
      <p class="sw-policy-date">Ultimo aggiornamento: maggio 2026</p>

      <h2>1. Titolare del trattamento</h2>
      <p>Spotwork è un progetto open source a scopo dimostrativo. Non raccoglie dati personali tramite server.</p>

      <h2>2. Dati raccolti</h2>
      <p>L'applicazione non invia dati personali a server propri. Le preferenze dell'utente (città salvate, aziende preferite, valutazioni) sono memorizzate esclusivamente nel <code>localStorage</code> del browser e non vengono mai trasmesse.</p>

      <h2>3. Servizi di terze parti</h2>
      <ul>
        <li><strong>OpenStreetMap / Nominatim:</strong> le ricerche geografiche vengono inoltrate ai server di Nominatim. Consulta la <a href="https://osmfoundation.org/wiki/Privacy_Policy" target="_blank" rel="noreferrer" class="sw-link">privacy policy di OSMF</a>.</li>
        <li><strong>CARTO:</strong> le tile della mappa sono servite da CARTO. Consulta la <a href="https://carto.com/privacy/" target="_blank" rel="noreferrer" class="sw-link">privacy policy di CARTO</a>.</li>
        <li><strong>SerpAPI / Google Jobs:</strong> le ricerche di annunci di lavoro sono inoltrate a SerpAPI. Consulta la <a href="https://serpapi.com/privacy-policy" target="_blank" rel="noreferrer" class="sw-link">privacy policy di SerpAPI</a>.</li>
      </ul>

      <h2>4. Cookie</h2>
      <p>Utilizziamo solo cookie tecnici necessari al funzionamento. Non utilizziamo cookie di profilazione o di tracciamento. Consulta la <a href="/cookie" class="sw-link">cookie policy</a> per i dettagli.</p>

      <h2>5. Contatti</h2>
      <p>Per qualsiasi domanda apri una issue su <a href="https://github.com/memphis90/spotwork" target="_blank" rel="noreferrer" class="sw-link">GitHub</a>.</p>
    </div>
  </div>
</template>
