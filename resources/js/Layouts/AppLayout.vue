<template>
  <div class="flex h-screen overflow-hidden bg-background">
    <!-- Sidebar -->
    <aside
      class="flex flex-col w-64 border-r bg-sidebar text-sidebar-foreground shrink-0"
    >
      <!-- Logo / App name -->
      <div class="flex items-center gap-2.5 px-6 border-b border-sidebar-border" style="height: var(--density-logo-h)">
        <div class="w-7 h-7 rounded-md bg-sidebar-primary flex items-center justify-center shrink-0">
          <span class="text-sidebar-primary-foreground font-bold text-base leading-none select-none">Λ</span>
        </div>
        <span class="font-semibold text-sm tracking-tight">Lambda CMS</span>
      </div>

      <!-- Navigation -->
      <ScrollArea element="nav" class="flex-1 px-3 py-4 space-y-0.5">
        <p class="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-sidebar-foreground/40">Overview</p>
        <SidebarLink :href="route('dashboard')" :active="currentRoute === 'dashboard'">
          <template #icon><LayoutDashboard class="w-4 h-4" /></template>
          Dashboard
        </SidebarLink>

        <p class="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-sidebar-foreground/40" style="margin-top: var(--density-sidebar-section-mt)">Content</p>
        <SidebarLink :href="route('posts.index')" :active="currentRoute?.startsWith('posts.')">
          <template #icon><FileText class="w-4 h-4" /></template>
          Posts
        </SidebarLink>

        <SidebarLink :href="route('calendar')" :active="currentRoute === 'calendar'">
          <template #icon>
            <Calendar class="w-4 h-4" />
          </template>
          Calendar
        </SidebarLink>

        <SidebarLink
          v-if="user.role === 'administrator'"
          :href="route('pages.index')"
          :active="currentRoute?.startsWith('pages.')"
        >
          <template #icon><File class="w-4 h-4" /></template>
          Pages
        </SidebarLink>

        <SidebarLink
          v-if="user.role === 'administrator'"
          :href="route('templates.index')"
          :active="currentRoute?.startsWith('templates.')"
        >
          <template #icon>
            <LayoutTemplate class="w-4 h-4" />
          </template>
          Templates
        </SidebarLink>

        <SidebarLink :href="route('categories.index')" :active="currentRoute?.startsWith('categories.')">
          <template #icon><Folder class="w-4 h-4" /></template>
          Categories
        </SidebarLink>

        <SidebarLink :href="route('tags.index')" :active="currentRoute?.startsWith('tags.')">
          <template #icon><Tag class="w-4 h-4" /></template>
          Tags
        </SidebarLink>

        <SidebarLink :href="route('media.index')" :active="currentRoute?.startsWith('media.')">
          <template #icon><Image class="w-4 h-4" /></template>
          Media
        </SidebarLink>

        <SidebarLink
          v-if="user.role === 'administrator'"
          :href="route('comments.index')"
          :active="currentRoute?.startsWith('comments.')"
        >
          <template #icon><MessageSquare class="w-4 h-4" /></template>
          <span class="flex-1">Comments</span>
          <span
            v-if="pendingCommentsCount"
            class="ml-auto flex h-5 min-w-5 items-center justify-center rounded-full bg-destructive px-1.5 text-[10px] font-semibold text-destructive-foreground"
          >{{ pendingCommentsCount }}</span>
        </SidebarLink>

        <template v-if="userCan('manage contacts') || userCan('manage companies') || userCan('manage deals') || userCan('manage call lists')">
          <p class="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-sidebar-foreground/40" style="margin-top: var(--density-sidebar-section-mt)">CRM</p>
          <SidebarLink
            v-if="userCan('manage contacts')"
            :href="route('contacts.index')"
            :active="currentRoute?.startsWith('contacts.')"
          >
            <template #icon><UserRound class="w-4 h-4" /></template>
            Contacts
          </SidebarLink>
          <SidebarLink
            v-if="userCan('manage companies')"
            :href="route('companies.index')"
            :active="currentRoute?.startsWith('companies.')"
          >
            <template #icon><Building2 class="w-4 h-4" /></template>
            Companies
          </SidebarLink>
          <SidebarLink
            v-if="userCan('manage deals')"
            :href="route('deals.index')"
            :active="currentRoute?.startsWith('deals.')"
          >
            <template #icon><Handshake class="w-4 h-4" /></template>
            Deals
          </SidebarLink>
          <SidebarLink
            v-if="userCan('manage call lists')"
            :href="route('call-lists.index')"
            :active="currentRoute?.startsWith('call-lists.')"
          >
            <template #icon><Phone class="w-4 h-4" /></template>
            Call Lists
          </SidebarLink>
          <SidebarLink
            :href="route('crm-export.index')"
            :active="currentRoute?.startsWith('crm-export.')"
          >
            <template #icon><Download class="w-4 h-4" /></template>
            CRM Export
          </SidebarLink>
          <SidebarLink
            :href="route('crm-import.index')"
            :active="currentRoute?.startsWith('crm-import.')"
          >
            <template #icon><Upload class="w-4 h-4" /></template>
            CRM Import
          </SidebarLink>
        </template>

        <template v-if="userCan('manage email')">
          <p class="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-sidebar-foreground/40" style="margin-top: var(--density-sidebar-section-mt)">Email</p>
          <SidebarLink
            v-if="user.role === 'administrator'"
            :href="route('email-templates.index')"
            :active="currentRoute?.startsWith('email-templates.')"
          >
            <template #icon><Mail class="w-4 h-4" /></template>
            Templates
          </SidebarLink>
          <SidebarLink :href="route('subscribers.index')" :active="currentRoute?.startsWith('subscribers.')">
            <template #icon><Users class="w-4 h-4" /></template>
            Subscribers
          </SidebarLink>
          <SidebarLink :href="route('campaigns.index')" :active="currentRoute?.startsWith('campaigns.')">
            <template #icon><Send class="w-4 h-4" /></template>
            Campaigns
          </SidebarLink>
        </template>

        <p class="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-sidebar-foreground/40" style="margin-top: var(--density-sidebar-section-mt)">Account</p>
        <SidebarLink :href="route('profile')" :active="currentRoute === 'profile'">
          <template #icon><CircleUser class="w-4 h-4" /></template>
          Profile
        </SidebarLink>

        <SidebarLink
          v-if="user.role === 'administrator'"
          :href="route('users.index')"
          :active="currentRoute?.startsWith('users.')"
        >
          <template #icon><Users class="w-4 h-4" /></template>
          Users
        </SidebarLink>

        <SidebarLink
          v-if="user.role === 'administrator'"
          :href="route('settings.index')"
          :active="currentRoute?.startsWith('settings.')"
        >
          <template #icon><Settings class="w-4 h-4" /></template>
          Settings
        </SidebarLink>

        <SidebarLink
          v-if="user.role === 'administrator'"
          :href="route('webhooks.index')"
          :active="currentRoute?.startsWith('webhooks.')"
        >
          <template #icon><Zap class="w-4 h-4" /></template>
          Webhooks
        </SidebarLink>

        <p class="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-sidebar-foreground/40" style="margin-top: var(--density-sidebar-section-mt)">Data</p>
        <SidebarLink
          v-if="user.role === 'administrator'"
          :href="route('export.index')"
          :active="currentRoute?.startsWith('export.')"
        >
          <template #icon><Download class="w-4 h-4" /></template>
          Export
        </SidebarLink>
        <SidebarLink
          v-if="user.role === 'administrator'"
          :href="route('import.index')"
          :active="currentRoute?.startsWith('import.')"
        >
          <template #icon><Upload class="w-4 h-4" /></template>
          Import
        </SidebarLink>
        <div class="border-t border-sidebar-border my-3"></div>
        <a
          href="/"
          target="_blank"
          rel="noopener"
          class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-foreground transition-colors"
        >
          <ExternalLink class="w-4 h-4 shrink-0" />
          Back to website
        </a>
      </ScrollArea>

      <!-- User / logout at bottom -->
      <div class="border-t border-sidebar-border p-3">
        <div class="flex items-center gap-3 px-3 py-2 rounded-md">
          <div class="w-8 h-8 rounded-full overflow-hidden shrink-0 bg-sidebar-accent flex items-center justify-center text-xs font-semibold uppercase">
            <img
              v-if="user.avatar_url"
              :src="user.avatar_url"
              :alt="user.name"
              class="w-full h-full object-cover"
            />
            <span v-else>{{ userInitials }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium truncate">{{ user.name }}</p>
            <p class="text-xs text-sidebar-foreground/60 truncate">{{ user.email }}</p>
          </div>
          <form @submit.prevent="logout">
            <button type="submit" title="Sign out" class="text-sidebar-foreground/60 hover:text-sidebar-foreground transition-colors">
              <LogOut class="w-4 h-4" />
            </button>
          </form>
        </div>
      </div>
    </aside>

    <Notifications />

    <!-- Main area -->
    <div class="flex flex-col flex-1 overflow-hidden">
      <!-- Topbar -->
      <header class="flex items-center justify-between px-6 border-b border-border bg-background shrink-0" style="height: var(--density-topbar-h); box-shadow: var(--shadow-sm)">
        <h1 class="text-sm font-semibold">{{ title }}</h1>
        <button
          @click="toggleTheme"
          class="inline-flex items-center justify-center w-9 h-9 rounded-md text-muted-foreground hover:text-primary hover:bg-primary/10 transition-colors"
          :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
          :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        >
          <Sun v-if="isDark" class="w-4 h-4" />
          <Moon v-else class="w-4 h-4" />
        </button>
      </header>

      <!-- Page content -->
      <ScrollArea element="main" class="flex-1" style="padding: var(--density-main-p)">
        <slot />
      </ScrollArea>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, watchEffect } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import {
  Sun, Moon, Calendar, ExternalLink, LayoutTemplate,
  LayoutDashboard, FileText, File, Folder, Tag, Image,
  MessageSquare, CircleUser, Users, Settings, Zap, Download, Upload, LogOut,
  UserRound, Building2, Handshake, Phone, Mail, Send,
} from '@lucide/vue';
import SidebarLink from "@/components/SidebarLink.vue";
import ScrollArea from "@/Components/ScrollArea.vue";
import { useTheme } from "@/composables/useTheme.js";
import Notifications from "@/Components/Notifications.vue";

