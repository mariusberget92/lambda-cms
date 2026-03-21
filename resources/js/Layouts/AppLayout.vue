<template>
  <div class="flex h-screen overflow-hidden bg-background">
    <!-- Sidebar -->
    <aside
      class="flex flex-col w-64 border-r bg-sidebar text-sidebar-foreground shrink-0"
    >
      <!-- Logo / App name -->
      <div class="flex items-center gap-2 h-16 px-6 border-b border-sidebar-border">
        <div class="w-7 h-7 rounded-md bg-sidebar-primary flex items-center justify-center">
          <svg class="w-4 h-4 text-sidebar-primary-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3L2 7l10 4 10-4-10-4zM2 17l10 4 10-4M2 12l10 4 10-4" />
          </svg>
        </div>
        <span class="font-semibold text-sm tracking-tight">Lambda CMS</span>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
        <p class="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-sidebar-foreground/40">Overview</p>
        <SidebarLink :href="route('dashboard')" :active="currentRoute === 'dashboard'">
          <template #icon>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
          </template>
          Dashboard
        </SidebarLink>

        <p class="px-3 mt-4 mb-1 text-xs font-semibold uppercase tracking-wider text-sidebar-foreground/40">Content</p>
        <SidebarLink :href="route('posts.index')" :active="currentRoute?.startsWith('posts.')">
          <template #icon>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
          </template>
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
          <template #icon>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l6 6v8a2 2 0 01-2 2z"/>
            </svg>
          </template>
          Pages
        </SidebarLink>

        <SidebarLink
          v-if="user.role === 'administrator'"
          :href="route('navigation.index')"
          :active="currentRoute?.startsWith('navigation.')"
        >
          <template #icon>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7"/>
            </svg>
          </template>
          Navigation
        </SidebarLink>

        <SidebarLink :href="route('categories.index')" :active="currentRoute?.startsWith('categories.')">
          <template #icon>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
            </svg>
          </template>
          Categories
        </SidebarLink>

        <SidebarLink :href="route('tags.index')" :active="currentRoute?.startsWith('tags.')">
          <template #icon>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
          </template>
          Tags
        </SidebarLink>

        <SidebarLink :href="route('media.index')" :active="currentRoute?.startsWith('media.')">
          <template #icon>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
          </template>
          Media
        </SidebarLink>

        <SidebarLink
          v-if="user.role === 'administrator'"
          :href="route('comments.index')"
          :active="currentRoute?.startsWith('comments.')"
        >
          <template #icon>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
            </svg>
          </template>
          <span class="flex-1">Comments</span>
          <span
            v-if="pendingCommentsCount"
            class="ml-auto flex h-5 min-w-5 items-center justify-center rounded-full bg-destructive px-1.5 text-[10px] font-semibold text-destructive-foreground"
          >{{ pendingCommentsCount }}</span>
        </SidebarLink>

        <p class="px-3 mt-4 mb-1 text-xs font-semibold uppercase tracking-wider text-sidebar-foreground/40">Account</p>
        <SidebarLink :href="route('profile')" :active="currentRoute === 'profile'">
          <template #icon>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A9 9 0 1118.88 6.196M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
          </template>
          Profile
        </SidebarLink>

        <SidebarLink
          v-if="user.role === 'administrator'"
          :href="route('users.index')"
          :active="currentRoute?.startsWith('users.')"
        >
          <template #icon>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
          </template>
          Users
        </SidebarLink>

        <SidebarLink
          v-if="user.role === 'administrator'"
          :href="route('settings.index')"
          :active="currentRoute?.startsWith('settings.')"
        >
          <template #icon>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
          </template>
          Settings
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
      </nav>

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
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
              </svg>
            </button>
          </form>
        </div>
      </div>
    </aside>

    <!-- Main area -->
    <div class="flex flex-col flex-1 overflow-hidden">
      <!-- Topbar -->
      <header class="flex items-center justify-between h-16 px-6 border-b border-border bg-background shrink-0">
        <h1 class="text-sm font-semibold">{{ title }}</h1>
        <button
          @click="toggleTheme"
          class="inline-flex items-center justify-center w-9 h-9 rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
          :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
          :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        >
          <Sun v-if="isDark" class="w-4 h-4" />
          <Moon v-else class="w-4 h-4" />
        </button>
      </header>

      <!-- Page content -->
      <main class="flex-1 overflow-y-auto p-6">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { Sun, Moon, Calendar, ExternalLink } from "lucide-vue-next";
import SidebarLink from "@/Components/SidebarLink.vue";
import { useTheme } from "@/composables/useTheme.js";

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
</script>
