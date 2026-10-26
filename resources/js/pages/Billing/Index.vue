<script setup>
import { Icon } from '@iconify/vue'
import { Deferred, router } from '@inertiajs/vue3'
import { computed, inject } from 'vue'
import Alert from '@/components/ui/alert/Alert.vue'
import AlertDescription from '@/components/ui/alert/AlertDescription.vue'
import AlertTitle from '@/components/ui/alert/AlertTitle.vue'
import Badge from '@/components/ui/badge/Badge.vue'
import Button from '@/components/ui/button/Button.vue'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
  currentPlan: { type: String, required: true },
  trialEndsAt: { type: String, default: null },
  paddleReady: { type: Boolean, default: false },
  subscription: { type: Object, default: null },
  plans: { type: Array, required: true },
  transactions: { type: Array, default: () => [] },
})

const route = inject('route')
const formatDate = iso => iso ? new Date(iso).toLocaleDateString(undefined, { day: 'numeric', month: 'long', year: 'numeric' }) : ''
const isActive = computed(() => props.subscription && !props.subscription.canceled)

function checkout(plan) {
  if (!window.Paddle) {
    alert('Payments are not available right now. Please try again later.')
    return
  }
  // Paddle opens its secure overlay; Cashier records the subscription from Paddle's webhook
  window.Paddle.Checkout.open(plan.checkout)
}
function switchTo(plan) {
  if (confirm(`Switch to ${plan.name} ($${plan.price}/month)? Paddle charges or credits the difference.`))
    router.post(route('billing.swap'), { plan: plan.key }, { preserveScroll: true })
}
function cancel() {
  if (confirm('Cancel your subscription? You keep your plan until the end of the paid period.'))
    router.post(route('billing.cancel'), {}, { preserveScroll: true })
}
</script>

<template>
  <AppLayout title="Plans & Billing">
    <template #header>
      <h2 class="text-xl font-semibold leading-tight">
        Plans & Billing
      </h2>
    </template>

    <div class="space-y-6">
      <Alert v-if="!paddleReady">
        <Icon icon="lucide:info" class="size-4" />
        <AlertTitle>Payments are not set up yet</AlertTitle>
        <AlertDescription>
          Add PADDLE_API_KEY, PADDLE_CLIENT_SIDE_TOKEN and the plan price ids to .env to enable checkout.
        </AlertDescription>
      </Alert>

      <Alert v-if="subscription?.past_due" variant="destructive">
        <Icon icon="lucide:triangle-alert" class="size-4" />
        <AlertTitle>Your last payment failed</AlertTitle>
        <AlertDescription class="flex items-center justify-between gap-4">
          Update your card to keep your plan.
          <Button as="a" size="sm" :href="route('billing.payment-method')">
            Update card
          </Button>
        </AlertDescription>
      </Alert>

      <Alert v-if="subscription?.on_grace_period">
        <Icon icon="lucide:calendar-x" class="size-4" />
        <AlertTitle>Subscription cancelled</AlertTitle>
        <AlertDescription class="flex items-center justify-between gap-4">
          Your plan stays active until {{ formatDate(subscription.ends_at) }}.
          <Button size="sm" @click="router.post(route('billing.resume'), {}, { preserveScroll: true })">
            Resume
          </Button>
        </AlertDescription>
      </Alert>

      <div class="grid gap-4 md:grid-cols-3">
        <Card v-for="plan in plans" :key="plan.key" :class="plan.key === currentPlan ? 'border-primary' : ''" class="flex flex-col">
          <CardHeader>
            <CardTitle class="flex items-center justify-between">
              {{ plan.name }}
              <Badge v-if="plan.key === currentPlan">
                {{ trialEndsAt ? 'Trial' : 'Current' }}
              </Badge>
            </CardTitle>
            <CardDescription>{{ plan.description }}</CardDescription>
            <p class="pt-2 text-3xl font-bold">
              ${{ plan.price }}<span class="text-base font-normal text-muted-foreground">/month</span>
            </p>
          </CardHeader>
          <CardContent class="flex-1">
            <ul class="space-y-2 text-sm">
              <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-2">
                <Icon icon="lucide:check" class="mt-0.5 size-4 shrink-0 text-primary" /> {{ feature }}
              </li>
            </ul>
          </CardContent>
          <CardFooter>
            <Button v-if="plan.checkout" class="w-full" @click="checkout(plan)">
              {{ trialEndsAt ? 'Subscribe' : 'Upgrade' }} to {{ plan.name }}
            </Button>
            <Button v-else-if="plan.can_switch" variant="outline" class="w-full" @click="switchTo(plan)">
              Switch to {{ plan.name }}
            </Button>
            <Button v-else-if="plan.key === currentPlan" variant="secondary" class="w-full" disabled>
              Your plan
            </Button>
          </CardFooter>
        </Card>
      </div>

      <div v-if="isActive" class="flex flex-wrap gap-2">
        <Button as="a" variant="outline" :href="route('billing.payment-method')">
          <Icon icon="lucide:credit-card" class="size-4" /> Update card
        </Button>
        <Button variant="ghost" class="text-destructive" @click="cancel">
          Cancel subscription
        </Button>
      </div>

      <Deferred data="transactions">
        <template #fallback>
          <Icon icon="lucide:loader" class="animate-spin" />
        </template>
        <Card v-if="transactions.length">
          <CardHeader>
            <CardTitle class="text-lg">
              Invoices
            </CardTitle>
          </CardHeader>
          <CardContent>
            <ul class="divide-y text-sm">
              <li v-for="t in transactions" :key="t.id" class="flex items-center justify-between py-2">
                <span>{{ formatDate(t.billed_at) }}</span>
                <span class="tabular-nums">{{ t.total }}</span>
                <Badge variant="outline">
                  {{ t.status }}
                </Badge>
                <a :href="route('billing.invoice', { transaction: t.id })" class="text-primary underline-offset-4 hover:underline">PDF</a>
              </li>
            </ul>
          </CardContent>
        </Card>
      </Deferred>

      <p class="text-sm text-muted-foreground">
        🔒 Payments are handled securely by Paddle, which also takes care of sales tax and VAT.
      </p>
    </div>
  </AppLayout>
</template>
