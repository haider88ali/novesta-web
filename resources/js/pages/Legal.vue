<script setup>
import { useSeoMetaTags } from '@/composables/useSeoMetaTags'
import WebLayout from '@/layouts/WebLayout.vue'

const props = defineProps({
  title: String,
  content: String,
  canLogin: Boolean,
  canRegister: Boolean,
})

useSeoMetaTags({
  title: props.title,
})
</script>

<template>
  <WebLayout :can-login="canLogin" :can-register="canRegister">
    <article class="legal mx-auto w-full max-w-3xl px-6 py-16" v-html="content" />
  </WebLayout>
</template>

<style scoped>
.legal :deep(h1) { font-size: 2.25rem; line-height: 1.2; font-weight: 700; letter-spacing: -0.02em; }
.legal :deep(h1 + p) { margin-top: 0.5rem; color: var(--muted-foreground); }
.legal :deep(h2) { margin-top: 2.5rem; font-size: 1.25rem; font-weight: 600; }
.legal :deep(p), .legal :deep(ul) { margin-top: 0.85rem; line-height: 1.75; color: color-mix(in oklab, var(--foreground) 85%, transparent); }
.legal :deep(ul) { padding-left: 1.25rem; list-style: disc; }
.legal :deep(li + li) { margin-top: 0.4rem; }
.legal :deep(strong) { color: var(--foreground); font-weight: 600; }
.legal :deep(a) { color: var(--primary); text-decoration: underline; text-underline-offset: 3px; }
.legal :deep(code) { font-size: 0.875em; padding: 0.1rem 0.3rem; border-radius: 0.25rem; background: var(--muted); }
</style>
