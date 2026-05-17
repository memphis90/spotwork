<script setup>
import { usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

const flash = computed(() => usePage().props.flash)
const visible = ref(false)

watch(flash, (val) => {
    if (val.success || val.error) {
        visible.value = true
        setTimeout(() => visible.value = false, 3000)
    }
}, { immediate: true })


</script>

<template>
    <div v-if="visible">
        <div v-if="flash.success" class="notification success">{{ flash.success }}</div>
        <div v-if="flash.error"   class="notification error">{{ flash.error }}</div>
    </div>
</template>
