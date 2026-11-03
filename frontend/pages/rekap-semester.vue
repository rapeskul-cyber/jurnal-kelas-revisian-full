<template>
  <div class="flex-1 flex flex-col">
    <AppHeader
      title="Rekapitulasi Presensi Semester"
    >
      <template #actions>
        <button
          @click="exportExcel"
          type="button"
          class="px-2.5 sm:px-3.5 py-2 rounded-xl bg-white border border-gray-200 text-gray-700 text-xs font-semibold hover:bg-gray-50 transition flex items-center gap-1.5 shadow-2xs cursor-pointer shrink-0 min-h-[36px] sm:min-h-[40px]"
          title="Ekspor Excel"
        >
          <FileSpreadsheetIcon class="w-3.5 h-3.5 text-green-600 shrink-0" />
          <span class="hidden sm:inline">Ekspor Excel</span>
        </button>
        <button
          @click="printPdf"
          type="button"
          class="px-2.5 sm:px-3.5 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-semibold transition flex items-center gap-1.5 shadow-2xs cursor-pointer shrink-0 min-h-[36px] sm:min-h-[40px]"
          title="Cetak PDF"
        >
          <PrinterIcon class="w-3.5 h-3.5 shrink-0" />
          <span class="hidden sm:inline">Cetak PDF</span>
        </button>
      </template>
    </AppHeader>

    <main class="px-4 sm:px-8 pb-10 space-y-5">

    <!-- Filter & Selectors -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-4 md:p-5 flex flex-wrap items-center justify-between gap-4">
      <div class="flex flex-wrap items-center gap-3">
        <div>
          <label class="block text-[11px] font-semibold text-gray-500 mb-1">Tahun Ajaran / Semester</label>
          <select
            v-model="filterTerm"
            @change="fetchData"
            class="px-3 py-2 rounded-xl border border-gray-200 text-xs font-medium text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white cursor-pointer"
          >
            <option value="2026-1">2026/2027 - Ganjil (Aktif)</option>
            <option value="2025-2">2025/2026 - Genap</option>
            <option value="2025-1">2025/2026 - Ganjil</option>
          </select>
        </div>
        <!-- Selector Kelas: Terkunci Otomatis untuk Wali Kelas (GURU_PEMBIMBING), Pilihan untuk Guru Pengajar / Superadmin -->
        <div v-if="isClassLocked">
          <label class="block text-[11px] font-semibold text-gray-500 mb-1">Kelas Binaan</label>
          <div class="px-3.5 py-2 rounded-xl bg-blue-50/70 border border-blue-200 text-xs font-semibold text-primary shadow-2xs flex items-center gap-1.5 whitespace-nowrap min-h-[38px]">
            <span class="w-2 h-2 rounded-full bg-primary"></span>
            <span>Kelas: {{ currentClassName }}</span>
            <span class="text-[10px] text-blue-600/80 font-normal ml-0.5">(Auto-Detect)</span>
          </div>
        </div>
        <div v-else>
          <label class="block text-[11px] font-semibold text-gray-500 mb-1">Pilih Kelas</label>
          <select
            v-model="filterClass"
            @change="fetchData"
            class="px-3 py-2 rounded-xl border border-gray-200 text-xs font-medium text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white cursor-pointer min-h-[38px]"
          >
            <option v-for="cls in classOptions" :key="cls.id" :value="String(cls.id)">
              {{ cls.name }}
            </option>
          </select>
        </div>
      </div>

      <div class="w-full md:w-64">
        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Cari Siswa</label>
        <div class="relative">
          <SearchIcon class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Ketik nama atau NIS..."
            class="w-full pl-9 pr-3 py-2 rounded-xl border border-gray-200 text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
          />
        </div>
      </div>
    </div>

    <!-- Summary KPI cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-card">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Hari Efektif</span>
          <CalendarIcon class="w-5 h-5 text-primary" />
        </div>
        <div class="mt-2 flex items-baseline gap-2">
          <span class="text-3xl font-extrabold text-gray-900">{{ effectiveDays }}</span>
          <span class="text-xs text-gray-500 font-medium">Hari Pembelajaran</span>
        </div>
        <p class="text-[11px] text-gray-400 mt-1">Total pertemuan terjadwal semester ini</p>
      </div>

      <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-card">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Rata-rata Kehadiran</span>
          <TrendingUpIcon class="w-5 h-5 text-green-600" />
        </div>
        <div class="mt-2 flex items-baseline gap-2">
          <span class="text-3xl font-extrabold text-green-600">{{ avgAttendance }}</span>
          <span class="text-xs text-gray-500 font-medium">Kehadiran Kelas</span>
        </div>
        <p class="text-[11px] text-gray-400 mt-1">{{ targetNote }}</p>
      </div>

      <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-card">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Siswa Aktif</span>
          <UsersIcon class="w-5 h-5 text-blue-600" />
        </div>
        <div class="mt-2 flex items-baseline gap-2">
          <span class="text-3xl font-extrabold text-gray-900">{{ students.length }}</span>
          <span class="text-xs text-gray-500 font-medium">Siswa Aktif</span>
        </div>
        <p class="text-[11px] text-gray-400 mt-1">Terdaftar resmi di database Dapodik</p>
      </div>
    </div>

    <!-- Chart Visualisasi Presensi Semester -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-5">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h2 class="text-base font-bold text-gray-800">Grafik Distribusi Presensi</h2>
          <p class="text-xs text-gray-400 mt-0.5">Komposisi kehadiran, sakit, izin, dan alfa seluruh siswa</p>
        </div>
        <div class="flex items-center gap-2">
          <button
            v-for="t in ['bar', 'pie']"
            :key="t"
            type="button"
            @click="chartType = t as 'bar' | 'pie'"
            class="px-3 py-1.5 text-xs font-semibold rounded-lg transition"
            :class="chartType === t ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
          >
            {{ t === 'bar' ? 'Bar Chart' : 'Pie Chart' }}
          </button>
        </div>
      </div>

      <div v-if="loading" class="py-16 text-center text-gray-400 text-sm">
        Memuat grafik...
      </div>
      <div v-else-if="students.length === 0" class="py-14 text-center text-gray-400 text-sm">
        Belum ada data untuk divisualisasikan.
      </div>
      <div v-else>
        <!-- Bar Chart mockup (canvas-based or SVG fallback) -->
        <div v-if="chartType === 'bar'" class="h-56 flex items-end justify-around gap-2 px-4">
          <div
            v-for="s in chartStudents"
            :key="s.name"
            class="flex-1 flex flex-col items-center gap-1 group"
          >
            <div class="w-full flex flex-col gap-0.5 items-center justify-end" style="height: 160px">
              <div
                v-if="s.alpha > 0"
                :style="{ height: (s.alpha / effectiveDays * 100) + '%', minHeight: '4px' }"
                class="w-3/4 rounded-t bg-rose-400 group-hover:bg-rose-500 transition-all"
                :title="`ALFA: ${s.alpha}`"
              ></div>
              <div
                v-if="s.izin > 0"
                :style="{ height: (s.izin / effectiveDays * 100) + '%', minHeight: '4px' }"
                class="w-3/4 bg-blue-400 group-hover:bg-blue-500 transition-all"
                :title="`Izin: ${s.izin}`"
              ></div>
              <div
                v-if="s.sakit > 0"
                :style="{ height: (s.sakit / effectiveDays * 100) + '%', minHeight: '4px' }"
                class="w-3/4 bg-amber-400 group-hover:bg-amber-500 transition-all"
                :title="`Sakit: ${s.sakit}`"
              ></div>
              <div
                :style="{ height: (s.hadir / effectiveDays * 100) + '%', minHeight: '4px' }"
                class="w-3/4 rounded-b bg-emerald-400 group-hover:bg-emerald-500 transition-all"
                :title="`Hadir: ${s.hadir}`"
              ></div>
            </div>
            <span class="text-[9px] text-gray-400 text-center leading-tight max-w-[48px] truncate">
              {{ s.shortName }}
            </span>
          </div>
        </div>

        <!-- Pie chart visual (CSS-based donut) -->
        <div v-else class="flex flex-col sm:flex-row items-center justify-center gap-8 py-4">
          <div class="relative w-40 h-40 flex-shrink-0">
            <svg viewBox="0 0 36 36" class="w-full h-full -rotate-90">
              <circle cx="18" cy="18" r="15.9" fill="none" stroke="#f0fdf4" stroke-width="3.8"/>
              <circle
                cx="18" cy="18" r="15.9" fill="none"
                stroke="#10b981"
                stroke-width="3.8"
                :stroke-dasharray="`${pieData.hadir} ${100 - pieData.hadir}`"
                stroke-dashoffset="0"
              />
              <circle
                cx="18" cy="18" r="15.9" fill="none"
                stroke="#f59e0b"
                stroke-width="3.8"
                :stroke-dasharray="`${pieData.sakit} ${100 - pieData.sakit}`"
                :stroke-dashoffset="`${-(pieData.hadir)}`"
              />
              <circle
                cx="18" cy="18" r="15.9" fill="none"
                stroke="#3b82f6"
                stroke-width="3.8"
                :stroke-dasharray="`${pieData.izin} ${100 - pieData.izin}`"
                :stroke-dashoffset="`${-(pieData.hadir + pieData.sakit)}`"
              />
              <circle
                cx="18" cy="18" r="15.9" fill="none"
                stroke="#f43f5e"
                stroke-width="3.8"
                :stroke-dasharray="`${pieData.alpha} ${100 - pieData.alpha}`"
                :stroke-dashoffset="`${-(pieData.hadir + pieData.sakit + pieData.izin)}`"
              />
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
              <span class="text-xl font-extrabold text-gray-900">{{ pieData.hadir.toFixed(0) }}%</span>
              <span class="text-[10px] text-gray-400 font-medium">Hadir</span>
            </div>
          </div>
          <div class="space-y-2">
            <div v-for="leg in pieLegends" :key="leg.label" class="flex items-center gap-3">
              <div class="w-3 h-3 rounded-full flex-shrink-0" :style="{ background: leg.color }"></div>
              <span class="text-xs text-gray-700 font-semibold min-w-[56px]">{{ leg.label }}</span>
              <span class="text-xs text-gray-400 font-mono">{{ leg.pct.toFixed(1) }}%</span>
            </div>
          </div>
        </div>

        <!-- Legend Bar Chart -->
        <div v-if="chartType === 'bar'" class="flex flex-wrap items-center justify-center gap-4 mt-3 border-t border-gray-100 pt-3">
          <span v-for="l in pieLegends" :key="l.label" class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-sm flex-shrink-0" :style="{ background: l.color }"></span>
            <span class="text-[11px] text-gray-500 font-medium">{{ l.label }}</span>
          </span>
        </div>
      </div>
    </div>

    <!-- Table of Student Semester Recap -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <div>
          <h2 class="text-base font-bold text-gray-800">Daftar Kehadiran Kumulatif Siswa</h2>
          <p class="text-xs text-gray-400 mt-0.5">Rincian angka presensi Hadir, Sakit, Izin, ALFA per siswa</p>
        </div>
        <span class="text-xs text-gray-500 font-medium">{{ filteredStudents.length }} Siswa Ditampilkan</span>
      </div>

      <div v-if="loading" class="py-16 text-center text-gray-400 text-sm">
        Memuat rekapitulasi semester...
      </div>

      <div v-else>
      <!-- Mobile Card List View -->
      <div class="block md:hidden space-y-3 p-4">
        <div
          v-for="(st, index) in filteredStudents"
          :key="st.id"
          class="bg-white rounded-2xl border border-gray-150 p-4 shadow-2xs space-y-3"
        >
          <div class="flex items-start justify-between gap-2">
            <div>
              <div class="flex items-center gap-1.5">
                <span class="text-xs font-bold text-gray-400">#{{ index + 1 }}</span>
                <span class="font-bold text-gray-900 text-sm">{{ st.name }}</span>
              </div>
              <span class="text-[11px] text-gray-500 font-mono">NIS: {{ st.nis }}</span>
            </div>
            <div class="text-right">
              <span class="text-base font-extrabold text-gray-900">{{ st.percentage }}</span>
              <span class="text-[11px] text-gray-400 block font-medium">Kehadiran</span>
            </div>
          </div>

          <!-- Attendance Stats Grid -->
          <div class="grid grid-cols-4 gap-2 text-center text-xs">
            <div class="p-2 bg-emerald-50/70 border border-emerald-100 rounded-xl">
              <span class="text-[10px] text-emerald-600 block">Hadir</span>
              <span class="font-bold text-emerald-800 text-sm">{{ st.hadir }}</span>
            </div>
            <div class="p-2 bg-amber-50/70 border border-amber-100 rounded-xl">
              <span class="text-[10px] text-amber-600 block">Sakit</span>
              <span class="font-bold text-amber-800 text-sm">{{ st.sakit }}</span>
            </div>
            <div class="p-2 bg-blue-50/70 border border-blue-100 rounded-xl">
              <span class="text-[10px] text-blue-600 block">Izin</span>
              <span class="font-bold text-blue-800 text-sm">{{ st.izin }}</span>
            </div>
            <div class="p-2 bg-rose-50/70 border border-rose-100 rounded-xl">
              <span class="text-[10px] text-rose-600 block">ALFA</span>
              <span class="font-bold text-rose-800 text-sm">{{ st.alpha }}</span>
            </div>
          </div>
        </div>
        <div v-if="filteredStudents.length === 0" class="py-12 text-center text-gray-400 text-sm">
          Tidak ada siswa yang cocok dengan pencarian.
        </div>
      </div>

      <!-- Desktop Table View -->
      <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-gray-50/70 border-b border-gray-100 text-gray-500 uppercase tracking-wider font-semibold">
              <th class="py-3 px-4 w-12 text-center whitespace-nowrap">No</th>
              <th class="py-3 px-4 whitespace-nowrap">Nama Siswa</th>
              <th class="py-3 px-4 whitespace-nowrap">NIS / NISN</th>
              <th class="py-3 px-4 text-center text-emerald-700 whitespace-nowrap">Hadir</th>
              <th class="py-3 px-4 text-center text-amber-700 whitespace-nowrap">Sakit</th>
              <th class="py-3 px-4 text-center text-blue-700 whitespace-nowrap">Izin</th>
              <th class="py-3 px-4 text-center text-rose-700 whitespace-nowrap">ALFA</th>
              <th class="py-3 px-4 text-center whitespace-nowrap">Persentase</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="(st, index) in filteredStudents"
              :key="st.id"
              class="hover:bg-gray-50/50 transition"
            >
              <td class="py-3 px-4 text-center font-medium text-gray-500 whitespace-nowrap">{{ index + 1 }}</td>
              <td class="py-3 px-4 whitespace-nowrap">
                <div class="font-bold text-gray-900">{{ st.name }}</div>
              </td>
              <td class="py-3 px-4 text-gray-500 font-mono whitespace-nowrap">{{ st.nis }}</td>
              <td class="py-3 px-4 text-center font-bold text-emerald-600 whitespace-nowrap">{{ st.hadir }}</td>
              <td class="py-3 px-4 text-center font-semibold text-amber-600 whitespace-nowrap">{{ st.sakit }}</td>
              <td class="py-3 px-4 text-center font-semibold text-blue-600 whitespace-nowrap">{{ st.izin }}</td>
              <td class="py-3 px-4 text-center font-semibold text-rose-600 whitespace-nowrap">{{ st.alpha }}</td>
              <td class="py-3 px-4 text-center whitespace-nowrap">
                <span class="font-bold text-gray-900">{{ st.percentage }}</span>
              </td>
            </tr>
            <tr v-if="filteredStudents.length === 0">
              <td colspan="8" class="py-12 text-center text-gray-400 text-sm">
                Tidak ada siswa yang cocok dengan pencarian.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      </div>
    </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { useToastStore } from '~/stores/toast'
