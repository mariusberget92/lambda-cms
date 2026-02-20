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
      <header class="flex items-center justify-between h-16 px-6 border-b shrink-0">
        <h1 class="text-sm font-semibold">{{ title }}</h1>
      </header>

      <!-- Page content -->
      <main class="flex-1 overflow-y-auto p-6">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import SidebarLink from "@/Components/SidebarLink.vue";

defineProps({
  title: {
    type: String,
    default: "",
  },
});

const page = usePage();
const user = computed(() => page.props.auth?.user ?? { name: "", email: "" });
const currentRoute = computed(() => page.props.currentRoute ?? "");
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
</script>
