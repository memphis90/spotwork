<script setup>
import { useLoginModal } from '@/Composables/useLoginModal'
import { useForm } from '@inertiajs/vue3'
import { ref, onMounted, onBeforeUnmount } from 'vue'

const { loginModalOpen, closeLoginModal } = useLoginModal()

const view = ref('login')

const loginForm = useForm({ email: '', password: '', remember: false, _redirect: '' })
const registerForm = useForm({ name: '', email: '', password: '', password_confirmation: '', _redirect: '' })

function switchTo(v) {
  view.value = v
  loginForm.clearErrors()
  registerForm.clearErrors()
}

function submitLogin() {
  loginForm._redirect = window.location.pathname + window.location.search
  loginForm.post('/login', {
    onSuccess: () => { loginForm.reset(); closeLoginModal() },
    onFinish:  () => loginForm.reset('password'),
  })
}

function submitRegister() {
  registerForm._redirect = window.location.pathname + window.location.search
  registerForm.post('/register', {
    onSuccess: () => { registerForm.reset(); closeLoginModal() },
    onFinish:  () => registerForm.reset('password', 'password_confirmation'),
  })
}

function onKey(e) { if (e.key === 'Escape') closeLoginModal() }
onMounted(() => document.addEventListener('keydown', onKey))
onBeforeUnmount(() => document.removeEventListener('keydown', onKey))
</script>

<template>
  <Teleport to="body">
    <Transition name="lm">
      <div v-if="loginModalOpen" class="lm-backdrop" @mousedown.self="closeLoginModal">
        <div class="lm-card" role="dialog" aria-modal="true"
             :aria-label="view === 'login' ? 'Accedi' : 'Registrati'">

          <button class="lm-close" @click="closeLoginModal" aria-label="Chiudi">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
              <path d="M4 4l8 8M12 4l-8 8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
          </button>

          <div class="lm-header">
            <img src="@img/sw_full.jpg" alt="SpotWork" class="lm-logo" />
            <p class="lm-sub">
              {{ view === 'login' ? 'Accedi per salvare aziende e attivare alert' : 'Crea un account gratuito' }}
            </p>
          </div>

          <!-- tabs -->
          <div class="lm-tabs">
            <button :class="['lm-tab', view === 'login' && 'is-on']" @click="switchTo('login')">Accedi</button>
            <button :class="['lm-tab', view === 'register' && 'is-on']" @click="switchTo('register')">Registrati</button>
          </div>

          <!-- login form -->
          <Transition name="lm-view" mode="out-in">
            <form v-if="view === 'login'" key="login" @submit.prevent="submitLogin" class="lm-form" novalidate>
              <div class="lm-field">
                <label class="lm-label" for="lm-email">Email</label>
                <input id="lm-email" type="email" class="lm-input" autocomplete="username"
                       v-model="loginForm.email" required autofocus
                       :class="{ 'lm-input-err': loginForm.errors.email }" />
                <span v-if="loginForm.errors.email" class="lm-err">{{ loginForm.errors.email }}</span>
              </div>

              <div class="lm-field">
                <label class="lm-label" for="lm-password">Password</label>
                <input id="lm-password" type="password" class="lm-input" autocomplete="current-password"
                       v-model="loginForm.password" required
                       :class="{ 'lm-input-err': loginForm.errors.password }" />
                <span v-if="loginForm.errors.password" class="lm-err">{{ loginForm.errors.password }}</span>
              </div>

              <div class="lm-row">
                <label class="lm-check">
                  <input type="checkbox" v-model="loginForm.remember" />
                  <span>Ricordami</span>
                </label>
                <a href="/forgot-password" class="lm-link">Password dimenticata?</a>
              </div>

              <button type="submit" class="lm-submit" :disabled="loginForm.processing">
                <span v-if="loginForm.processing" class="sw-spin" aria-hidden="true" />
                <span>{{ loginForm.processing ? 'Accesso…' : 'Accedi' }}</span>
              </button>
            </form>

            <!-- register form -->
            <form v-else key="register" @submit.prevent="submitRegister" class="lm-form" novalidate>
              <div class="lm-field">
                <label class="lm-label" for="lm-name">Nome</label>
                <input id="lm-name" type="text" class="lm-input" autocomplete="name"
                       v-model="registerForm.name" required autofocus
                       :class="{ 'lm-input-err': registerForm.errors.name }" />
                <span v-if="registerForm.errors.name" class="lm-err">{{ registerForm.errors.name }}</span>
              </div>

              <div class="lm-field">
                <label class="lm-label" for="lm-reg-email">Email</label>
                <input id="lm-reg-email" type="email" class="lm-input" autocomplete="username"
                       v-model="registerForm.email" required
                       :class="{ 'lm-input-err': registerForm.errors.email }" />
                <span v-if="registerForm.errors.email" class="lm-err">{{ registerForm.errors.email }}</span>
              </div>

              <div class="lm-field">
                <label class="lm-label" for="lm-reg-password">Password</label>
                <input id="lm-reg-password" type="password" class="lm-input" autocomplete="new-password"
                       v-model="registerForm.password" required
                       :class="{ 'lm-input-err': registerForm.errors.password }" />
                <span v-if="registerForm.errors.password" class="lm-err">{{ registerForm.errors.password }}</span>
              </div>

              <div class="lm-field">
                <label class="lm-label" for="lm-reg-confirm">Conferma password</label>
                <input id="lm-reg-confirm" type="password" class="lm-input" autocomplete="new-password"
                       v-model="registerForm.password_confirmation" required
                       :class="{ 'lm-input-err': registerForm.errors.password_confirmation }" />
                <span v-if="registerForm.errors.password_confirmation" class="lm-err">{{ registerForm.errors.password_confirmation }}</span>
              </div>

              <button type="submit" class="lm-submit" :disabled="registerForm.processing">
                <span v-if="registerForm.processing" class="sw-spin" aria-hidden="true" />
                <span>{{ registerForm.processing ? 'Registrazione…' : 'Crea account' }}</span>
              </button>
            </form>
          </Transition>

        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.lm-backdrop {
  position: fixed; inset: 0; z-index: 2000;
  background: rgba(14, 16, 20, .55);
  display: flex; align-items: center; justify-content: center;
  padding: 16px;
  backdrop-filter: blur(2px);
}

