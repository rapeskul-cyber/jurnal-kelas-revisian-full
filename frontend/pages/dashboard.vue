<template>
  <div class="flex-1 flex flex-col">
    <AppHeader
      :title="dashboardTitle"
      :subtitle="dashboardSubtitle"
    />

    <main class="px-4 sm:px-8 pb-8 space-y-5">
      <!-- Loading Skeleton -->
      <div v-if="isLoading" class="grid grid-cols-2 md:grid-cols-4 gap-2.5 sm:gap-4 animate-pulse">
        <div v-for="i in 4" :key="i" class="h-28 bg-gray-200 rounded-2xl"></div>
      </div>

      <!-- ============================================================== -->
      <!-- DUAL-ROLE MODE SWITCHER CARDS (Sesuai Wireframe: [Sebagai Pengajar] [Sebagai Walas]) -->
      <!-- ============================================================== -->
      <div v-if="authStore.isDualRole" class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
        <button
          type="button"
          @click="handleSwitchRole('GURU_PENGAJAR')"
          :disabled="isSwitchingRole"
          class="flex items-center justify-between p-4 sm:p-5 rounded-2xl border-2 transition text-left cursor-pointer shadow-2xs active:scale-[0.99] disabled:opacity-50"
          :class="authStore.user?.role === 'GURU_PENGAJAR'
            ? 'border-primary bg-primary text-white shadow-md'
            : 'border-gray-200 bg-white hover:border-primary/50 text-gray-700 hover:bg-blue-50/20'"
        >
          <div class="space-y-0.5">
            <span class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider block" :class="authStore.user?.role === 'GURU_PENGAJAR' ? 'text-blue-100' : 'text-gray-400'">Mode Guru Mapel</span>
            <span class="text-sm sm:text-base font-extrabold block">Sebagai Pengajar</span>
            <span class="text-[11px] block" :class="authStore.user?.role === 'GURU_PENGAJAR' ? 'text-blue-100' : 'text-gray-500'">Jadwal KBM & Isi Jurnal Pelajaran</span>
          </div>
          <span
            v-if="authStore.user?.role === 'GURU_PENGAJAR'"
            class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-white text-primary border border-white/20 shrink-0"
          >
            Aktif ✓
          </span>
          <span
            v-else
            class="px-2.5 py-1 rounded-full text-[10px] font-medium bg-gray-100 text-gray-500 shrink-0"
          >
            Beralih →
          </span>
        </button>

        <button
          type="button"
          @click="handleSwitchRole('GURU_PEMBIMBING')"
          :disabled="isSwitchingRole"
          class="flex items-center justify-between p-4 sm:p-5 rounded-2xl border-2 transition text-left cursor-pointer shadow-2xs active:scale-[0.99] disabled:opacity-50"
          :class="authStore.user?.role === 'GURU_PEMBIMBING'
            ? 'border-primary bg-primary text-white shadow-md'
            : 'border-gray-200 bg-white hover:border-primary/50 text-gray-700 hover:bg-blue-50/20'"
        >
          <div class="space-y-0.5">
            <span class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider block" :class="authStore.user?.role === 'GURU_PEMBIMBING' ? 'text-blue-100' : 'text-gray-400'">Mode Wali Kelas</span>
            <span class="text-sm sm:text-base font-extrabold block">Sebagai Walas</span>
            <span class="text-[11px] block" :class="authStore.user?.role === 'GURU_PEMBIMBING' ? 'text-blue-100' : 'text-gray-500'">Pantau Rombel & Persetujuan Izin</span>
          </div>
          <span
            v-if="authStore.user?.role === 'GURU_PEMBIMBING'"
            class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-white text-primary border border-white/20 shrink-0"
          >
            Aktif ✓
          </span>
          <span
            v-else
            class="px-2.5 py-1 rounded-full text-[10px] font-medium bg-gray-100 text-gray-500 shrink-0"
          >
            Beralih →
          </span>
        </button>
      </div>

      <!-- ============================================== -->
      <!-- ROLE: SISWA (Status Kehadiran & Jadwal KBM)   -->
      <!-- ============================================== -->
      <template v-else-if="authStore.user?.role === 'SISWA'">
        <!-- Kartu Status Kehadiran Siswa Hari Ini -->
        <div class="bg-white rounded-2xl border border-blue-200 p-4 sm:p-6 shadow-2xs flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
          <div class="space-y-1.5">
            <div class="flex flex-wrap items-center gap-2">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-50 text-primary border border-blue-200">
                Status Kehadiran Siswa
              </span>
              <span class="text-xs text-gray-500 font-medium">
                {{ data?.today_attendance?.date_formatted || 'Hari Ini' }} • {{ authStore.user?.school_class?.name || '-' }}
              </span>
            </div>
            <h3 class="text-base font-bold text-gray-900 flex flex-wrap items-center gap-1.5">
              <span>Status Kehadiran Anda Hari Ini:</span>
              <span
                :class="[
                  'px-2.5 py-0.5 rounded-lg text-xs font-bold inline-block',
                  data?.today_attendance?.is_leave_rejected ? 'bg-rose-50 text-rose-700 border border-rose-200' :
                  data?.today_attendance?.is_leave_approved ? 'bg-orange-50 text-orange-700 border border-orange-200' :
                  data?.today_attendance?.checked_in ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' :
                  data?.today_attendance?.has_pending_leave ? 'bg-amber-50 text-amber-700 border border-amber-200' :
                  'bg-gray-100 text-gray-700 border border-gray-200'
                ]"
              >
                {{
                  data?.today_attendance?.is_leave_rejected ? 'Surat Izin Ditolak' :
                  data?.today_attendance?.is_leave_approved ? `Izin Disetujui (${data?.today_attendance?.status})` :
                  data?.today_attendance?.checked_in ? (data?.today_attendance?.status || 'HADIR') :
                  data?.today_attendance?.has_pending_leave ? 'Menunggu Persetujuan Izin' : 'Menunggu Jam KBM'
                }}
              </span>
            </h3>
            <p class="text-xs text-gray-500">
              {{ data?.today_attendance?.note || (data?.today_attendance?.checked_in ? 'Kehadiran Anda hari ini telah dicatat oleh guru di kelas.' : 'Status kehadiran dicatat oleh Guru Pengajar pada saat jam pelajaran berlangsung.') }}
            </p>
          </div>

          <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-2.5 w-full lg:w-auto">
            <!-- Jika Surat Izin Ditolak Wali Kelas -->
            <div
              v-if="data?.today_attendance?.is_leave_rejected"
              class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-2 w-full sm:w-auto"
            >
              <div class="px-4 py-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold shadow-2xs flex items-center justify-center sm:justify-start gap-2">
                <XCircle class="w-4 h-4 text-rose-600 flex-shrink-0" />
                <span>Surat Izin Ditolak (Wajib Hadir di Kelas)</span>
              </div>
              <NuxtLink
                to="/surat-izin"
                class="px-3.5 py-2.5 rounded-xl bg-white border border-rose-200 hover:border-rose-300 text-rose-700 text-xs font-semibold transition flex items-center justify-center gap-1.5 min-h-[44px]"
              >
                <span>Lihat Alasan Penolakan</span>
              </NuxtLink>
            </div>

            <!-- Jika Surat Izin Disetujui (SAKIT / IZIN) -->
            <div
              v-else-if="data?.today_attendance?.is_leave_approved || (data?.today_attendance?.checked_in && (data?.today_attendance?.status === 'SAKIT' || data?.today_attendance?.status === 'IZIN' || data?.today_attendance?.status === 'DISPENSASI'))"
              class="px-4 py-2.5 rounded-xl bg-orange-50 border border-orange-200 text-orange-800 text-xs font-bold shadow-2xs flex items-center justify-center sm:justify-start gap-2 w-full sm:w-auto min-h-[44px]"
            >
              <CheckCircle2 class="w-4 h-4 text-orange-600" />
              <span>Surat Izin Disetujui ({{ data?.today_attendance?.status }})</span>
            </div>

            <!-- Jika Sudah Tercatat HADIR -->
            <div
              v-else-if="data?.today_attendance?.checked_in"
              class="px-4 py-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold shadow-2xs flex items-center justify-center sm:justify-start gap-2 w-full sm:w-auto min-h-[44px]"
            >
              <CheckCircle2 class="w-4 h-4 text-emerald-600" />
              <span>Tercatat Hadir ({{ data?.today_attendance?.status || 'HADIR' }})</span>
            </div>

            <div
              v-else-if="data?.today_attendance?.has_pending_leave"
              class="px-4 py-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold shadow-2xs flex items-center justify-center sm:justify-start gap-2 w-full sm:w-auto min-h-[44px]"
            >
              <Clock class="w-4 h-4 text-amber-600" />
              <span>Izin Menunggu Persetujuan</span>
            </div>

            <NuxtLink
              to="/surat-izin"
              class="px-3.5 py-2.5 rounded-xl bg-white border border-gray-200 hover:border-primary text-gray-700 hover:text-primary text-xs font-semibold transition flex items-center justify-center gap-1.5 w-full sm:w-auto min-h-[44px]"
            >
              <Mail class="w-4 h-4 text-primary" />
              <span>Ajukan Surat Izin</span>
            </NuxtLink>

            <NuxtLink
              to="/jurnal-pelajaran"
              class="px-3.5 py-2.5 rounded-xl bg-white border border-gray-200 hover:border-primary text-gray-700 hover:text-primary text-xs font-semibold transition flex items-center justify-center gap-1.5 w-full sm:w-auto min-h-[44px]"
            >
              <FileText class="w-4 h-4 text-primary" />
              <span>Isi Jurnal Kegiatan</span>
            </NuxtLink>
          </div>
        </div>

        <!-- 4 Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
          <StatCard title="Hadir Bulan Ini" :value="data?.stats?.hadir_hari ?? '0 Hari'" :subtext="data?.stats?.hadir_percent ?? 'Bulan Ini'" color="blue" />
          <StatCard title="Total Sakit" :value="data?.stats?.sakit_hari ?? '0 Hari'" subtext="Surat Terlampir" color="blue" />
          <StatCard title="Total Izin Disetujui" :value="data?.stats?.izin_hari ?? '0 Hari'" subtext="Disetujui Wali Kelas" color="blue" />
          <StatCard title="Alpha / Tanpa Kabar" :value="data?.stats?.alpha_hari ?? '0 Hari'" subtext="Tanpa Keterangan" color="blue" />
        </div>

        <!-- Widget Timeline Materi Belajar Hari Ini -->
        <div class="bg-white rounded-2xl border border-gray-150 p-4 sm:p-6 shadow-2xs space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-gray-100 gap-1.5">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-xl bg-blue-50 text-primary flex items-center justify-center shrink-0">
                <Clock class="w-4 h-4" />
              </div>
              <div>
                <h3 class="text-sm sm:text-base font-bold text-gray-900">Timeline Materi Belajar Hari Ini</h3>
                <p class="text-[11px] text-gray-500">Urutan sesi jam pelajaran, topik materi yang diajarkan, dan status terlaksana</p>
              </div>
            </div>
            <span class="text-xs font-semibold text-primary bg-blue-50 px-3 py-1 rounded-full self-start sm:self-auto border border-blue-200">
              {{ data?.today_attendance?.date_formatted || 'Hari Ini' }}
            </span>
          </div>

          <!-- Timeline Items -->
          <div v-if="data?.today_timeline && data.today_timeline.length > 0" class="relative pl-6 sm:pl-8 space-y-4 before:absolute before:left-2.5 sm:before:left-3.5 before:top-3 before:bottom-3 before:w-0.5 before:bg-gray-200">
            <div
              v-for="(item, idx) in data.today_timeline"
              :key="idx"
              class="relative"
            >
              <!-- Timeline Node Dot -->
              <div
                class="absolute -left-6 sm:-left-8 top-2 w-3.5 h-3.5 rounded-full border-2 border-white ring-2 transition"
                :class="item.status === 'TERLAKSANA' ? 'bg-emerald-500 ring-emerald-200' : item.status === 'MENUNGGU_VERIFIKASI' ? 'bg-primary ring-blue-200' : 'bg-gray-300 ring-gray-150'"
              ></div>

              <div class="p-3.5 sm:p-4 rounded-xl border border-gray-150 bg-gray-50/50 hover:bg-white hover:shadow-xs transition space-y-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                  <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-primary/10 text-primary">
                      {{ item.jam_ke }}
                    </span>
                    <span class="text-xs font-mono font-semibold text-gray-700">
                      {{ item.time }}
                    </span>
                    <span class="text-[11px] text-gray-400">
                      • {{ item.room }}
                    </span>
                  </div>

                  <!-- Status Terlaksana Badge -->
                  <span
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold shrink-0"
                    :class="item.status === 'TERLAKSANA' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : item.status === 'MENUNGGU_VERIFIKASI' ? 'bg-blue-50 text-primary border border-blue-200' : 'bg-gray-100 text-gray-600 border border-gray-200'"
                  >
                    <CheckCircle2 v-if="item.status === 'TERLAKSANA'" class="w-3 h-3 text-emerald-600" />
                    <Clock v-else class="w-3 h-3 text-gray-400" />
                    <span>{{ item.status_label }}</span>
                  </span>
                </div>

                <div>
                  <h4 class="text-xs sm:text-sm font-bold text-gray-900">{{ item.subject }}</h4>
                  <p class="text-xs text-gray-500 mt-0.5">
                    Guru Pengajar: <strong class="text-gray-700 font-medium">{{ item.teacher_name }}</strong>
                  </p>
                </div>

                <!-- Materi / Pokok Bahasan -->
                <div class="p-2.5 rounded-lg bg-white border border-gray-150 text-xs">
                  <span class="font-semibold text-gray-500 text-[10px] uppercase block tracking-wider mb-0.5">Materi / Topik KBM:</span>
                  <p class="text-gray-800 font-medium leading-relaxed">
                    {{ item.topic }}
                  </p>
                  <p v-if="item.notes" class="text-[11px] text-gray-500 italic mt-1 pt-1 border-t border-gray-100">
                    Catatan: {{ item.notes }}
                  </p>

                  <!-- Direct Download Modul Pembelajaran jika ada file -->
                  <div v-if="item.attachment_url" class="pt-2 mt-1.5 border-t border-gray-100">
                    <a
                      :href="item.attachment_url"
                      target="_blank"
                      :download="item.attachment_name || 'Modul_Belajar.pdf'"
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-primary border border-blue-200 text-xs font-bold transition shadow-2xs cursor-pointer active:scale-95 max-w-full"
                    >
                      <Download class="w-3.5 h-3.5 shrink-0" />
                      <span class="truncate">Unduh Modul / Lampiran ({{ item.attachment_name || 'Berkas' }})</span>
                    </a>
                  </div>
                </div>

                <!-- Alert Siswa Tidak Berada di Jam Ini jika ada -->
                <div v-if="item.absent_students && item.absent_students.length > 0" class="p-2 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-[11px] space-y-0.5">
                  <span class="font-bold flex items-center gap-1">
                    <AlertCircle class="w-3 h-3 text-rose-600" />
                    Siswa tidak berada di kelas pada jam ini ({{ item.absent_students.length }} siswa):
                  </span>
                  <p class="text-rose-700">
                    {{ item.absent_students.map((s: any) => s.name).join(', ') }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else class="text-center py-8 rounded-xl border border-dashed border-gray-200 bg-gray-50/50 space-y-1.5">
            <div class="w-8 h-8 rounded-full bg-blue-50 text-primary mx-auto flex items-center justify-center">
              <Calendar class="w-4 h-4" />
            </div>
            <p class="text-xs font-bold text-gray-700">Tidak Ada Sesi KBM Terjadwal Hari Ini</p>
            <p class="text-[11px] text-gray-400">Jadwal pembelajaran untuk hari ini kosong atau sedang masa jeda KBM.</p>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Riwayat Kehadiran Terakhir -->
          <div class="bg-white rounded-2xl border border-gray-150 p-4 sm:p-6 shadow-2xs">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-sm font-bold text-gray-900">Riwayat Kehadiran Terakhir</h3>
              <NuxtLink to="/riwayat-kehadiran" class="text-xs text-primary font-semibold hover:underline">
                Lihat Semua
              </NuxtLink>
            </div>
            <!-- Mobile Card View -->
            <div class="block md:hidden space-y-2.5">
              <div
                v-for="rec in data?.recent_attendance || []"
                :key="rec.id"
                class="p-3 bg-gray-50/70 border border-gray-100 rounded-xl space-y-1.5"
              >
                <div class="flex items-center justify-between text-xs">
                  <span class="font-bold text-gray-900">{{ rec.date }}</span>
                  <StatusBadge :status="rec.status" />
                </div>
                <div class="text-[11px] text-gray-500 flex items-center justify-between">
                  <span>{{ rec.note || '-' }}</span>
                  <span class="text-gray-400">{{ rec.school_hours }}</span>
                </div>
              </div>
              <div v-if="!data?.recent_attendance?.length" class="text-center py-6 text-gray-400 text-xs">
                Belum ada riwayat kehadiran.
              </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead>
                  <tr class="text-gray-400 uppercase text-[10px] border-b border-gray-100">
                    <th class="pb-3 font-semibold">Hari / Tanggal</th>
                    <th class="pb-3 font-semibold">Status</th>
                    <th class="pb-3 font-semibold">Keterangan / Waktu</th>
                    <th class="pb-3 font-semibold">Jam Sekolah</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                  <tr v-for="rec in data?.recent_attendance || []" :key="rec.id" class="hover:bg-gray-50/50">
                    <td class="py-3 font-medium text-gray-900">{{ rec.date }}</td>
                    <td class="py-3"><StatusBadge :status="rec.status" /></td>
                    <td class="py-3 text-gray-500">{{ rec.note }}</td>
                    <td class="py-3 text-gray-400">{{ rec.school_hours }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Jurnal Kegiatan Siswa & Materi Belajar Guru -->
          <div class="bg-white rounded-2xl border border-gray-150 p-4 sm:p-6 shadow-2xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-100 pb-3">
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  @click="studentJournalTab = 'journals'"
                  :class="[
                    'px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer',
                    studentJournalTab === 'journals'
                      ? 'bg-blue-50 text-primary border border-blue-200'
                      : 'text-gray-500 hover:text-gray-800'
                  ]"
                >
                  Laporan Jurnal Belajar
                </button>
                <button
                  type="button"
                  @click="studentJournalTab = 'materials'"
                  :class="[
                    'px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer',
                    studentJournalTab === 'materials'
                      ? 'bg-blue-50 text-primary border border-blue-200'
                      : 'text-gray-500 hover:text-gray-800'
                  ]"
                >
                  Materi dari Guru
                </button>
              </div>
              <NuxtLink to="/jurnal-pelajaran" class="text-xs text-primary font-semibold hover:underline">
                Lihat Selengkapnya →
              </NuxtLink>
            </div>

            <!-- Tab 1: Laporan Jurnal Belajar Siswa -->
            <div v-if="studentJournalTab === 'journals'">
              <!-- Mobile Card View -->
              <div class="block md:hidden space-y-2.5">
                <div
                  v-for="j in data?.recent_journals || []"
                  :key="j.id"
                  class="p-3 bg-gray-50/70 border border-gray-100 rounded-xl space-y-1.5"
                >
                  <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-primary">{{ j.subject }}</span>
                    <span class="text-[11px] text-gray-400">{{ j.date }}</span>
                  </div>
                  <p class="text-xs text-gray-800">{{ j.activity }}</p>
                  <div class="pt-1 flex items-center justify-end">
                    <StatusBadge :status="j.status" />
                  </div>
                </div>
                <div v-if="!data?.recent_journals?.length" class="py-6 text-center text-gray-400 text-xs">
                  Belum ada jurnal kegiatan yang dilaporkan.
                </div>
              </div>

              <!-- Desktop Table View -->
              <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs min-w-[500px]">
                  <thead>
                    <tr class="text-gray-400 uppercase text-[10px] border-b border-gray-100">
                      <th class="pb-3 font-semibold">Tanggal</th>
                      <th class="pb-3 font-semibold">Mata Pelajaran</th>
                      <th class="pb-3 font-semibold">Kegiatan Belajar</th>
                      <th class="pb-3 font-semibold text-center">Status</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-50">
                    <tr v-for="j in data?.recent_journals || []" :key="j.id" class="hover:bg-gray-50/50">
                      <td class="py-3 font-medium text-gray-900 whitespace-nowrap">{{ j.date }}</td>
                      <td class="py-3 text-gray-800 font-medium whitespace-nowrap">{{ j.subject }}</td>
                      <td class="py-3 text-gray-600 truncate max-w-[200px]">{{ j.activity }}</td>
                      <td class="py-3 text-center whitespace-nowrap">
                        <StatusBadge :status="j.status" />
                      </td>
                    </tr>
                    <tr v-if="!data?.recent_journals?.length">
                      <td colspan="4" class="py-6 text-center text-gray-400">Belum ada jurnal kegiatan yang dilaporkan.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Tab 2: Materi dari Guru -->
            <div v-else>
              <!-- Mobile Card View -->
              <div class="block md:hidden space-y-2.5">
                <div
                  v-for="mat in data?.recent_materials || []"
                  :key="mat.id"
                  class="p-3 bg-gray-50/70 border border-gray-100 rounded-xl space-y-1.5"
                >
                  <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-gray-900">{{ mat.subject }}</span>
                    <span class="text-[11px] text-gray-400">{{ mat.date }}</span>
                  </div>
                  <p class="text-xs text-gray-700 font-medium">{{ mat.topic }}</p>
                  <div class="pt-1 flex items-center justify-between text-[11px]">
                    <span class="text-gray-400">Guru:</span>
                    <span class="text-[11px] font-medium text-gray-600 bg-gray-50 px-2 py-0.5 rounded-md border border-gray-150">{{ mat.teacher || 'Guru' }}</span>
                  </div>
                </div>
                <div v-if="!data?.recent_materials?.length" class="py-6 text-center text-gray-400 text-xs">
                  Belum ada materi pelajaran yang diunggah guru.
                </div>
              </div>

              <!-- Desktop Table View -->
              <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs min-w-[500px]">
                  <thead>
                    <tr class="text-gray-400 uppercase text-[10px] border-b border-gray-100">
                      <th class="pb-3 font-semibold">Tanggal</th>
                      <th class="pb-3 font-semibold">Mata Pelajaran</th>
                      <th class="pb-3 font-semibold">Judul Materi</th>
                      <th class="pb-3 font-semibold text-right">Guru</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-50">
                    <tr v-for="mat in data?.recent_materials || []" :key="mat.id" class="hover:bg-gray-50/50">
                      <td class="py-3 font-medium text-gray-900 whitespace-nowrap">{{ mat.date }}</td>
                      <td class="py-3 text-gray-800 font-medium whitespace-nowrap">{{ mat.subject }}</td>
                      <td class="py-3 text-gray-600 truncate max-w-[200px]">{{ mat.topic }}</td>
                      <td class="py-3 text-right whitespace-nowrap">
                        <span class="text-[11px] font-medium text-gray-600 bg-gray-50 px-2 py-0.5 rounded-md border border-gray-150">{{ mat.teacher || 'Guru' }}</span>
                      </td>
                    </tr>
                    <tr v-if="!data?.recent_materials?.length">
                      <td colspan="4" class="py-6 text-center text-gray-400">Belum ada materi pelajaran yang diunggah guru.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- ============================================== -->
      <!-- ROLE: ORANG TUA (Pantauan Kehadiran & Jurnal)  -->
      <!-- ============================================== -->
      <template v-else-if="authStore.user?.role === 'ORANG_TUA'">
        <!-- Kartu Identitas Anak & Status Kehadiran Real-Time -->
        <div
          :class="[
            'bg-white rounded-2xl p-4 sm:p-6 shadow-2xs space-y-4 border transition',
            data?.child?.is_leave_rejected
              ? 'border-rose-200'
              : data?.child?.is_leave_approved
              ? 'border-orange-200'
              : data?.child?.has_pending_leave
              ? 'border-amber-200'
              : data?.child?.today_status === 'HADIR'
              ? 'border-emerald-200'
              : 'border-gray-200'
          ]"
        >
          <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <div class="space-y-1.5">
              <div class="flex flex-wrap items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-50 text-primary border border-blue-200">
                  Portal Pantauan Orang Tua / Wali Murid
                </span>
                <span class="text-xs text-gray-500 font-medium">
                  {{ data?.child?.date_formatted || 'Hari Ini' }}
                </span>
              </div>
              <h3 class="text-base font-bold text-gray-900">
                Siswa: {{ data?.child?.name || 'Siswa' }}
                <span class="text-xs font-normal text-gray-500 ml-1">
                  (NIS: {{ data?.child?.nis || '-' }} • {{ data?.child?.class_name || '-' }} • Wali Kelas: {{ data?.child?.wali_kelas || '-' }})
                </span>
              </h3>
              <div class="flex flex-wrap items-center gap-2 pt-0.5">
                <span class="text-xs text-gray-600">Status Kehadiran Hari Ini:</span>
                <StatusBadge :status="data?.child?.today_status || 'BELUM_PRESENSI'" />
                <span class="text-xs text-gray-500">— {{ data?.child?.today_note || 'Belum melakukan presensi' }}</span>
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
              <NuxtLink
                to="/jurnal-pelajaran"
                class="px-3.5 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-semibold transition flex items-center gap-1.5"
              >
                <FileText class="w-4 h-4" />
                <span>Lihat Jurnal Belajar Anak</span>
              </NuxtLink>
              <NuxtLink
                to="/riwayat-kehadiran"
                class="px-3.5 py-2 rounded-xl bg-white border border-gray-200 hover:border-primary text-gray-700 text-xs font-semibold transition flex items-center gap-1.5"
              >
                <ClipboardCheck class="w-4 h-4 text-primary" />
                <span>Detail Riwayat Kehadiran</span>
              </NuxtLink>
            </div>
          </div>

          <!-- Alert Khusus Orang Tua Jika Surat Izin Anak DITOLAK oleh Wali Kelas -->
          <div
            v-if="data?.child?.is_leave_rejected"
            class="p-4 bg-rose-50 border border-rose-200 rounded-xl flex items-start gap-3"
          >
            <XCircle class="w-5 h-5 text-rose-600 flex-shrink-0 mt-0.5" />
            <div class="text-xs space-y-1">
              <p class="font-bold text-rose-900">
                Pemberitahuan: Permohonan Izin Siswa Ditolak oleh Wali Kelas
              </p>
              <p class="text-rose-700 leading-relaxed">
                Alasan Penolakan Wali Kelas: <em>"{{ data?.child?.rejection_reason || 'Tidak memenuhi ketentuan izin sekolah.' }}"</em>
              </p>
              <p class="text-rose-800 text-[11px] font-medium pt-0.5">
                Putra/putri Anda wajib tetap hadir ke sekolah dan melapor langsung ke Sekretaris Kelas untuk verifikasi kehadiran fisik.
              </p>
            </div>
          </div>

          <!-- Alert Khusus Orang Tua Jika Surat Izin Menunggu Persetujuan -->
          <div
            v-else-if="data?.child?.has_pending_leave"
            class="p-3.5 bg-amber-50 border border-amber-200 rounded-xl flex items-center gap-2.5 text-xs text-amber-900"
          >
            <Clock class="w-4 h-4 text-amber-600 flex-shrink-0" />
            <span>Permohonan surat izin putra/putri Anda hari ini sedang dalam proses review oleh Wali Kelas.</span>
          </div>

          <!-- Alert Khusus Jika Akun Orang Tua Belum Ditautkan ke Siswa -->
          <div
            v-else-if="!data?.child?.id"
            class="p-3.5 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-2.5 text-xs text-amber-900"
          >
            <Clock class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" />
            <div>
              <p class="font-bold text-amber-900">Akun Belum Ditautkan ke Siswa</p>
              <p class="text-amber-700 mt-0.5 leading-relaxed">
                Akun Orang Tua ini belum terhubung ke data siswa oleh Administrator Sekolah. Silakan hubungi Wali Kelas atau pihak Tata Usaha untuk menautkan akun Anda dengan nomor induk siswa.
              </p>
            </div>
          </div>
        </div>

        <!-- 4 Stat Cards Kehadiran Anak -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
          <StatCard title="Hadir Bulan Ini" :value="data?.stats?.hadir_hari ?? '0 Hari'" :subtext="data?.stats?.hadir_percent ?? 'Bulan Ini'" color="blue" />
          <StatCard title="Sakit" :value="data?.stats?.sakit_hari ?? '0 Hari'" subtext="Disertai Surat" color="blue" />
          <StatCard title="Izin Disetujui" :value="data?.stats?.izin_hari ?? '0 Hari'" subtext="Disetujui Wali Kelas" color="blue" />
          <StatCard title="Alpha" :value="data?.stats?.alpha_hari ?? '0 Hari'" subtext="Tanpa Keterangan" color="blue" />
        </div>

        <!-- Widget Timeline Materi Belajar Anak Hari Ini (Untuk Orang Tua) -->
        <div class="bg-white rounded-2xl border border-gray-150 p-4 sm:p-6 shadow-2xs space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-gray-100 gap-1.5">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-xl bg-blue-50 text-primary flex items-center justify-center shrink-0">
                <Clock class="w-4 h-4" />
              </div>
              <div>
                <h3 class="text-sm sm:text-base font-bold text-gray-900">Timeline Materi Belajar Anak Hari Ini</h3>
                <p class="text-[11px] text-gray-500">Urutan jam pelajaran, materi yang dipelajari putra/putri Anda, dan status keterlaksanaannya</p>
              </div>
            </div>
            <span class="text-xs font-semibold text-primary bg-blue-50 px-3 py-1 rounded-full self-start sm:self-auto border border-blue-200">
              {{ data?.child?.date_formatted || 'Hari Ini' }}
            </span>
          </div>

          <!-- Timeline Items -->
          <div v-if="data?.today_timeline && data.today_timeline.length > 0" class="relative pl-6 sm:pl-8 space-y-4 before:absolute before:left-2.5 sm:before:left-3.5 before:top-3 before:bottom-3 before:w-0.5 before:bg-gray-200">
            <div
              v-for="(item, idx) in data.today_timeline"
              :key="idx"
              class="relative"
            >
              <!-- Timeline Node Dot -->
              <div
                class="absolute -left-6 sm:-left-8 top-2 w-3.5 h-3.5 rounded-full border-2 border-white ring-2 transition"
                :class="item.status === 'TERLAKSANA' ? 'bg-emerald-500 ring-emerald-200' : item.status === 'MENUNGGU_VERIFIKASI' ? 'bg-primary ring-blue-200' : 'bg-gray-300 ring-gray-150'"
              ></div>

              <div class="p-3.5 sm:p-4 rounded-xl border border-gray-150 bg-gray-50/50 hover:bg-white hover:shadow-xs transition space-y-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                  <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-primary/10 text-primary">
                      {{ item.jam_ke }}
                    </span>
                    <span class="text-xs font-mono font-semibold text-gray-700">
                      {{ item.time }}
                    </span>
                    <span class="text-[11px] text-gray-400">
                      • {{ item.room }}
                    </span>
                  </div>

                  <!-- Status Terlaksana Badge -->
                  <span
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold shrink-0"
                    :class="item.status === 'TERLAKSANA' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : item.status === 'MENUNGGU_VERIFIKASI' ? 'bg-blue-50 text-primary border border-blue-200' : 'bg-gray-100 text-gray-600 border border-gray-200'"
                  >
                    <CheckCircle2 v-if="item.status === 'TERLAKSANA'" class="w-3 h-3 text-emerald-600" />
                    <Clock v-else class="w-3 h-3 text-gray-400" />
                    <span>{{ item.status_label }}</span>
                  </span>
                </div>

                <div>
                  <h4 class="text-xs sm:text-sm font-bold text-gray-900">{{ item.subject }}</h4>
                  <p class="text-xs text-gray-500 mt-0.5">
                    Guru Pengajar: <strong class="text-gray-700 font-medium">{{ item.teacher_name }}</strong>
                  </p>
                </div>

                <!-- Materi / Pokok Bahasan -->
                <div class="p-2.5 rounded-lg bg-white border border-gray-150 text-xs">
                  <span class="font-semibold text-gray-500 text-[10px] uppercase block tracking-wider mb-0.5">Materi / Topik KBM:</span>
                  <p class="text-gray-800 font-medium leading-relaxed">
                    {{ item.topic }}
                  </p>
                  <p v-if="item.notes" class="text-[11px] text-gray-500 italic mt-1 pt-1 border-t border-gray-100">
                    Catatan Kelas: {{ item.notes }}
                  </p>

                  <!-- Direct Download Modul Pembelajaran jika ada file -->
                  <div v-if="item.attachment_url" class="pt-2 mt-1.5 border-t border-gray-100">
                    <a
                      :href="item.attachment_url"
                      target="_blank"
                      :download="item.attachment_name || 'Modul_Belajar.pdf'"
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-primary border border-blue-200 text-xs font-bold transition shadow-2xs cursor-pointer active:scale-95 max-w-full"
                    >
                      <Download class="w-3.5 h-3.5 shrink-0" />
                      <span class="truncate">Unduh Modul / Materi ({{ item.attachment_name || 'Berkas' }})</span>
                    </a>
                  </div>
                </div>

                <!-- Alert Siswa Tidak Berada di Jam Ini jika ada -->
                <div v-if="item.absent_students && item.absent_students.length > 0" class="p-2 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-[11px] space-y-0.5">
                  <span class="font-bold flex items-center gap-1">
                    <AlertCircle class="w-3 h-3 text-rose-600" />
                    Siswa tidak berada di kelas pada jam ini ({{ item.absent_students.length }} siswa):
                  </span>
                  <p class="text-rose-700">
                    {{ item.absent_students.map((s: any) => s.name).join(', ') }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else class="text-center py-8 rounded-xl border border-dashed border-gray-200 bg-gray-50/50 space-y-1.5">
            <div class="w-8 h-8 rounded-full bg-blue-50 text-primary mx-auto flex items-center justify-center">
              <Calendar class="w-4 h-4" />
            </div>
            <p class="text-xs font-bold text-gray-700">Tidak Ada Sesi KBM Terjadwal Hari Ini</p>
            <p class="text-[11px] text-gray-400">Jadwal pembelajaran untuk hari ini kosong atau sedang masa jeda KBM.</p>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Jurnal Kegiatan Belajar Anak di Sekolah -->
          <div class="bg-white rounded-2xl border border-gray-150 p-4 sm:p-6 shadow-2xs space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-gray-900">Jurnal Kegiatan Belajar Anak di Sekolah</h3>
                <p class="text-xs text-gray-400">Laporan aktivitas pembelajaran harian siswa dan guru di kelas</p>
              </div>
              <NuxtLink to="/jurnal-pelajaran" class="text-xs text-primary font-semibold hover:underline">
                Lihat Semua
              </NuxtLink>
            </div>

            <div class="space-y-3">
              <div
                v-for="j in data?.recent_journals || []"
                :key="j.id"
                class="p-3.5 rounded-xl border border-gray-100 bg-gray-50/60 space-y-1.5"
              >
                <div class="flex items-center justify-between text-xs">
                  <span class="font-bold text-primary">{{ j.subject }}</span>
                  <span class="text-[11px] text-gray-400">{{ j.date }}</span>
                </div>
                <p class="text-xs font-semibold text-gray-900">{{ j.activity }}</p>
                <p v-if="j.notes" class="text-[11px] text-gray-500">{{ j.notes }}</p>
                <div class="flex items-center justify-between pt-1 text-[10px] text-gray-400">
                  <span>Dilaporkan oleh: <strong class="text-gray-700">{{ j.student_author }}</strong></span>
                  <span>Guru: {{ j.teacher }}</span>
                </div>
              </div>
              <div v-if="!data?.recent_journals?.length" class="text-center py-8 text-xs text-gray-400">
                Belum ada jurnal kegiatan tercatat.
              </div>
            </div>
          </div>

          <!-- Riwayat Kehadiran & Surat Izin Anak -->
          <div class="bg-white rounded-2xl border border-gray-150 p-4 sm:p-6 shadow-2xs space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-gray-900">Riwayat Kehadiran Harian Anak</h3>
                <p class="text-xs text-gray-400">Catatan kehadiran dan surat izin siswa</p>
              </div>
              <NuxtLink to="/surat-izin" class="text-xs text-primary font-semibold hover:underline">
                Riwayat Izin →
              </NuxtLink>
            </div>
            <!-- Mobile Card View -->
            <div class="block md:hidden space-y-2.5">
              <div
                v-for="rec in data?.recent_attendance || []"
                :key="rec.id"
                class="p-3 bg-gray-50/70 border border-gray-100 rounded-xl space-y-1.5"
              >
                <div class="flex items-center justify-between text-xs">
                  <span class="font-bold text-gray-900">{{ rec.date }}</span>
                  <StatusBadge :status="rec.status" />
                </div>
                <p class="text-xs text-gray-500">{{ rec.note || '-' }}</p>
              </div>
              <div v-if="!data?.recent_attendance?.length" class="py-6 text-center text-gray-400 text-xs">
                Belum ada riwayat kehadiran tercatat.
              </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead>
                  <tr class="text-gray-400 uppercase text-[10px] border-b border-gray-100">
                    <th class="pb-3 font-semibold">Hari / Tanggal</th>
                    <th class="pb-3 font-semibold">Status</th>
                    <th class="pb-3 font-semibold">Waktu / Catatan</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                  <tr v-for="rec in data?.recent_attendance || []" :key="rec.id" class="hover:bg-gray-50/50">
                    <td class="py-3 font-medium text-gray-900">{{ rec.date }}</td>
                    <td class="py-3"><StatusBadge :status="rec.status" /></td>
                    <td class="py-3 text-gray-500">{{ rec.note }}</td>
                  </tr>
                  <tr v-if="!data?.recent_attendance?.length">
                    <td colspan="3" class="py-6 text-center text-gray-400">Belum ada riwayat kehadiran tercatat.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </template>

      <!-- ============================================== -->
      <!-- ROLE: SEKRETARIS (Rekap Kelas & Jurnal Kelas)  -->
      <!-- ============================================== -->
      <template v-else-if="authStore.user?.role === 'SEKRETARIS'">
        <!-- Kartu Status Kehadiran Pribadi Sekretaris Sendiri (Dualisme Petugas & Siswa) -->
        <div
          :class="[
            'bg-white rounded-2xl p-4 sm:p-6 shadow-2xs space-y-4 border transition',
            data?.self_attendance?.is_leave_rejected
              ? 'border-rose-200'
              : data?.self_attendance?.is_leave_approved
              ? 'border-orange-200'
              : data?.self_attendance?.has_pending_leave
              ? 'border-amber-200'
              : data?.self_attendance?.status === 'HADIR'
              ? 'border-emerald-200'
              : 'border-blue-150'
          ]"
        >
          <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="space-y-1.5 min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-50 text-primary border border-blue-200">
                  Status Kehadiran Pribadi Anda (Sekretaris Kelas)
                </span>
                <span class="text-xs text-gray-500 font-medium">
                  {{ data?.self_attendance?.date_formatted || 'Hari Ini' }}
                </span>
              </div>
              <div class="flex flex-wrap items-center gap-1.5 pt-1">
                <span class="text-xs font-semibold text-gray-700">Status Kehadiran Anda Hari Ini:</span>
                <StatusBadge :status="data?.self_attendance?.status || 'BELUM_PRESENSI'" />
                <span v-if="data?.self_attendance?.note" class="text-xs text-gray-500 break-words">— {{ data?.self_attendance?.note }}</span>
              </div>
            </div>

            <!-- Quick Action Buttons for Secretary's Personal Attendance -->
            <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-2 w-full lg:w-auto">
              <NuxtLink
                to="/surat-izin"
                class="px-3.5 py-2.5 rounded-xl bg-white border border-gray-200 hover:border-primary text-gray-700 hover:text-primary text-xs font-semibold transition flex items-center justify-center gap-1.5 shadow-2xs w-full sm:w-auto min-h-[44px]"
              >
                <Mail class="w-3.5 h-3.5 text-primary" />
                <span>Ajukan Izin Saya</span>
              </NuxtLink>

              <NuxtLink
                to="/jurnal-pelajaran"
                class="px-3.5 py-2.5 rounded-xl bg-white border border-gray-200 hover:border-primary text-gray-700 hover:text-primary text-xs font-semibold transition flex items-center justify-center gap-1.5 shadow-2xs w-full sm:w-auto min-h-[44px]"
              >
                <BookOpen class="w-3.5 h-3.5 text-primary" />
                <span>Catatan Belajar Saya</span>
              </NuxtLink>
            </div>
          </div>
        </div>

        <!-- 4 Stat Cards Hari Ini -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
          <StatCard title="Hadir" :value="data?.stats?.hadir_count ?? '0 Siswa'" :subtext="data?.stats?.hadir_percent ?? '0% Kehadiran'" color="blue" />
          <StatCard title="Izin" :value="data?.stats?.izin_count ?? '0 Siswa'" subtext="Disetujui Wali Kelas" color="blue" />
          <StatCard title="Sakit" :value="data?.stats?.sakit_count ?? '0 Siswa'" subtext="Disetujui Wali Kelas" color="blue" />
          <StatCard title="Alfa" :value="data?.stats?.alpha_count ?? '0 Siswa'" subtext="Tanpa Keterangan" color="blue" />
        </div>

        <!-- Presensi Kelas Hari Ini Table -->
        <div class="bg-white rounded-2xl border border-gray-150 p-4 sm:p-6 shadow-2xs">
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-4">
            <div>
              <h3 class="text-sm font-bold text-gray-900">Rekap Presensi {{ data?.class_name || 'Kelas' }} Hari Ini</h3>
              <p class="text-xs text-gray-400 mt-0.5">Data terisi dari Presensi Guru Pengajar & Surat Izin yang disetujui Wali Kelas</p>
            </div>
            <div class="flex items-center gap-2">
              <NuxtLink
                to="/jurnal-kelas"
                class="px-3 py-2 bg-white border border-gray-200 hover:border-primary text-gray-700 hover:text-primary rounded-xl text-xs font-semibold flex items-center gap-1.5 shadow-2xs transition"
              >
                <FileText class="w-3.5 h-3.5 text-primary" />
                <span>Agenda KBM Kelas</span>
              </NuxtLink>
              <NuxtLink
                to="/presensi"
                class="px-3.5 py-2 bg-primary hover:bg-primary-hover text-white rounded-xl text-xs font-semibold flex items-center gap-1.5 shadow-2xs transition"
              >
                <ClipboardCheck class="w-4 h-4" />
                <span>Presensi Kelas</span>
              </NuxtLink>
            </div>
          </div>

          <!-- Mobile Card View -->
          <div class="block md:hidden space-y-2.5">
            <div
              v-for="item in data?.attendance_list || []"
              :key="item.student_id"
              class="p-3 bg-gray-50/70 border border-gray-100 rounded-xl space-y-2"
            >
              <div class="flex items-start justify-between gap-2">
                <div>
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="font-bold text-xs text-gray-900">{{ item.name }}</span>
                    <span v-if="item.is_self" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                      Anda
                    </span>
                  </div>
                  <span class="text-[11px] text-gray-400">NIS: {{ item.nis }}</span>
                </div>
                <StatusBadge :status="item.status" />
              </div>

              <div v-if="item.is_rejected_leave" class="text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 p-1.5 rounded-lg">
                Izin Ditolak Walas: {{ item.rejection_reason || 'Wajib verifikasi fisik di kelas' }}
              </div>

              <div class="flex items-center justify-between text-[11px] pt-1 border-t border-gray-100">
                <span class="text-gray-500 truncate max-w-[200px]">{{ item.note || '-' }}</span>
                <span v-if="item.verified" class="inline-flex items-center text-emerald-600 font-semibold gap-1 text-[11px]">
                  <CheckCircle2 class="w-3.5 h-3.5" /> Terverifikasi
                </span>
                <span v-else class="text-gray-400 text-[11px]">-</span>
              </div>
            </div>
            <div v-if="!data?.attendance_list?.length" class="text-center py-6 text-gray-400 text-xs">
              Belum ada data presensi kelas.
            </div>
          </div>

          <!-- Desktop Table View -->
          <div class="hidden md:block overflow-x-auto -mx-2 sm:mx-0">
            <table class="w-full text-left text-xs min-w-[550px]">
              <thead>
                <tr class="text-gray-400 uppercase text-[10px] border-b border-gray-100 font-bold">
                  <th class="pb-3 px-2 font-semibold whitespace-nowrap w-10">No</th>
                  <th class="pb-3 px-2 font-semibold whitespace-nowrap">Nama Siswa</th>
                  <th class="pb-3 px-2 font-semibold whitespace-nowrap">NIS</th>
                  <th class="pb-3 px-2 font-semibold whitespace-nowrap text-center">Status Presensi</th>
                  <th class="pb-3 px-2 font-semibold whitespace-nowrap">Keterangan</th>
                  <th class="pb-3 px-2 font-semibold whitespace-nowrap text-right">Verifikasi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-50">
                <tr v-for="item in data?.attendance_list || []" :key="item.student_id" class="hover:bg-gray-50/50 transition">
                  <td class="py-3 px-2 text-gray-400 whitespace-nowrap">{{ item.no }}</td>
                  <td class="py-3 px-2 font-semibold text-gray-900 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                      <span>{{ item.name }}</span>
                      <span v-if="item.is_self" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                        Anda
                      </span>
                      <span
                        v-if="item.is_rejected_leave"
                        class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200"
                        :title="item.rejection_reason || 'Surat izin ditolak Wali Kelas - Wajib verifikasi fisik di kelas'"
                      >
                        Izin Ditolak Walas
                      </span>
                    </div>
                  </td>
                  <td class="py-3 px-2 text-gray-500 whitespace-nowrap">{{ item.nis }}</td>
                  <td class="py-3 px-2 text-center whitespace-nowrap"><StatusBadge :status="item.status" /></td>
                  <td class="py-3 px-2 text-gray-500 max-w-xs truncate" :title="item.note || '-'">{{ item.note || '-' }}</td>
                  <td class="py-3 px-2 text-right whitespace-nowrap">
                    <span v-if="item.verified" class="inline-flex items-center text-emerald-600 font-semibold gap-1 text-[11px] whitespace-nowrap">
                      <CheckCircle2 class="w-3.5 h-3.5" /> Terverifikasi
                    </span>
                    <span v-else class="text-gray-400 text-[11px] whitespace-nowrap">-</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>

      <!-- ============================================== -->
      <!-- ROLE: GURU PENGAJAR                            -->
      <!-- ============================================== -->
      <template v-else-if="authStore.user?.role === 'GURU_PENGAJAR'">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
          <div class="bg-white rounded-2xl border border-gray-150 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider block">Kelas Diampu</span>
            <div class="text-xl sm:text-2xl font-bold text-gray-900 mt-1">{{ data?.stats?.kelas_diampu || '0 Kelas' }}</div>
            <p class="text-[10px] text-gray-500 mt-0.5 truncate">{{ authStore.user?.specialization || 'Kompetensi Mapel' }}</p>
          </div>
          <div class="bg-white rounded-2xl border border-gray-150 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider block">Jam Mengajar</span>
            <div class="text-xl sm:text-2xl font-bold text-gray-900 mt-1">{{ data?.stats?.jam_mengajar || '0 Jam' }}</div>
            <p class="text-[10px] text-gray-500 mt-0.5 truncate">Hari ini terjadwal</p>
          </div>
          <div class="bg-white rounded-2xl border border-gray-150 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider block">Jurnal Terisi</span>
            <div class="text-xl sm:text-2xl font-bold text-gray-900 mt-1">{{ data?.stats?.jurnal_terisi ?? '0/0' }}</div>
            <p class="text-[10px] text-gray-500 mt-0.5 truncate">Pertemuan KBM</p>
          </div>
          <div class="bg-white rounded-2xl border border-gray-150 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider block">Belum Diisi</span>
            <div class="text-xl sm:text-2xl font-bold text-rose-600 mt-1">{{ data?.stats?.jurnal_belum_diisi ?? '0 Pertemuan' }}</div>
            <p class="text-[10px] text-gray-500 mt-0.5 truncate">Pertemuan Menunggu</p>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Jadwal Mengajar Hari Ini (2 cols) -->
          <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-150 p-4 sm:p-6 shadow-2xs">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-sm font-bold text-gray-900">Jadwal Mengajar Hari Ini</h3>
                <p class="text-xs text-gray-400">5 pertemuan • Total 5 jam mengajar</p>
              </div>
              <NuxtLink to="/jurnal-materi" class="px-3 py-1.5 bg-primary hover:bg-primary-hover text-white rounded-xl text-xs font-semibold">
                + Isi Jurnal Baru
              </NuxtLink>
            </div>

            <!-- Mobile Card View -->
            <div class="block md:hidden space-y-2.5">
              <div
                v-for="sch in data?.schedules || []"
                :key="sch.id"
                class="p-3.5 bg-gray-50/70 border border-gray-100 rounded-xl space-y-2"
              >
                <div class="flex items-center justify-between text-xs">
                  <span class="font-bold text-gray-900">{{ sch.subject }}</span>
                  <span class="font-mono text-[11px] text-gray-500 bg-white px-2 py-0.5 rounded border border-gray-150">{{ sch.time }}</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                  <span class="text-gray-600 font-medium">Kelas: {{ sch.class }}</span>
                  <StatusBadge :status="sch.status" />
                </div>
                <div class="pt-1.5">
                  <NuxtLink
                    to="/jurnal-materi"
                    class="w-full min-h-[44px] flex items-center justify-center bg-white hover:bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold text-gray-700 shadow-2xs transition"
                  >
                    Isi Jurnal
                  </NuxtLink>
                </div>
              </div>
              <div v-if="!data?.schedules?.length" class="text-center py-6 text-gray-400 text-xs">
                Tidak ada jadwal mengajar hari ini.
              </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto -mx-6 px-6">
              <table class="w-full text-left text-xs whitespace-nowrap min-w-[500px]">
                <thead>
                  <tr class="text-gray-400 uppercase text-[10px] border-b border-gray-100">
                    <th class="pb-3 font-semibold">Jam</th>
                    <th class="pb-3 font-semibold">Mata Pelajaran</th>
                    <th class="pb-3 font-semibold">Kelas</th>
                    <th class="pb-3 font-semibold">Status Jurnal</th>
                    <th class="pb-3 font-semibold text-right">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                  <tr v-for="sch in data?.schedules || []" :key="sch.id" class="hover:bg-gray-50/50">
                    <td class="py-3 font-medium text-gray-900">{{ sch.time }}</td>
                    <td class="py-3 font-semibold text-gray-800">{{ sch.subject }}</td>
                    <td class="py-3 text-gray-600">{{ sch.class }}</td>
                    <td class="py-3"><StatusBadge :status="sch.status" /></td>
                    <td class="py-3 text-right">
                      <NuxtLink to="/jurnal-materi" class="inline-flex px-3 py-1 bg-white hover:bg-gray-50 border border-gray-200 rounded-lg text-[11px] font-semibold text-gray-700">
                        Isi Jurnal
                      </NuxtLink>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Ringkasan Presensi & Pengingat (1 col) -->
          <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-gray-150 p-4 sm:p-6 shadow-2xs">
              <h3 class="text-sm font-bold text-gray-900 mb-3">Ringkasan Presensi Kelas</h3>
              <p class="text-xs text-gray-400 mb-4">Persentase kehadiran hari ini</p>
              <div class="space-y-3">
                <div v-for="cls in data?.attendance_summary || []" :key="cls.class">
                  <div class="flex justify-between text-xs font-medium text-gray-700 mb-1">
                    <span>{{ cls.class }}</span>
                    <span>{{ cls.present_ratio }} <span class="font-bold text-gray-900">{{ cls.percent }}%</span></span>
                  </div>
                  <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                    <div class="bg-emerald-500 h-2 rounded-full" :style="{ width: cls.percent + '%' }"></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-150 p-4 sm:p-6 shadow-2xs">
              <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-gray-900">Pengingat Pembelajaran</h3>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                  {{ (data?.reminders || []).length }} Tugas
                </span>
              </div>
              <ul class="space-y-2.5 text-xs text-gray-600">
                <li v-for="(rem, idx) in data?.reminders || []" :key="idx" class="flex items-start gap-2 bg-gray-50/70 p-2.5 rounded-xl border border-gray-100">
                  <span class="w-2 h-2 rounded-full bg-amber-500 mt-1 flex-shrink-0"></span>
                  <div class="flex-1">
                    <p class="font-medium text-gray-800">{{ rem }}</p>
                    <NuxtLink
                      v-if="rem.includes('jurnal belum diisi')"
                      to="/jurnal-materi"
                      class="inline-block mt-1 text-[11px] font-bold text-primary hover:underline"
                    >
                      Buka Form Jurnal KBM →
                    </NuxtLink>
                    <NuxtLink
                      v-else-if="rem.includes('surat izin')"
                      to="/persetujuan-izin"
                      class="inline-block mt-1 text-[11px] font-bold text-primary hover:underline"
                    >
                      Buka Persetujuan Izin →
                    </NuxtLink>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </template>

      <!-- ============================================== -->
      <!-- ROLE: WALI KELAS (eks GURU_PEMBIMBING)         -->
      <!-- ============================================== -->
      <template v-else-if="authStore.user?.role === 'GURU_PEMBIMBING'">
        <!-- 3 Stat Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <StatCard title="Kelas Binaan (Wali Kelas)" :value="data?.stats?.total_kelas ?? '-'" :subtext="data?.stats?.total_siswa ?? 'Semester Ini'" color="blue" />
          <StatCard title="Jurnal Kegiatan Terisi" :value="data?.stats?.jurnal_terisi ?? '0 Sesi'" subtext="Realisasi Hari Ini" color="blue" />
          <StatCard title="Surat Izin Siswa Menunggu" :value="data?.stats?.surat_izin_pending ?? '0 Permohonan'" subtext="Perlu Verifikasi Wali Kelas" color="blue" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Pemantauan Real-time Jurnal Kelas (2 cols) -->
          <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-150 p-4 sm:p-6 shadow-2xs">
            <h3 class="text-sm font-bold text-gray-900 mb-4">Pemantauan Real-time Jurnal & Kehadiran Kelas Binaan</h3>
            <!-- Mobile Card View -->
            <div class="block md:hidden space-y-2.5">
              <div
                v-for="cls in data?.monitored_classes || []"
                :key="cls.id"
                class="p-3 bg-gray-50/70 border border-gray-100 rounded-xl space-y-2"
              >
                <div class="flex items-center justify-between text-xs">
                  <span class="font-bold text-gray-900">{{ cls.class }}</span>
                  <span class="font-bold text-gray-800">{{ cls.attendance_percent }}</span>
                </div>
                <div class="flex items-center justify-between text-[11px]">
                  <span class="text-gray-500">Sekretaris: {{ cls.secretary }}</span>
                  <StatusBadge :status="cls.journal_status" />
                </div>
              </div>
              <div v-if="!data?.monitored_classes?.length" class="text-center py-6 text-gray-400 text-xs">
                Belum ada kelas binaan.
              </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead>
                  <tr class="text-gray-400 uppercase text-[10px] border-b border-gray-100">
                    <th class="pb-3 font-semibold">Kelas</th>
                    <th class="pb-3 font-semibold">Sekretaris</th>
                    <th class="pb-3 font-semibold">Status Jurnal</th>
                    <th class="pb-3 font-semibold text-right">Kehadiran</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                  <tr v-for="cls in data?.monitored_classes || []" :key="cls.id" class="hover:bg-gray-50/50">
                    <td class="py-3 font-bold text-gray-900">{{ cls.class }}</td>
                    <td class="py-3 text-gray-600">{{ cls.secretary }}</td>
                    <td class="py-3"><StatusBadge :status="cls.journal_status" /></td>
                    <td class="py-3 text-right font-semibold text-gray-800">{{ cls.attendance_percent }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Monitoring Kehadiran Kelas Per Jam Pelajaran (Realtime untuk Walas) -->
            <div class="mt-6 pt-5 border-t border-gray-100 space-y-3">
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="text-xs sm:text-sm font-bold text-gray-900 flex items-center gap-1.5">
                    <Clock class="w-4 h-4 text-primary" />
                    <span>Kehadiran Kelas Per Jam Pelajaran (Hari Ini)</span>
                  </h4>
                  <p class="text-[11px] text-gray-400 mt-0.5">Pantau kehadiran murid per jam mapel dan deteksi jika ada siswa yang cabut di jam tertentu</p>
                </div>
                <span class="text-[11px] font-bold text-primary bg-blue-50 border border-blue-200 px-2.5 py-0.5 rounded-full">
                  {{ data?.periods_attendance?.length || 0 }} Jam KBM
                </span>
              </div>

              <div v-if="!data?.periods_attendance?.length" class="text-center py-4 text-gray-400 text-xs">
                Tidak ada jadwal KBM aktif untuk hari ini.
              </div>

              <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                <div
                  v-for="p in data?.periods_attendance"
                  :key="p.id"
                  class="p-3.5 rounded-xl border transition"
                  :class="p.is_current ? 'border-blue-400 bg-blue-50/20 ring-1 ring-blue-300' : 'border-gray-200 bg-white'"
                >
                  <div class="flex items-center justify-between text-xs pb-1.5 border-b border-gray-100">
                    <span class="font-bold text-gray-900 flex items-center gap-1">
                      <Clock class="w-3.5 h-3.5 text-gray-400" />
                      {{ p.time }}
                    </span>
                    <span
                      class="px-2 py-0.5 rounded-md text-[10px] font-bold"
                      :class="p.is_current ? 'bg-blue-100 text-blue-800 animate-pulse' : (p.status_kbm === 'Selesai' ? 'bg-gray-100 text-gray-600' : 'bg-amber-50 text-amber-700')"
                    >
                      {{ p.status_kbm }}
                    </span>
                  </div>

                  <div class="pt-2 space-y-1 text-xs">
                    <div class="font-bold text-gray-800 truncate">{{ p.subject }}</div>
                    <div class="text-[11px] text-gray-500">Guru: {{ p.teacher }}</div>

                    <!-- Kehadiran Status Jam Ini -->
                    <div class="pt-1.5 flex items-center justify-between text-[11px]">
                      <span v-if="p.absent_count === 0" class="inline-flex items-center gap-1 font-bold text-emerald-600">
                        <CheckCircle2 class="w-3 h-3" /> Lengkap (Tidak ada cabut)
                      </span>
                      <span v-else class="inline-flex items-center gap-1 font-bold text-rose-600">
                        <AlertCircle class="w-3 h-3" /> {{ p.absent_count }} Siswa Cabut Jam Ini
                      </span>
                      <span class="text-gray-400 font-mono">{{ p.present_count }} Hadir</span>
                    </div>

                    <!-- Siswa Cabut Names jika ada -->
                    <div v-if="p.absent_students && p.absent_students.length > 0" class="pt-1 flex flex-wrap gap-1">
                      <span
                        v-for="std in p.absent_students"
                        :key="std.id"
                        class="px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 text-[10px] font-semibold border border-rose-200"
                      >
                        {{ std.name }}
                      </span>
                    </div>

                    <!-- Presensi Siswa yang Dicatat Guru Pengajar Jam Ini (Sesuai Wireframe) -->
                    <div v-if="p.student_attendance && p.student_attendance.length > 0" class="pt-2 border-t border-gray-100 space-y-1">
                      <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block">Presensi Murid Jam Ini:</span>
                      <div class="flex flex-wrap gap-1.5">
                        <span
                          v-for="std in p.student_attendance"
                          :key="std.id"
                          class="px-2 py-0.5 rounded-md text-[10px] font-bold border inline-flex items-center gap-1"
                          :class="std.status === 'HADIR' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (std.status === 'IZIN' ? 'bg-blue-50 text-blue-700 border-blue-200' : (std.status === 'SAKIT' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200'))"
                        >
                          {{ std.name }}: {{ std.status }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Persetujuan Surat Izin Cards (1 col) -->
          <div class="bg-white rounded-2xl border border-gray-150 p-4 sm:p-6 shadow-2xs">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-sm font-bold text-gray-900">Persetujuan Surat Izin Siswa</h3>
              <span class="px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-[10px] font-bold">
                {{ data?.pending_leaves?.length || 0 }} Baru
              </span>
            </div>

            <div class="space-y-4">
              <div
                v-for="lv in data?.pending_leaves || []"
                :key="lv.id"
                class="border border-gray-150 rounded-xl p-4 space-y-3 bg-gray-50/30"
              >
                <div class="flex items-center justify-between">
                  <div>
                    <h4 class="text-xs font-bold text-gray-900">{{ lv.student_name }}</h4>
                    <p class="text-[10px] text-gray-400">{{ lv.class }}</p>
                  </div>
                  <StatusBadge :status="lv.type" />
                </div>
                <p class="text-xs text-gray-600 line-clamp-2">{{ lv.reason }}</p>
                <div class="flex items-center gap-2 pt-1">
                  <button
                    @click="handleQuickReview(lv.id, 'APPROVED')"
                    class="flex-1 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold transition-colors cursor-pointer"
                  >
                    Setujui
                  </button>
                  <button
                    @click="handleQuickReview(lv.id, 'REJECTED')"
                    class="flex-1 py-1.5 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 rounded-lg text-xs font-semibold transition-colors cursor-pointer"
                  >
                    Tolak
                  </button>
                </div>
              </div>

              <div v-if="!data?.pending_leaves?.length" class="text-center py-6 text-gray-400 text-xs">
                Tidak ada permohonan surat izin yang pending.
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- ============================================== -->
      <!-- ROLE: SUPERADMIN (Pusat Pengawasan Akademik)    -->
      <!-- ============================================== -->
      <template v-else-if="authStore.user?.role === 'SUPERADMIN'">
        <!-- 6 Academic Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-2.5 sm:gap-4">
          <StatCard title="Wali Kelas" :value="data?.stats?.total_guru_pembimbing ?? '0'" subtext="Wali Kelas Aktif" color="blue" />
          <StatCard title="Guru Pengajar" :value="data?.stats?.total_guru_pengajar ?? '0'" subtext="Pengampu Mapel" color="blue" />
          <StatCard title="Total Siswa" :value="data?.stats?.total_siswa ?? '0'" subtext="Terdata di Kelas" color="blue" />
          <StatCard title="Total Kelas" :value="data?.stats?.total_kelas ?? '0'" subtext="Kelas Terdaftar" color="blue" />
          <StatCard title="Rata-rata Presensi" :value="data?.stats?.kehadiran_rata_rata ?? '0%'" subtext="Hari Ini" color="blue" />
          <StatCard title="Jurnal Terisi" :value="data?.stats?.jurnal_terisi_today ?? '0'" subtext="Hari Ini" color="blue" />
        </div>

        <!-- 2 Column Master Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Left: Pemantauan Seluruh Kelas (2 Cols) -->
          <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-150 p-4 sm:p-6 shadow-2xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div>
                <h3 class="text-sm font-bold text-gray-900">Pemantauan Presensi & Jurnal Kelas Hari Ini</h3>
                <p class="text-xs text-gray-400 mt-0.5">Pantau status pengisian presensi dan jurnal seluruh kelas</p>
              </div>

              <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50 text-primary border border-blue-200 text-xs font-bold shrink-0 self-start sm:self-center">
                <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                <span>Kelas Aktif</span>
              </div>
            </div>

            <!-- Mobile Card View -->
            <div class="block md:hidden space-y-2.5">
              <div
                v-for="cls in filteredClassesMonitoring"
                :key="cls.id"
                class="p-3.5 bg-gray-50/70 border border-gray-150 rounded-xl space-y-2.5"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-1.5">
                    <span class="font-bold text-sm text-gray-900">{{ cls.class_name }}</span>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-primary border border-blue-200">
                      {{ cls.major || 'UMUM' }}
                    </span>
                  </div>
                  <div class="text-right">
                    <span class="text-xs font-bold text-gray-800">{{ cls.attendance_percent }}%</span>
                    <span class="block text-[10px] text-gray-400">{{ cls.present_ratio }}</span>
                  </div>
                </div>

                <div class="text-xs text-gray-600 space-y-0.5">
                  <p><span class="text-gray-400">Wali Kelas:</span> {{ cls.advisor_name }}</p>
                  <p><span class="text-gray-400">Sekretaris:</span> {{ cls.secretary_name }}</p>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-gray-200/60 text-xs">
                  <div class="flex items-center gap-2">
                    <span
                      v-if="cls.session_status === 'LOCKED'"
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-50 text-red-700 border border-red-200 inline-flex items-center gap-1"
                    >
                      <Lock class="w-3 h-3 text-red-600 shrink-0" /> Terkunci
                    </span>
                    <span
                      v-else-if="cls.session_status === 'DRAFT'"
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 inline-flex items-center gap-1"
                    >
                      <FileEdit class="w-3 h-3 text-amber-600 shrink-0" /> Aktif
                    </span>
                    <span
                      v-else
                      class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-50 text-gray-500 border border-gray-200 inline-flex items-center gap-1"
                    >
                      <Clock class="w-3 h-3 text-gray-400 shrink-0" /> Belum Buka
                    </span>
                    <StatusBadge :status="cls.journal_status" />
                  </div>

                  <button
                    v-if="cls.session_status === 'LOCKED'"
                    @click="handleForceUnlock(cls.id)"
                    class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-lg text-xs font-bold transition cursor-pointer min-h-[44px] inline-flex items-center gap-1"
                  >
                    Buka Kunci
                  </button>
                </div>
              </div>
              <div v-if="!filteredClassesMonitoring?.length" class="text-center py-6 text-gray-400 text-xs">
                Tidak ada data pemantauan kelas.
              </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto -mx-2 sm:mx-0">
              <table class="w-full text-left text-xs border-collapse min-w-[620px]">
                <thead>
                  <tr class="text-gray-400 uppercase text-[10px] border-b border-gray-100 font-bold bg-gray-50/50">
                    <th class="py-2.5 px-3 whitespace-nowrap">Kelas</th>
                    <th class="py-2.5 px-3 whitespace-nowrap">Jurusan</th>
                    <th class="py-2.5 px-3 whitespace-nowrap">Wali Kelas</th>
                    <th class="py-2.5 px-3 whitespace-nowrap">Sekretaris</th>
                    <th class="py-2.5 px-3 whitespace-nowrap text-center">Status Sesi</th>
                    <th class="py-2.5 px-3 whitespace-nowrap text-right">Kehadiran</th>
                    <th class="py-2.5 px-3 whitespace-nowrap text-center">Jurnal KBM</th>
                    <th class="py-2.5 px-3 whitespace-nowrap text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                  <tr v-for="cls in filteredClassesMonitoring" :key="cls.id" class="hover:bg-gray-50/70 transition">
                    <td class="py-3 px-3 font-bold text-gray-900 whitespace-nowrap">{{ cls.class_name }}</td>
                    <td class="py-3 px-3 whitespace-nowrap">
                      <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-primary border border-blue-200 whitespace-nowrap">
                        {{ cls.major || 'UMUM' }}
                      </span>
                    </td>
                    <td class="py-3 px-3 text-gray-600 truncate max-w-[130px] whitespace-nowrap" :title="cls.advisor_name">{{ cls.advisor_name }}</td>
                    <td class="py-3 px-3 text-gray-600 truncate max-w-[110px] whitespace-nowrap" :title="cls.secretary_name">{{ cls.secretary_name }}</td>
                    <td class="py-3 px-3 text-center whitespace-nowrap">
                      <span
                        v-if="cls.session_status === 'LOCKED'"
                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-red-50 text-red-700 border border-red-200 whitespace-nowrap inline-flex items-center gap-1"
                      >
                        <Lock class="w-3 h-3 text-red-600 shrink-0" /><span>Terkunci</span>
                      </span>
                      <span
                        v-else-if="cls.session_status === 'DRAFT'"
                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 whitespace-nowrap inline-flex items-center gap-1"
                      >
                        <FileEdit class="w-3 h-3 text-amber-600 shrink-0" /><span>Aktif</span>
                      </span>
                      <span
                        v-else
                        class="px-2.5 py-0.5 rounded-full text-[10px] font-medium bg-gray-50 text-gray-500 border border-gray-200 whitespace-nowrap inline-flex items-center gap-1"
                      >
                        <Clock class="w-3 h-3 text-gray-400 shrink-0" /><span>Belum Buka</span>
                      </span>
                    </td>
                    <td class="py-3 px-3 text-right font-bold text-gray-800 whitespace-nowrap">
                      {{ cls.attendance_percent }}%
                      <span class="block text-[10px] font-normal text-gray-400 whitespace-nowrap">{{ cls.present_ratio }}</span>
                    </td>
                    <td class="py-3 px-3 text-center whitespace-nowrap">
                      <StatusBadge :status="cls.journal_status" />
                    </td>
                    <td class="py-3 px-3 text-center whitespace-nowrap">
                      <button
                        v-if="cls.session_status === 'LOCKED'"
                        @click="handleForceUnlock(cls.id)"
                        class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-lg text-[10px] font-bold transition cursor-pointer whitespace-nowrap inline-flex items-center gap-1"
                        title="Buka kunci sesi presensi agar dapat diperbaiki"
                      >
                        Buka Kunci
                      </button>
                      <span v-else class="text-gray-300 text-xs">—</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <!-- Right: Live Global Audit Trail (1 Col) -->
          <div class="space-y-6">

            <div class="bg-white rounded-2xl border border-gray-150 p-4 sm:p-6 shadow-2xs space-y-3">
              <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="text-sm font-bold text-gray-900">Aktivitas Terkini Sistem</h3>
                <NuxtLink to="/pengaturan-sistem" class="text-[11px] text-primary font-bold hover:underline">
                  Semua Log
                </NuxtLink>
              </div>

              <div class="space-y-3">
                <div
                  v-for="log in data?.recent_audit_logs || []"
                  :key="log.id"
                  class="text-xs border-l-2 border-primary pl-3 py-1 space-y-0.5"
                >
                  <div class="flex items-center justify-between text-[10px] text-gray-400">
                    <span class="font-bold text-gray-800">{{ log.user }}</span>
                    <span>{{ log.time }}</span>
                  </div>
                  <p class="text-gray-600 text-[11px] leading-tight">{{ log.action }}</p>
                  <p class="text-[10px] text-gray-400 font-mono">IP: {{ log.ip_address }}</p>
                </div>

                <div v-if="!data?.recent_audit_logs?.length" class="text-center py-6 text-gray-400 text-xs">
                  Belum ada log aktivitas tercatat.
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </main>
  </div>
</template>

<script setup lang="ts">
import {
  Lock,
  FileEdit,
  Clock,
  ClipboardCheck,
  CheckCircle2,
  XCircle,
  Mail,
  FileText,
  Calendar,
  AlertCircle,
  Download,
} from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useToastStore } from '~/stores/toast'

const authStore = useAuthStore()
const toast = useToastStore()
const data = ref<any>(null)
const isLoading = ref(true)
const isCheckingIn = ref(false)
const checkInMessage = ref('')
const selectedMajorFilter = ref('ALL')
const studentJournalTab = ref<'journals' | 'materials'>('journals')
const isSwitchingRole = ref(false)

async function handleSwitchRole(targetRole: 'GURU_PENGAJAR' | 'GURU_PEMBIMBING') {
  if (authStore.user?.role === targetRole || isSwitchingRole.value) return
  isSwitchingRole.value = true
  try {
    await authStore.switchRole(targetRole)
  } catch (err: any) {
    toast.error(err?.data?.message || 'Gagal beralih mode peran.')
  } finally {
    isSwitchingRole.value = false
  }
}

if (process.client) {
  try {
    const cached = localStorage.getItem(`cache_dashboard_${authStore.user?.role || 'default'}`)
    if (cached) {
      data.value = JSON.parse(cached)
      isLoading.value = false
    }
  } catch (e) {
    // ignore
  }
}

const filteredClassesMonitoring = computed(() => {
  if (!data.value?.classes_monitoring) return []
  if (selectedMajorFilter.value === 'ALL') return data.value.classes_monitoring
  return data.value.classes_monitoring.filter((c: any) => {
    let m = c.major
    if (m && (m.toLowerCase().includes('rekayasa') || m.toLowerCase().includes('perangkat'))) {
      m = 'RPL'
    }
    return m === selectedMajorFilter.value
  })
})

const dashboardTitle = computed(() => {
  const role = authStore.user?.role
  if (role === 'SUPERADMIN') return 'Dashboard Super Administrator'
  if (role === 'SISWA') return 'Dashboard Siswa'
  if (role === 'ORANG_TUA') return 'Dashboard Pantauan Orang Tua Siswa'
  if (role === 'SEKRETARIS') return 'Dashboard Presensi ' + (data.value?.class_name || 'Kelas')
  if (role === 'GURU_PENGAJAR') return 'Dashboard Guru Pengajar'
  if (role === 'GURU_PEMBIMBING') return 'Dashboard Wali Kelas'
  return 'Dashboard'
})

const dashboardSubtitle = computed(() => '')

function getAuthToken(): string | null {
  if (authStore.token) return authStore.token
  if (process.client) {
    const saved = localStorage.getItem('token')
    if (saved) {
      authStore.token = saved
      return saved
    }
  }
  return null
}

async function loadDashboard(silent = false) {
  let token = getAuthToken()
  if (!token) {
    await new Promise(r => setTimeout(r, 100))
    token = getAuthToken()
  }
  if (!token) return

  if (!silent && !data.value) {
    isLoading.value = true
  }

  try {
    const res = await $fetch<any>(`${authStore.apiBase}/dashboard`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    data.value = res
    if (process.client) {
      localStorage.setItem(`cache_dashboard_${authStore.user?.role || 'default'}`, JSON.stringify(res))
    }
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

async function handleSelfCheckIn() {
  isCheckingIn.value = true
  checkInMessage.value = ''
  try {
    const res: any = await $fetch(`${authStore.apiBase}/attendance/check-in`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {}
    })
    checkInMessage.value = res?.message || 'Presensi Mandiri berhasil dicatat!'
    toast.success(checkInMessage.value)
    await loadDashboard(true)
  } catch (e: any) {
    toast.error(e?.data?.message || 'Gagal melakukan presensi mandiri.')
  } finally {
    isCheckingIn.value = false
  }
}

async function handleForceUnlock(classId: number) {
  const ok = await confirmDialog({
    title: 'Buka Kunci Presensi?',
    message: 'Buka kunci sesi presensi kelas ini kembali ke status DRAFT agar dapat diedit kembali oleh pengurus kelas?',
    confirmText: 'Buka Kunci Presensi',
    cancelText: 'Batal',
    type: 'warning',
  })
  if (!ok) return
  try {
    const today = getLocalTodayYMD()
    await $fetch(`${authStore.apiBase}/attendance/force-unlock`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: { class_id: classId, date: today }
    })
    toast.success('Sesi presensi berhasil dibuka kembali.')
    loadDashboard()
  } catch (e: any) {
    toast.error(e?.data?.message || 'Gagal membuka kunci sesi presensi.')
  }
}

async function handleQuickReview(id: number, status: 'APPROVED' | 'REJECTED') {
  try {
    await $fetch(`${authStore.apiBase}/leave-requests/${id}/review`, {
      method: 'PUT',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: { status, review_note: status === 'APPROVED' ? 'Disetujui oleh Wali Kelas' : 'Ditolak oleh Wali Kelas' }
    })
    loadDashboard()
  } catch (e) {
    toast.error('Gagal memperbarui status izin.')
  }
}

onMounted(() => {
  loadDashboard()
})
</script>
