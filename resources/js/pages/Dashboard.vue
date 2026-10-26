<script setup>
import { Icon } from '@iconify/vue'
import { Link, router } from '@inertiajs/vue3'
import { computed, inject } from 'vue'
import Badge from '@/components/ui/badge/Badge.vue'
import Button from '@/components/ui/button/Button.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import Progress from '@/components/ui/progress/Progress.vue'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
  plan: { type: Object, required: true },
  trialEndsAt: { type: String, default: null },
  limits: { type: Object, required: true },
  usage: { type: Object, required: true },
  devices: { type: Array, default: () => [] },
  downloads: { type: Object, default: () => ({}) },
})

const route = inject('route')

const meters = computed(() => [
  { key: 'messages', label: 'Messages', icon: 'lucide:message-square' },
  { key: 'searches', label: 'Web searches', icon: 'lucide:globe' },
  { key: 'designs', label: 'UI designs', icon: 'lucide:palette' },
  { key: 'voice_minutes', label: 'AI voice minutes', icon: 'lucide:audio-lines' },
].map(m => ({
  ...m,
  used: props.usage[m.key] ?? 0,
  limit: props.limits[m.key] ?? 0,
  pct: Math.min(100, Math.round(((props.usage[m.key] ?? 0) / Math.max(1, props.limits[m.key] ?? 1)) * 100)),
})))

const trialDaysLeft = computed(() => props.trialEndsAt
  ? Math.max(0, Math.ceil((new Date(props.trialEndsAt) - Date.now()) / 86400000))
  : null)

const formatDate = iso => iso ? new Date(iso).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' }) : 'Never'

function removeDevice(device) {
  if (confirm(`Sign out "${device.name}"? Nova on that computer will ask you to sign in again.`))
    router.delete(route('devices.destroy', { token: device.id }), { preserveScroll: true })
}
</script>

<template>
  <AppLayout title="Account">
    <template #header>
      <h2 class="text-xl font-semibold leading-tight">
        Account
      </h2>
    </template>

    <div class="grid gap-4 lg:grid-cols-3">
      <!-- Plan -->
      <Card>
        <CardHeader>
          <CardDescription>Current plan</CardDescription>
          <CardTitle class="flex items-center gap-2 text-2xl">
            {{ plan.name }}
            <Badge v-if="trialDaysLeft !== null" variant="secondary">
              Trial · {{ trialDaysLeft }} day{{ trialDaysLeft === 1 ? '' : 's' }} left
            </Badge>
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-3">
          <p class="text-sm text-muted-foreground">
            <template v-if="trialDaysLeft !== null">
              You have every {{ plan.name }} feature during your free trial.
            </template>
            <template v-else-if="plan.price">
              ${{ plan.price }} / month
            </template>
            <template v-else>
              Upgrade for more messages, searches, designs and voice.
            </template>
          </p>
          <Button as-child class="w-full">
            <Link :href="route('billing')">
              {{ trialDaysLeft === null && plan.price ? 'Manage billing' : 'See plans' }}
            </Link>
          </Button>
        </CardContent>
      </Card>

      <!-- Usage -->
      <Card class="lg:col-span-2">
        <CardHeader>
          <CardDescription>Usage this month</CardDescription>
          <CardTitle class="text-lg">
            Resets on the 1st
          </CardTitle>
        </CardHeader>
        <CardContent class="grid gap-5 sm:grid-cols-2">
          <div v-for="m in meters" :key="m.key" class="space-y-2">
            <div class="flex items-center justify-between text-sm">
              <span class="flex items-center gap-2"><Icon :icon="m.icon" class="size-4 text-muted-foreground" />{{ m.label }}</span>
              <span class="tabular-nums" :class="m.pct >= 90 ? 'text-destructive' : 'text-muted-foreground'">{{ m.used }} / {{ m.limit }}</span>
            </div>
            <Progress :model-value="m.pct" />
          </div>
        </CardContent>
      </Card>

      <!-- Download -->
      <Card>
        <CardHeader>
          <CardDescription>Get Nova</CardDescription>
          <CardTitle class="text-lg">
            Download the app
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-2">
          <Button as="a" :href="downloads.mac || '#'" variant="outline" class="w-full" :disabled="!downloads.mac">
            <Icon icon="lucide:apple" class="size-4" /> macOS
          </Button>
          <Button as="a" :href="downloads.windows || '#'" variant="outline" class="w-full" :disabled="!downloads.windows">
            <Icon icon="lucide:monitor" class="size-4" /> Windows
          </Button>
          <p v-if="!downloads.mac && !downloads.windows" class="text-xs text-muted-foreground">
            Downloads will appear here when the desktop app is released.
          </p>
        </CardContent>
      </Card>

      <!-- Devices -->
      <Card class="lg:col-span-2">
        <CardHeader>
          <CardDescription>Signed-in devices · {{ devices.length }} of {{ limits.devices }}</CardDescription>
          <CardTitle class="text-lg">
            Your computers
          </CardTitle>
        </CardHeader>
        <CardContent>
          <p v-if="!devices.length" class="text-sm text-muted-foreground">
            No devices yet. Install Nova and sign in — it will open this website to confirm.
          </p>
          <ul v-else class="divide-y">
            <li v-for="device in devices" :key="device.id" class="flex items-center justify-between gap-4 py-3">
              <div class="flex items-center gap-3">
                <Icon :icon="device.name.includes('(Mac)') ? 'lucide:laptop' : 'lucide:monitor'" class="size-5 text-muted-foreground" />
                <div>
                  <p class="font-medium">
                    {{ device.name }}
                  </p>
                  <p class="text-xs text-muted-foreground">
                    Signed in {{ formatDate(device.created_at) }} · last used {{ formatDate(device.last_used_at) }}
                  </p>
                </div>
              </div>
              <Button variant="ghost" size="sm" @click="removeDevice(device)">
                Remove
              </Button>
            </li>
          </ul>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