import {
  FileSpreadsheet as FileSpreadsheetIcon,
  Printer as PrinterIcon,
  Search as SearchIcon,
  Calendar as CalendarIcon,
  TrendingUp as TrendingUpIcon,
  Users as UsersIcon,
} from 'lucide-vue-next'

const config = useRuntimeConfig()
const authStore = useAuthStore()
const toast = useToastStore()

const filterTerm = ref('2026-1')
const filterClass = ref(
  authStore.user?.role === 'GURU_PEMBIMBING' && authStore.user?.guided_class?.id
    ? String(authStore.user.guided_class.id)
    : (authStore.user?.class_id
        ? String(authStore.user.class_id)
        : (authStore.user?.guided_class?.id ? String(authStore.user.guided_class.id) : ''))
)

const isClassLocked = computed(() => {
  if (['SEKRETARIS', 'SISWA'].includes(authStore.user?.role || '')) return true
  if (authStore.user?.role === 'GURU_PEMBIMBING' && (authStore.user?.guided_class?.id || authStore.user?.class_id)) return true
  return false
})

const currentClassName = computed(() => {
  if (authStore.user?.role === 'GURU_PEMBIMBING') {
    return authStore.user?.guided_class?.name || authStore.user?.school_class?.name || 'Kelas Binaan'
  }
  return authStore.user?.school_class?.name || authStore.user?.guided_class?.name || 'Kelas Anda'
})
const searchQuery = ref('')
const loading = ref(false)
const chartType = ref<'bar' | 'pie'>('bar')

