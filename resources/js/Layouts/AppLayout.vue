<script setup>
import Notification from '@/Components/Notification.vue'
import LoginModal from '@/Components/LoginModal.vue'
</script>

<template>
    <Notification />
    <LoginModal />
    <slot />
</template>
