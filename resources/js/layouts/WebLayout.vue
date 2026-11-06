<script setup>
import { Icon } from '@iconify/vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useColorMode } from '@vueuse/core'
import { computed, ref } from 'vue'
import Button from '@/components/ui/button/Button.vue'

defineProps({
  canLogin: {
    type: Boolean,
  },
  canRegister: {
    type: Boolean,
  },
})

const mode = useColorMode({
  attribute: 'class',
  modes: {
    light: '',
    dark: 'dark',
  },
})

const navLinks = [
  { label: 'Features', href: '/#features' },
  { label: 'Pricing', href: '/#pricing' },
  { label: 'Download', href: '/#download' },
  { label: 'FAQ', href: '/#faq' },
]

const supportEmail = computed(() => usePage().props.supportEmail)
const year = new Date().getFullYear()

const isMenuOpen = ref(false)

function toggleMenu() {
  isMenuOpen.value = !isMenuOpen.value
}
</script>

<template>
  <div class="min-h-screen">
    <header
      class="sticky top-0 z-50 w-full border-b bg-background/95 backdrop-blur-sm supports-backdrop-filter:bg-background/60"
    >
      <div class="container mx-auto flex h-16 items-center justify-between px-4">
        <div class="flex items-center">
          <a class="flex items-center gap-2" href="/" aria-label="Novesta home">
            <span class="nova-mark" aria-hidden="true" />
            <span class="font-bold tracking-[0.25em]">NOVESTA</span>
          </a>
          <nav class="ml-8 hidden items-center space-x-6 text-sm font-medium md:flex">
            <a
              v-for="link in navLinks"
              :key="link.href"
              :href="link.href"
              class="text-foreground/60 transition-colors hover:text-foreground"
            >
              {{ link.label }}
            </a>
          </nav>
        </div>
        <div class="flex items-center space-x-2">
          <div class="hidden space-x-2 sm:flex">
            <template v-if="!$page.props.auth.user">
              <Button v-if="canLogin" variant="ghost" :as="Link" href="/login" prefetch="mount">
                Log in
              </Button>
              <Button v-if="canRegister" :as="Link" href="/register" prefetch="mount">
                Start free trial
              </Button>
            </template>
            <Button v-else variant="outline" :as="Link" href="/dashboard" prefetch="mount">
              Your account
            </Button>
          </div>
          <Button
            variant="ghost"
            size="icon"
            aria-label="Toggle theme"
            @click="mode = mode === 'dark' ? 'light' : 'dark'"
          >
            <Icon class="h-5 w-5 text-muted-foreground" :icon="mode === 'dark' ? 'lucide:sun' : 'lucide:moon'" />
          </Button>
          <Button class="md:hidden" variant="ghost" size="icon" aria-label="Toggle menu" @click="toggleMenu">
            <Icon :icon="isMenuOpen ? 'lucide:x' : 'lucide:menu'" class="h-6 w-6" aria-hidden="true" />
          </Button>
        </div>
      </div>
      <!-- Mobile menu -->
      <div v-show="isMenuOpen" class="border-t md:hidden">
        <nav class="flex flex-col space-y-4 p-4">
          <a
            v-for="link in navLinks"
            :key="link.href"
            :href="link.href"
            class="text-sm font-medium transition-colors hover:text-foreground/80"
            @click="toggleMenu"
          >
            {{ link.label }}
          </a>
          <template v-if="!$page.props.auth.user">
            <Button v-if="canLogin" variant="outline" :as="Link" href="/login" class="w-full" @click="toggleMenu">
              Log in
            </Button>
            <Button v-if="canRegister" :as="Link" href="/register" class="w-full" @click="toggleMenu">
              Start free trial
            </Button>
          </template>
          <Button v-else variant="outline" :as="Link" href="/dashboard" class="w-full" @click="toggleMenu">
            Your account
          </Button>
        </nav>
      </div>
    </header>

    <slot />

    <!-- Footer -->
    <footer class="border-t">
      <div class="container mx-auto flex flex-col items-center justify-between gap-4 px-4 py-8 text-sm text-muted-foreground sm:flex-row">
        <p class="flex items-center gap-2">
          <span class="nova-mark nova-mark--small" aria-hidden="true" />
          © {{ year }} Novesta. All rights reserved.
        </p>
        <nav class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2">
          <a href="/terms-of-service" class="hover:text-foreground">Terms</a>
          <a href="/privacy-policy" class="hover:text-foreground">Privacy</a>
          <a href="/refund-policy" class="hover:text-foreground">Refunds</a>
          <a :href="`mailto:${supportEmail}`" class="hover:text-foreground">Support</a>
        </nav>
      </div>
    </footer>
  </div>
</template>

<style scoped>
/* Small glowing orb used as the Novesta logo mark */
.nova-mark {
  width: 22px; height: 22px; border-radius: 9999px; flex-shrink: 0;
  background: radial-gradient(circle at 40% 35%, #fff 0%, #67e8f9 25%, #06b6d4 55%, #164e63 90%);
  box-shadow: 0 0 0 2px rgb(34 211 238 / 0.35), 0 0 14px rgb(34 211 238 / 0.6);
}
.nova-mark--small { width: 16px; height: 16px; }
</style>