const effectiveDays = ref(0)
const avgAttendance = ref('0%')
const predicate = ref('Sangat Baik')
const targetNote = ref('Target sekolah di atas 90% tercapai')
const predicateBadgeClass = ref('text-green-700 bg-green-50')
const students = ref<any[]>([])

const filteredStudents = computed(() => {
  if (!searchQuery.value) return students.value
  const q = searchQuery.value.toLowerCase()
  return students.value.filter(
    s => s.name.toLowerCase().includes(q) || (s.nis && s.nis.includes(q))
  )
})

// Show only first 10 students in chart to avoid clutter
const chartStudents = computed(() => {
  return students.value.slice(0, 10).map(s => ({
    ...s,
    shortName: s.name.split(' ')[0],
  }))
})

const totalHadir = computed(() => students.value.reduce((a, s) => a + (parseInt(s.hadir) || 0), 0))
const totalSakit = computed(() => students.value.reduce((a, s) => a + (parseInt(s.sakit) || 0), 0))
const totalIzin = computed(() => students.value.reduce((a, s) => a + (parseInt(s.izin) || 0), 0))
const totalAlpha = computed(() => students.value.reduce((a, s) => a + (parseInt(s.alpha) || 0), 0))
const totalAll = computed(() => totalHadir.value + totalSakit.value + totalIzin.value + totalAlpha.value || 1)