defineProps({
  title: {
    type: String,
    default: "",
  },
});

const page = usePage();
const user = computed(() => page.props.auth?.user ?? { name: "", email: "" });
const currentRoute = computed(() => page.props.currentRoute ?? "");
const pendingCommentsCount = computed(() => page.props.pendingCommentsCount ?? 0);
const permissions = computed(() => page.props.auth?.user?.permissions ?? []);
function userCan(permission) { return permissions.value.includes(permission) }
const userInitials = computed(() =>
  user.value.name
    .split(" ")
    .map((n) => n[0])
    .slice(0, 2)
    .join("")
);

function logout() {
  router.post(route("logout"));
}

const { isDark, initTheme, toggleTheme } = useTheme()

onMounted(() => {
  initTheme()
})

// Reactively apply UI density class
const DENSITY_CLASSES = ['density-compact', 'density-comfortable']
watchEffect(() => {
  const density = page.props.uiDensity ?? 'default'
  DENSITY_CLASSES.forEach(c => document.documentElement.classList.remove(c))
  if (density === 'compact') document.documentElement.classList.add('density-compact')
  else if (density === 'comfortable') document.documentElement.classList.add('density-comfortable')
})

// Reactively apply the accent color CSS variable whenever it changes (e.g. after saving Settings).
const accentHoverMap = {
  '#3898ec': '#2b7fcc',
  '#22c55e': '#16a34a',
  '#f59e0b': '#d97706',
  '#f97316': '#ea580c',
  '#e05368': '#c93d52',
  '#8b5cf6': '#7c3aed',
}
watchEffect(() => {
  const color = page.props.accentColor
  if (color && /^#[0-9a-fA-F]{6}$/.test(color)) {
    document.documentElement.style.setProperty('--primary', color)
    document.documentElement.style.setProperty('--primary-hover', accentHoverMap[color] ?? color)
    document.documentElement.style.setProperty('--primary-foreground', '#ffffff')
    document.documentElement.style.setProperty('--sidebar-primary', color)
    document.documentElement.style.setProperty('--sidebar-primary-foreground', '#ffffff')
    document.documentElement.style.setProperty('--ring', color)
    document.documentElement.style.setProperty('--sidebar-ring', color)
  }
})
</script>
