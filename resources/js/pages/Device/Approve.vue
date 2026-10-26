<script setup>
import { Icon } from '@iconify/vue'
import { Link, useForm } from '@inertiajs/vue3'
import { inject } from 'vue'
import InputError from '@/components/InputError.vue'
import Button from '@/components/ui/button/Button.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import Input from '@/components/ui/input/Input.vue'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
  code: { type: String, default: '' },
  device: { type: Object, default: null },
  approved: { type: Boolean, default: false },
})

const route = inject('route')
const form = useForm({ code: props.code })

function approve() {
  form.post(route('device.approve'))
}
</script>

<template>
  <AppLayout title="Sign in Nova">
    <template #header>
      <h2 class="text-xl font-semibold leading-tight">
        Sign in to Nova
      </h2>
    </template>

    <div class="mx-auto max-w-md">
      <Card v-if="approved">
        <CardHeader class="items-center text-center">
          <Icon icon="lucide:circle-check" class="size-12 text-green-500" />
          <CardTitle>You're signed in</CardTitle>
          <CardDescription>Go back to Nova — it will continue in a few seconds. You can close this tab.</CardDescription>
        </CardHeader>
        <CardContent>
          <Button as-child variant="outline" class="w-full">
            <Link :href="route('dashboard')">
              Go to your account
            </Link>
          </Button>
        </CardContent>
      </Card>

      <Card v-else>
        <CardHeader>
          <CardTitle>Approve this computer</CardTitle>
          <CardDescription>
            Check that the code matches the one shown in the Nova app, then approve. Only approve codes you started yourself.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form class="space-y-4" @submit.prevent="approve">
            <div v-if="device" class="flex items-center gap-3 rounded-md border p-3">
              <Icon :icon="device.platform === 'mac' ? 'lucide:laptop' : 'lucide:monitor'" class="size-6 text-muted-foreground" />
              <div>
                <p class="font-medium">
                  {{ device.name }}
                </p>
                <p class="text-xs text-muted-foreground">
                  {{ device.platform === 'mac' ? 'macOS' : 'Windows' }}
                </p>
              </div>
            </div>

            <div class="space-y-2">
              <label for="code" class="text-sm font-medium">Code from the app</label>
              <Input
                id="code"
                v-model="form.code"
                placeholder="XXXX-XXXX"
                maxlength="9"
                autocomplete="off"
                class="text-center font-mono text-2xl uppercase tracking-[0.3em]"
              />
              <InputError :message="form.errors.code" />
            </div>

            <Button type="submit" class="w-full" :disabled="form.processing || form.code.length < 9">
              Approve
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