const pieData = computed(() => ({
  hadir: (totalHadir.value / totalAll.value) * 100,
  sakit: (totalSakit.value / totalAll.value) * 100,
  izin: (totalIzin.value / totalAll.value) * 100,
  alpha: (totalAlpha.value / totalAll.value) * 100,
}))

const pieLegends = computed(() => [
  { label: 'Hadir', color: '#10b981', pct: pieData.value.hadir },
  { label: 'Sakit', color: '#f59e0b', pct: pieData.value.sakit },
  { label: 'Izin', color: '#3b82f6', pct: pieData.value.izin },
  { label: 'ALFA', color: '#f43f5e', pct: pieData.value.alpha },
])

const fetchData = async () => {
  loading.value = true
  try {
    const res: any = await $fetch(`${config.public.apiBase}/attendance/semester-recap`, {
      headers: { Authorization: `Bearer ${authStore.token}` },
      params: {
        class_id: filterClass.value,
        term: filterTerm.value
      }
    })
    effectiveDays.value = res.effective_days
    avgAttendance.value = res.average_attendance
    if (res.predicate) predicate.value = res.predicate
    if (res.target_note) targetNote.value = res.target_note
    if (res.predicate_badge_class) predicateBadgeClass.value = res.predicate_badge_class
    students.value = res.students || []
  } catch (err: any) {
    console.error('Error fetching semester recap:', err)
  } finally {
    loading.value = false
  }
}

