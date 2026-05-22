<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useForm, usePage, router } from '@inertiajs/vue3'

defineProps({ open: { type: Boolean, required: true } })
const emit = defineEmits(['close'])

const page       = usePage()
const user       = () => page.props.auth.user
const activeTab  = ref('candidatura')
const cvDragOver = ref(false)
const cvInput    = ref(null)

const messageForm = useForm({ message: '' })
const profileForm = useForm({ name: '', email: '' })

function syncForms() {
  messageForm.message = user()?.application_message || ''
  profileForm.name    = user()?.name  || ''
  profileForm.email   = user()?.email || ''
}

function saveMessage()  { messageForm.patch('/settings/message') }
function saveProfile()  { profileForm.patch('/profile') }

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
  const form = useForm({ cv: file })
  form.post('/settings/cv', { forceFormData: true })
}
function deleteCv() {
  if (!confirm('Rimuovere il CV salvato?')) return
  router.delete('/settings/cv')
}
function cvFilename() {
  return user()?.cv_path ? user().cv_path.split('/').pop() : null
}
function logout() {
  router.post('/logout')
}

function onKey(e) {
  if (e.key === 'Escape') emit('close')
}
onMounted(() => {
  document.addEventListener('keydown', onKey)
  syncForms()
})
onBeforeUnmount(() => document.removeEventListener('keydown', onKey))
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="sw-smod-overlay" @mousedown.self="$emit('close')" role="dialog" aria-modal="true" aria-label="Impostazioni">

      <div class="sw-smod">
        <div class="sw-smod-head">
          <div class="sw-smod-tabs">
            <button :class="['sw-smod-tab', activeTab === 'candidatura' && 'is-on']" @click="activeTab = 'candidatura'">
              Candidatura
            </button>
            <button :class="['sw-smod-tab', activeTab === 'account' && 'is-on']" @click="activeTab = 'account'">
              Account
            </button>
          </div>
          <button class="sw-smod-close" @click="$emit('close')" aria-label="Chiudi">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M3 3l10 10M13 3L3 13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
          </button>
        </div>

        <div class="sw-smod-body">

          <!-- TAB: Candidatura -->
          <template v-if="activeTab === 'candidatura'">
            <section class="st-section">
              <h2 class="st-section-title">Messaggio di candidatura</h2>
              <p class="st-section-desc">Testo precompilato quando invii una candidatura spontanea.</p>
              <textarea class="st-textarea" v-model="messageForm.message"
                placeholder="Gentile team,&#10;&#10;Vi scrivo per esprimere il mio interesse..." rows="6" />
              <div v-if="messageForm.errors.message" class="st-error">{{ messageForm.errors.message }}</div>
              <div class="st-section-foot">
                <span v-if="messageForm.wasSuccessful" class="st-feedback-ok">Salvato ✓</span>
                <button class="sw-btn-primary" @click="saveMessage" :disabled="messageForm.processing">Salva</button>
              </div>
            </section>

            <section class="st-section">
              <h2 class="st-section-title">Curriculum vitae</h2>
              <p class="st-section-desc">Allegato di riferimento per le candidature spontanee.</p>

              <div v-if="user()?.cv_path" class="st-cv-info">
                <svg width="20" height="20" viewBox="0 0 16 16" fill="none">
                  <rect x="3" y="1" width="10" height="14" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
                  <path d="M5 5h6M5 8h6M5 11h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
                </svg>
                <span class="st-cv-name">{{ cvFilename() }}</span>
                <a href="/settings/cv/download" class="st-cv-btn">Scarica</a>
                <button class="st-cv-btn st-cv-btn--danger" @click="deleteCv">Rimuovi</button>
              </div>

              <template v-else>
                <div class="st-upload-zone" :class="{ 'is-over': cvDragOver }"
                     @dragover.prevent="cvDragOver = true" @dragleave="cvDragOver = false"
                     @drop.prevent="handleDrop" @click="cvInput.click()">
                  <svg width="28" height="28" viewBox="0 0 16 16" fill="none">
                    <path d="M8 11V3M5 6l3-3 3 3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 12h12" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                  </svg>
                  <p class="st-upload-label">Trascina il CV qui o <span class="st-upload-link">clicca per selezionare</span></p>
                  <p class="st-upload-hint">PDF, DOC, DOCX · max 5 MB</p>
                  <input ref="cvInput" type="file" accept=".pdf,.doc,.docx" style="display:none" @change="handleCvSelect" />
                </div>
              </template>
            </section>
          </template>

          <!-- TAB: Account -->
          <template v-if="activeTab === 'account'">
            <section class="st-section">
              <h2 class="st-section-title">Informazioni account</h2>
              <div class="sw-smod-fields">
                <label class="sw-smod-field">
                  <span class="sw-smod-label">Nome</span>
                  <input class="sw-smod-input" type="text" v-model="profileForm.name" autocomplete="name" />
                  <span v-if="profileForm.errors.name" class="st-error">{{ profileForm.errors.name }}</span>
                </label>
                <label class="sw-smod-field">
                  <span class="sw-smod-label">Email</span>
                  <input class="sw-smod-input" type="email" v-model="profileForm.email" autocomplete="email" />
                  <span v-if="profileForm.errors.email" class="st-error">{{ profileForm.errors.email }}</span>
                </label>
              </div>
              <div class="st-section-foot">
                <span v-if="profileForm.wasSuccessful" class="st-feedback-ok">Salvato ✓</span>
                <button class="sw-btn-primary" @click="saveProfile" :disabled="profileForm.processing">Salva</button>
              </div>
            </section>

            <section class="st-section">
              <h2 class="st-section-title">Password</h2>
              <p class="st-section-desc">
                Per cambiare la password vai alla
                <a href="/profile" class="sw-smod-link" @click="$emit('close')">pagina profilo completa</a>.
              </p>
            </section>

            <section class="st-section st-section--danger">
              <button class="sw-smod-logout" @click="logout">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                  <path d="M10 3h3v10h-3M7 11l4-4-4-4M11 8H3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Esci dall'account
              </button>
            </section>
          </template>

        </div>
      </div>

    </div>
  </Teleport>
</template>
