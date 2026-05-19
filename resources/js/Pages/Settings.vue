<script setup>
// resources/js/Pages/Settings.vue
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref } from 'vue'
import { useForm, usePage, router, Head } from '@inertiajs/vue3'

defineOptions({ layout: AppLayout })

const page  = usePage()
const user  = page.props.auth.user

const messageForm = useForm({ message: user.application_message || '' })
const cvForm      = useForm({ cv: null })
const cvDragOver  = ref(false)
const cvInput     = ref(null)

function saveMessage() {
  messageForm.patch('/settings/message')
}

function handleCvSelect(e) {
  const file = e.target.files[0]
  if (file) submitCv(file)
}

function handleDrop(e) {
  cvDragOver.value = false
  const file = e.dataTransfer.files[0]
  if (file) submitCv(file)
}

function submitCv(file) {
  cvForm.cv = file
  cvForm.post('/settings/cv', { forceFormData: true })
}

function deleteCv() {
  if (!confirm('Rimuovere il CV salvato?')) return
  router.delete('/settings/cv')
}

function cvFilename() {
  return user.cv_path ? user.cv_path.split('/').pop() : null
}
</script>

<template>
  <Head title="Impostazioni" />
  <div class="st-page">
    <header class="st-header">
      <a href="/" class="st-back" aria-label="Torna alla mappa">
        <svg width="18" height="18" viewBox="0 0 16 16" fill="none">
          <path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="1.6"
                stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </a>
      <h1 class="st-title">Impostazioni</h1>
    </header>

    <div class="st-body">

      <!-- messaggio candidatura -->
      <section class="st-section">
        <h2 class="st-section-title">Messaggio di candidatura</h2>
        <p class="st-section-desc">
          Testo precompilato quando invii una candidatura spontanea.
          Puoi modificarlo prima di ogni invio.
        </p>
        <textarea
          class="st-textarea"
          v-model="messageForm.message"
          placeholder="Gentile team,&#10;&#10;Vi scrivo per esprimere il mio interesse..."
          rows="8"
        />
        <div v-if="messageForm.errors.message" class="st-error">
          {{ messageForm.errors.message }}
        </div>
        <div class="st-section-foot">
          <span v-if="messageForm.wasSuccessful" class="st-feedback-ok">Salvato ✓</span>
          <button class="sw-btn-primary" @click="saveMessage"
                  :disabled="messageForm.processing">
            Salva
          </button>
        </div>
      </section>

      <!-- curriculum vitae -->
      <section class="st-section">
        <h2 class="st-section-title">Curriculum vitae</h2>
        <p class="st-section-desc">
          Usato come promemoria quando invii candidature. Allegalo manualmente all'email.
        </p>

        <div v-if="user.cv_path" class="st-cv-info">
          <svg width="20" height="20" viewBox="0 0 16 16" fill="none">
            <rect x="3" y="1" width="10" height="14" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
            <path d="M5 5h6M5 8h6M5 11h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
          </svg>
          <span class="st-cv-name">{{ cvFilename() }}</span>
          <a href="/settings/cv/download" class="st-cv-btn">Scarica</a>
          <button class="st-cv-btn st-cv-btn--danger" @click="deleteCv">Rimuovi</button>
        </div>

        <template v-else>
          <div
            class="st-upload-zone"
            :class="{ 'is-over': cvDragOver }"
            @dragover.prevent="cvDragOver = true"
            @dragleave="cvDragOver = false"
            @drop.prevent="handleDrop"
            @click="cvInput.click()"
          >
            <svg width="28" height="28" viewBox="0 0 16 16" fill="none">
              <path d="M8 11V3M5 6l3-3 3 3" stroke="currentColor" stroke-width="1.4"
                    stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M2 12h12" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
            </svg>
            <p class="st-upload-label">
              Trascina il CV qui o
              <span class="st-upload-link">clicca per selezionare</span>
            </p>
            <p class="st-upload-hint">PDF, DOC, DOCX · max 5 MB</p>
            <input
              ref="cvInput"
              type="file"
              accept=".pdf,.doc,.docx"
              style="display:none"
              @change="handleCvSelect"
            />
          </div>
          <div v-if="cvForm.processing" class="st-uploading">Caricamento in corso…</div>
          <div v-if="cvForm.errors.cv" class="st-error">{{ cvForm.errors.cv }}</div>
        </template>
      </section>

    </div>
  </div>
</template>