const exportExcel = () => {
  if (!filteredStudents.value || filteredStudents.value.length === 0) {
    toast.warning('Tidak ada data siswa untuk diekspor.')
    return
  }

  const headers = ['No', 'Nama Siswa', 'NIS / NISN', 'Hadir', 'Sakit', 'Izin', 'ALFA', 'Persentase']
  const rows = filteredStudents.value.map((s, idx) => [
    idx + 1,
    `"${(s.name || '').replace(/"/g, '""')}"`,
    `"${(s.nis || '').replace(/"/g, '""')}"`,
    s.hadir,
    s.sakit,
    s.izin,
    s.alpha,
    `"${s.percentage}"`
  ])

  const csvRows = ['sep=;', headers.join(';'), ...rows.map(e => e.join(';'))].join('\r\n')
  const blob = new Blob(['\uFEFF' + csvRows], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.setAttribute('href', url)
  link.setAttribute('download', `rekap_presensi_semester_${filterTerm.value}_kelas_${filterClass.value}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}

const printPdf = () => {
  window.print()
}

const classOptions = ref<any[]>([])

const fetchClasses = async () => {
  try {
    const res: any = await $fetch(`${config.public.apiBase}/master/classes`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    if (Array.isArray(res) && res.length > 0) {
      classOptions.value = res
      const myClassId = authStore.user?.class_id || authStore.user?.guided_class?.id
      if (authStore.user?.role === 'GURU_PEMBIMBING' && myClassId) {
        filterClass.value = String(myClassId)
      } else if (!classOptions.value.some(c => String(c.id) === String(filterClass.value))) {
        filterClass.value = String(res[0].id)
      }
    }
  } catch (e) {
    // Keep fallback
  }
}

onMounted(async () => {
  if (!isClassLocked.value) {
    await fetchClasses()
  } else {
    const myClassId = authStore.user?.guided_class?.id || authStore.user?.class_id
    if (myClassId) filterClass.value = String(myClassId)
  }
  fetchData()
})
</script>