.lm-card {
  position: relative;
  width: 100%; max-width: 400px;
  background: var(--sw-surface);
  border-radius: var(--sw-radius-lg);
  box-shadow: var(--sw-shadow-2);
  padding: 32px 28px 28px;
}

.lm-close {
  position: absolute; top: 16px; right: 16px;
  width: 28px; height: 28px; border-radius: 50%;
  border: 1px solid var(--sw-line);
  background: var(--sw-surface); color: var(--sw-muted);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: color .15s, background .15s;
}
.lm-close:hover { color: var(--sw-accent); background: var(--sw-bg); }
.lm-close:active { color: var(--sw-teal); }

.lm-header { text-align: center; margin-bottom: 20px; }
.lm-logo { display: block; height: 32px; object-fit: contain; margin: 0 auto 10px; }
.lm-sub { margin: 0; font-size: 13px; color: var(--sw-muted); line-height: 1.4; }

/* tabs */
.lm-tabs {
  display: flex;
  border: 1px solid var(--sw-line);
  border-radius: 999px;
  padding: 3px;
  margin-bottom: 20px;
  gap: 2px;
}
.lm-tab {
  flex: 1; border: none; border-radius: 999px;
  padding: 8px 0;
  font: 500 13px/1 "Manrope", sans-serif;
  color: var(--sw-muted); background: transparent;
  cursor: pointer; transition: color .15s, background .15s;
}
.lm-tab:hover:not(.is-on) { color: var(--sw-accent); }
.lm-tab:active:not(.is-on) { color: var(--sw-teal); }
.lm-tab.is-on { background: var(--sw-accent); color: #fff; }
.lm-tab.is-on:active { background: var(--sw-teal); }

.lm-form { display: flex; flex-direction: column; gap: 14px; }

.lm-field { display: flex; flex-direction: column; gap: 5px; }

.lm-label {
  font: 500 12px/1 "Manrope", sans-serif;
  color: var(--sw-muted); letter-spacing: 0.04em; text-transform: uppercase;
}

.lm-input {
  height: 40px; padding: 0 12px;
  border: 1.5px solid var(--sw-line);
  border-radius: var(--sw-radius-sm);
  background: var(--sw-bg);
  font: 14px/1 "Manrope", sans-serif; color: var(--sw-ink);
  outline: none; transition: border-color .15s;
}
.lm-input:hover { border-color: var(--sw-lineStrong); }
.lm-input:focus-visible { border-color: var(--sw-accent); }
.lm-input:active { border-color: var(--sw-teal); }
.lm-input-err { border-color: #dc2626 !important; }

.lm-err { font-size: 12px; color: #dc2626; }

.lm-row { display: flex; align-items: center; justify-content: space-between; }

.lm-check {
  display: flex; align-items: center; gap: 7px;
  font-size: 13px; color: var(--sw-muted); cursor: pointer;
}
.lm-check input { accent-color: var(--sw-accent); cursor: pointer; }

.lm-link { font-size: 12px; color: var(--sw-accent); text-decoration: none; transition: color .15s; }
.lm-link:hover { color: var(--sw-teal); }

.lm-submit {
  height: 42px; border: none; border-radius: 999px; margin-top: 2px;
  background: var(--sw-accent); color: #fff;
  font: 600 14px/1 "Manrope", sans-serif;
  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
  transition: opacity .15s, background .15s;
}
.lm-submit:hover { opacity: .9; }
.lm-submit:active { background: var(--sw-teal); }
.lm-submit:disabled { opacity: .7; cursor: default; }

/* modal entrance */
.lm-enter-active, .lm-leave-active { transition: opacity .2s; }
.lm-enter-active .lm-card, .lm-leave-active .lm-card { transition: transform .2s, opacity .2s; }
.lm-enter-from, .lm-leave-to { opacity: 0; }
.lm-enter-from .lm-card, .lm-leave-to .lm-card { transform: translateY(12px) scale(.97); opacity: 0; }

/* view switch */
.lm-view-enter-active, .lm-view-leave-active { transition: opacity .15s, transform .15s; }
.lm-view-enter-from { opacity: 0; transform: translateX(10px); }
.lm-view-leave-to { opacity: 0; transform: translateX(-10px); }
</style>
