<template>
  <header class="bg-white md:bg-transparent border-b md:border-b-0 border-gray-200 px-3 sm:px-4 md:px-8 py-2.5 sm:py-3 md:py-0 md:h-20 select-none sticky md:static top-0 z-30 shadow-2xs md:shadow-none flex items-center justify-between min-w-0 pt-safe">
    <div class="flex items-center gap-2 sm:gap-3 min-w-0 flex-1 mr-2 sm:mr-3">
      <button
        v-if="showBack"
        @click="handleBack"
        type="button"
        class="flex items-center gap-1.5 px-2.5 sm:px-3 py-2 sm:py-2.5 bg-white border border-gray-200 hover:border-gray-300 hover:bg-gray-50 rounded-xl text-xs sm:text-sm font-semibold text-gray-700 transition shadow-2xs shrink-0 cursor-pointer active:scale-95 min-h-[40px] sm:min-h-[44px]"
      >
        <ArrowLeft class="w-4 h-4 md:w-5 md:h-5" />
        <span class="hidden sm:inline">Kembali</span>
      </button>

      <div v-else class="md:hidden w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-primary flex items-center justify-center text-white shrink-0 shadow-xs">
        <Book class="w-4 h-4 sm:w-5 sm:h-5" />
      </div>

      <div class="min-w-0 flex-1">
        <h2 class="text-xs sm:text-lg md:text-2xl font-bold text-gray-900 tracking-tight leading-snug line-clamp-2 sm:line-clamp-none sm:truncate" :title="title">{{ title }}</h2>
        <p v-if="subtitle" class="hidden sm:block text-xs md:text-sm text-gray-500 mt-0.5 truncate leading-normal">{{ subtitle }}</p>
      </div>
    </div>

    <div class="flex items-center gap-1.5 sm:gap-2 md:gap-3 shrink-0 max-w-[65%] sm:max-w-none justify-end">
      <slot name="actions" />

      <!-- Teacher / Walas Dual Role Switcher -->
      <button
        v-if="authStore.isDualRole"
        type="button"
        @click="toggleTeacherRole"
        :disabled="isSwitchingRole"
        class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl border border-primary/30 bg-primary/10 hover:bg-primary hover:text-white text-primary text-xs font-bold transition shadow-2xs cursor-pointer min-h-[36px] sm:min-h-[40px] shrink-0 active:scale-95 disabled:opacity-50"
        :title="authStore.user?.role === 'GURU_PEMBIMBING' ? 'Klik untuk beralih ke Mode Guru Pengajar' : 'Klik untuk beralih ke Mode Wali Kelas'"
      >
        <RefreshCw class="w-3.5 h-3.5 shrink-0" :class="{ 'animate-spin': isSwitchingRole }" />
        <span class="hidden sm:inline">{{ authStore.user?.role === 'GURU_PEMBIMBING' ? 'Mode: Wali Kelas' : 'Mode: Guru Pengajar' }}</span>
        <span class="sm:hidden">{{ authStore.user?.role === 'GURU_PEMBIMBING' ? 'Walas' : 'Guru' }}</span>
      </button>

      <!-- Kritik & Saran Link (Desktop/Tablet) -->
      <a
        href="https://saran.rapiuw.ai.id"
        target="_blank"
        class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg md:rounded-xl bg-blue-50 border border-blue-200 text-primary hover:bg-blue-100 text-xs font-semibold transition shadow-2xs"
        title="Kirim kritik dan saran portal"
      >
        <span>Kritik dan Saran</span>
      </a>

      <!-- Notification Bell -->
      <NuxtLink
        v-if="!hideNotifications"
        to="/notifikasi"
        class="relative w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-gray-600 hover:text-gray-900 hover:border-gray-300 transition shadow-2xs min-h-[36px] min-w-[36px] sm:min-h-[44px] sm:min-w-[44px] active:scale-95 shrink-0"
        title="Pemberitahuan"
      >
        <Bell class="w-4 h-4 md:w-5 md:h-5" />
        <span
          v-if="unreadCount > 0"
          class="absolute -top-1 -right-1 w-4 h-4 md:w-5 md:h-5 bg-primary text-white text-[9px] md:text-[11px] font-bold rounded-full flex items-center justify-center ring-2 ring-white"
        >
          {{ unreadCount }}
        </span>
      </NuxtLink>

      <!-- Profile Avatar on Mobile -->
      <NuxtLink
        to="/profile"
        class="md:hidden w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-blue-100 text-primary font-bold flex items-center justify-center text-xs ring-1 ring-blue-200 transition shadow-2xs hover:ring-2 hover:ring-primary/20 overflow-hidden min-h-[36px] min-w-[36px] sm:min-h-[44px] sm:min-w-[44px] active:scale-95 shrink-0"
        title="Profil Saya"
      >
        <img
          v-if="authStore.user?.avatar || authStore.user?.avatar_url"
          :src="authStore.user?.avatar || authStore.user?.avatar_url"
          alt="Avatar"
          class="w-full h-full object-cover"
        />
        <span v-else>{{ authStore.user?.name ? authStore.user.name.charAt(0).toUpperCase() : 'U' }}</span>
      </NuxtLink>

      <!-- Logout Button (Desktop only, Original Style) -->
      <button
        v-if="!hideLogout"
        @click="handleLogout"
        type="button"
        class="hidden md:flex items-center gap-1.5 md:gap-2 px-2.5 py-1.5 md:px-3.5 md:py-2 bg-white border border-gray-200 hover:border-red-200 hover:text-primary rounded-lg md:rounded-xl text-xs font-semibold text-gray-700 transition shadow-2xs cursor-pointer min-h-[44px] active:scale-95 shrink-0"
        title="Keluar dari Akun"
      >
        <LogOut class="w-4 h-4" />
        <span class="hidden sm:inline">Keluar</span>
      </button>
    </div>
  </header>
</template>

<script setup lang="ts">
import {
  Bell,
  LogOut,
  ArrowLeft,
  Book,
  User,
  RefreshCw,
} from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'

const props = withDefaults(
  defineProps<{
    title?: string
    subtitle?: string
    showBack?: boolean
    backTo?: string
    hideNotifications?: boolean
    hideLogout?: boolean
  }>(),
  {
    title: 'Jurnal Kelas',
    subtitle: '',
    showBack: false,
    backTo: '',
    hideNotifications: false,
    hideLogout: false,
  }
)

const router = useRouter()
const authStore = useAuthStore()
const unreadCount = ref(0)
const isSwitchingRole = ref(false)

const toggleTeacherRole = async () => {
  const currentRole = authStore.user?.role
  const targetRole = currentRole === 'GURU_PEMBIMBING' ? 'GURU_PENGAJAR' : 'GURU_PEMBIMBING'
  isSwitchingRole.value = true
  try {
    await authStore.switchRole(targetRole)
  } catch (e) {
    console.error('Failed to switch role:', e)
  } finally {
    isSwitchingRole.value = false
  }
}

onMounted(async () => {
  if (authStore.token) {
    try {
      const res = await $fetch<{ unread_count: number }>(`${authStore.apiBase}/notifications`, {
        headers: { Authorization: `Bearer ${authStore.token}` }
      })
      if (res && typeof res.unread_count === 'number') {
        unreadCount.value = res.unread_count
      }
    } catch (e) {
      // fallback
    }
  }
})

function handleBack() {
  if (props.backTo) {
    navigateTo(props.backTo)
  } else {
    router.back()
  }
}

async function handleLogout() {
  await authStore.logout()
}
</script>
