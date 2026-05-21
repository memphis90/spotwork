<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
defineOptions({ layout: AppLayout })
</script>

<template>
  <Head title="Cookie Policy" />
  <div class="sw-policy-page">
    <div class="sw-policy-wrap">
      <h1>Cookie Policy</h1>
      <p class="sw-policy-date">Ultimo aggiornamento: maggio 2026</p>

      <h2>Cookie tecnici</h2>
      <p>Spotwork utilizza esclusivamente cookie e storage tecnici necessari al funzionamento dell'applicazione. Non vengono installati cookie di profilazione, tracciamento o marketing.</p>

      <table class="sw-policy-table">
        <thead>
          <tr><th>Nome</th><th>Tipo</th><th>Durata</th><th>Scopo</th></tr>
        </thead>
        <tbody>
          <tr><td>sw_saved</td><td>localStorage</td><td>Persistente</td><td>Aziende salvate dall'utente</td></tr>
          <tr><td>sw_ratings</td><td>localStorage</td><td>Persistente</td><td>Valutazioni assegnate alle aziende</td></tr>
          <tr><td>sw_density</td><td>localStorage</td><td>Persistente</td><td>Preferenza di visualizzazione (compatta/spaziosa)</td></tr>
          <tr><td>sw_cookie_consent</td><td>localStorage</td><td>Persistente</td><td>Registra l'accettazione di questa informativa</td></tr>
          <tr><td>XSRF-TOKEN</td><td>Cookie di sessione</td><td>Sessione</td><td>Protezione CSRF (sicurezza)</td></tr>
          <tr><td>spotwork_session</td><td>Cookie di sessione</td><td>Sessione</td><td>Gestione sessione autenticata</td></tr>
        </tbody>
      </table>

      <h2>Come disabilitare i cookie</h2>
      <p>Puoi eliminare i dati memorizzati in qualsiasi momento dalle impostazioni del tuo browser (Strumenti sviluppatore → Application → Storage). Fai presente che cancellare questi dati rimuoverà le tue preferenze salvate.</p>

      <p><a href="/privacy" class="sw-link">← Torna alla Privacy Policy</a></p>
    </div>
  </div>
</template>
