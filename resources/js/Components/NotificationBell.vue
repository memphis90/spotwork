<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import axios from 'axios'

const page        = usePage()
const open        = ref(false)
const notifications = ref([])
const loading     = ref(false)
const bellRef     = ref(null)

const unreadCount = () => page.props.unread_notifications_count ?? 0

async function toggle() {
  if (!open.value) {
    open.value = true
    loading.value = true
    try {
      const { data } = await axios.get('/notifications')
      notifications.value = data
      if (unreadCount() > 0) {
        await axios.post('/notifications/read-all')
        router.reload({ only: ['unread_notifications_count'] })
      }
    } finally {
      loading.value = false
    }
  } else {
    open.value = false
  }
}

function onDoc(e) {
  if (bellRef.value && !bellRef.value.contains(e.target)) open.value = false
}
onMounted(() => document.addEventListener('mousedown', onDoc))
onBeforeUnmount(() => document.removeEventListener('mousedown', onDoc))

function timeAgo(dateStr) {
  const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000)
  if (diff < 60)    return 'ora'
  if (diff < 3600)  return `${Math.floor(diff / 60)}m fa`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h fa`
  return `${Math.floor(diff / 86400)}g fa`
}
</script>

<template>
  <div class="sw-bell" ref="bellRef">
    <button class="sw-icon-btn sw-bell-btn" @click="toggle" title="Notifiche" :aria-label="`Notifiche${unreadCount() ? `, ${unreadCount()} non lette` : ''}`">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
        <path d="M8 2a5 5 0 0 1 5 5v2.5l1.5 2H1.5L3 9.5V7a5 5 0 0 1 5-5Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
        <path d="M6.5 13.5a1.5 1.5 0 0 0 3 0" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
      </svg>
      <span v-if="unreadCount() > 0" class="sw-bell-badge" aria-hidden="true">{{ unreadCount() > 9 ? '9+' : unreadCount() }}</span>
    </button>

    <div v-if="open" class="sw-notif-dropdown">
      <div class="sw-notif-header">Notifiche</div>
      <div v-if="loading" class="sw-notif-empty">Caricamento…</div>
      <div v-else-if="notifications.length === 0" class="sw-notif-empty">Nessuna notifica</div>
      <ul v-else class="sw-notif-list">
        <li v-for="n in notifications" :key="n.id" :class="['sw-notif-item', !n.read_at && 'sw-notif-item--unread']">
          <span class="sw-notif-text">{{ n.data.message }}</span>
          <span class="sw-notif-time">{{ timeAgo(n.created_at) }}</span>
        </li>
      </ul>
    </div>
  </div>
</template>
