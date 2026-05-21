<script setup>
import Notification from '@/Components/Notification.vue'
import LoginModal from '@/Components/LoginModal.vue'
import CookieBanner from '@/Components/CookieBanner.vue'
</script>

<template>
    <Notification />
    <LoginModal />
    <slot />
    <CookieBanner />
</template>
