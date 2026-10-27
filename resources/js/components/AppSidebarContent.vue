<script setup>
import { Icon } from '@iconify/vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useColorMode } from '@vueuse/core'
import { computed, inject } from 'vue'
import {
  SidebarContent,
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/components/ui/sidebar'

const route = inject('route')
const mode = useColorMode({
  attribute: 'class',
  modes: { light: '', dark: 'dark' },
})

const navigationConfig = [
  {
    label: 'Platform',
    items: [
      {
        name: 'Account',
        icon: 'lucide:layout-dashboard',
        route: 'dashboard',
      },
      {
        name: 'Plans & Billing',
        icon: 'lucide:credit-card',
        route: 'billing',
      },
      {
        name: 'Settings',
        icon: 'lucide:settings',
        route: 'profile.show',
      },
    ],
  },
  {
    label: null,
    class: 'mt-auto',
    items: [
      {
        name: 'Support',
        icon: 'lucide:life-buoy',
        href: `mailto:${usePage().props.supportEmail}`,
        external: true,
      },
    ],
  },
]

const isDarkMode = computed(() => mode.value === 'dark')

function renderLink(item) {
  if (item.external) {
    return {
      is: 'a',
      href: item.href || route(item.route),
      target: '_blank',
    }
  }
  return {
    is: Link,
    href: route(item.route),
  }
}
</script>

<template>
  <SidebarContent>
    <SidebarGroup
      v-for="(group, index) in navigationConfig"
      :key="index"
      :class="group.class"
    >
      <SidebarGroupLabel v-if="group.label">
        {{ group.label }}
      </SidebarGroupLabel>
      <SidebarMenu>
        <SidebarMenuItem
          v-for="item in group.items"
          :key="item.name"
          :class="{
            'font-semibold text-primary bg-secondary rounded':
              !item.external && route().current(item.route),
          }"
        >
          <SidebarMenuButton as-child>
            <component
              v-bind="renderLink(item)"
              :is="item.external ? 'a' : Link"
              prefetch
            >
              <Icon :icon="item.icon" />
              {{ item.name }}
            </component>
          </SidebarMenuButton>
        </SidebarMenuItem>
        <SidebarMenuItem v-if="index === navigationConfig.length - 1">
          <SidebarMenuButton
            @click="mode = isDarkMode ? 'light' : 'dark'"
          >
            <Icon
              :icon="isDarkMode ? 'lucide:moon' : 'lucide:sun'"
            />
            {{ isDarkMode ? "Dark" : "Light" }} Mode
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarGroup>
  </SidebarContent>
</template>
