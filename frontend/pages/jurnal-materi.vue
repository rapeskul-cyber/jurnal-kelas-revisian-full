<template>
  <div class="flex-1 flex flex-col">
    <AppHeader
      title="Jurnal KBM Terpadu"
    />

    <main class="px-4 sm:px-6 md:px-8 pb-10 space-y-6">
      <!-- Alert / Success Toast -->
      <div v-if="successMsg" class="p-4 rounded-2xl bg-green-50 border border-green-200 text-green-700 flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-2 text-xs font-semibold">
          <CheckCircle2Icon class="w-4 h-4 text-green-600 shrink-0" />
          <span>{{ successMsg }}</span>
        </div>
        <button @click="successMsg = ''" class="text-green-600 hover:text-green-800 text-xs font-bold cursor-pointer">
          <XIcon class="w-3.5 h-3.5" />
        </button>
      </div>

      <!-- Top Tab Switcher (Unifikasi Menu Guru) -->
      <div class="flex items-center gap-1.5 p-1.5 bg-gray-100 rounded-2xl w-full sm:w-fit shadow-2xs">
        <button
          type="button"
          @click="switchMainTab('input')"
          class="flex-1 sm:flex-initial flex items-center justify-center gap-2 px-3 sm:px-4 py-2.5 rounded-xl text-xs font-bold transition min-h-[44px] cursor-pointer"
          :class="activeMainTab === 'input' ? 'bg-white text-primary shadow-xs' : 'text-gray-600 hover:text-gray-900'"
        >
          <CalendarIcon class="w-4 h-4 shrink-0" />
          <span class="hidden sm:inline">Jadwal & Input Materi Hari Ini</span>
          <span class="sm:hidden">Jadwal & Input</span>
        </button>
        <button
          type="button"
          @click="switchMainTab('monitoring')"
          class="flex-1 sm:flex-initial flex items-center justify-center gap-2 px-3 sm:px-4 py-2.5 rounded-xl text-xs font-bold transition min-h-[44px] cursor-pointer"
          :class="activeMainTab === 'monitoring' ? 'bg-white text-primary shadow-xs' : 'text-gray-600 hover:text-gray-900'"
        >
          <BarChart2Icon class="w-4 h-4 shrink-0" />
          <span class="hidden sm:inline">Monitoring & Rekap Kelas</span>
          <span class="sm:hidden">Monitoring</span>
        </button>
      </div>

      <!-- ============================================== -->
      <!-- TAB 1: JADWAL & INPUT MATERI HARI INI          -->
      <!-- ============================================== -->
      <div v-if="activeMainTab === 'input'" class="space-y-6">
        <!-- Top 3 Stat Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-5">
          <!-- Total Kelas Diampu -->
          <div class="bg-white rounded-2xl border border-gray-150 p-4 sm:p-5 shadow-card flex items-start justify-between">
            <div class="min-w-0 flex-1 pr-2">
              <span class="text-xs font-semibold text-gray-500">Total Kelas Diampu</span>
              <div class="text-2xl font-bold text-gray-900 mt-1">{{ taughtClassesList.length }} Kelas</div>
              <p class="text-[11px] text-gray-400 mt-1 truncate" :title="taughtClassesList.join(', ')">
                {{ taughtClassesList.length > 0 ? taughtClassesList.join(', ') : 'Belum ada kelas yang diampu' }}
              </p>
            </div>
            <div class="w-8 h-1 rounded-full bg-blue-500 mt-1 shrink-0"></div>
          </div>

          <!-- Jurnal Hari Ini -->
          <div class="bg-white rounded-2xl border border-gray-150 p-4 sm:p-5 shadow-card flex items-start justify-between">
            <div class="min-w-0 flex-1 pr-2">
              <span class="text-xs font-semibold text-gray-500">Jurnal Hari Ini</span>
              <div class="text-2xl font-bold text-gray-900 mt-1">{{ filledCount }} / {{ scheduleItems.length }} Terisi</div>
              <p class="text-[11px] text-gray-400 mt-1">
                {{ scheduleItems.length > 0 ? Math.round((filledCount / scheduleItems.length) * 100) : 0 }}% Kegiatan Belajar Terdata
              </p>
            </div>
            <div class="w-8 h-1 rounded-full bg-amber-500 mt-1 shrink-0"></div>
          </div>

          <!-- Menunggu Verifikasi -->
          <div class="bg-white rounded-2xl border border-gray-150 p-4 sm:p-5 shadow-card flex items-start justify-between">
            <div class="min-w-0 flex-1 pr-2">
              <span class="text-xs font-semibold text-gray-500">Menunggu Verifikasi</span>
              <div class="text-2xl font-bold text-gray-900 mt-1">{{ pendingCount }} Jurnal</div>
              <p class="text-[11px] text-gray-400 mt-1">Perlu Konfirmasi / Paraf Guru</p>
            </div>
            <div class="w-8 h-1 rounded-full bg-primary mt-1 shrink-0"></div>
          </div>
        </div>

        <!-- Main Content: Jadwal Mengajar & Status Jurnal -->
        <div class="bg-white rounded-2xl border border-gray-150 shadow-card p-4 sm:p-6 space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3.5 border-b border-gray-100 gap-2.5">
            <div>
              <h2 class="text-sm sm:text-base font-bold text-gray-900 tracking-tight leading-snug">Jadwal Mengajar & Status Jurnal</h2>
              <p class="text-[11px] text-gray-400 mt-0.5">Daftar kelas aktif, input materi sesi, dan verifikasi KBM</p>
            </div>
            <div class="flex items-center gap-2 self-start sm:self-auto flex-wrap">
              <button
                type="button"
                @click="openUploadMaterialModal = true"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-primary bg-primary text-white hover:bg-primary-hover text-xs font-semibold shadow-2xs transition cursor-pointer whitespace-nowrap active:scale-95"
              >
                <UploadCloudIcon class="w-3.5 h-3.5" />
                <span>Unggah Modul Belajar</span>
              </button>
              <button
                type="button"
                @click="openAddScheduleModal = true"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-gray-200 hover:border-primary bg-white hover:bg-primary/5 text-gray-700 hover:text-primary text-xs font-semibold shadow-2xs transition cursor-pointer whitespace-nowrap"
              >
                <PlusIcon class="w-3.5 h-3.5 text-primary" />
                <span>Tambah Jadwal</span>
              </button>
            </div>
          </div>

          <!-- Filter Hari Pembelajaran -->
          <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
            <span class="text-[11px] font-semibold text-gray-400 mr-1 shrink-0">Filter Hari:</span>
            <button
              v-for="day in ['Hari Ini', 'Semua', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']"
              :key="day"
              type="button"
              @click="setDayFilter(day)"
              class="px-2.5 py-1 rounded-lg font-medium transition shrink-0 cursor-pointer text-xs"
              :class="activeDayFilter === day ? 'bg-primary text-white shadow-2xs font-semibold' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
            >
              {{ day }}
            </button>
          </div>

          <div class="space-y-3.5">
            <!-- Empty State jika tidak ada jadwal -->
            <div
              v-if="scheduleItems.length === 0"
              class="py-12 px-4 text-center rounded-2xl border border-dashed border-gray-200 bg-gray-50/50 space-y-2"
            >
              <div class="w-10 h-10 mx-auto rounded-full bg-blue-50 text-primary flex items-center justify-center">
                <CalendarIcon class="w-5 h-5" />
              </div>
              <p class="text-xs font-bold text-gray-700">Tidak Ada Jadwal Mengajar</p>
              <p class="text-[11px] text-gray-400 max-w-xs mx-auto">
                {{ activeDayFilter === 'Hari Ini' ? 'Anda tidak memiliki jadwal KBM aktif untuk hari ini (' + currentDayName + ').' : 'Belum ada jadwal KBM yang terdaftar untuk filter ini.' }}
              </p>
            </div>

            <div
              v-else
              v-for="item in scheduleItems"
              :key="item.id"
              class="p-4 sm:p-5 rounded-2xl border transition duration-150 space-y-3"
              :class="item.is_current_slot ? 'border-blue-400 bg-blue-50/20 shadow-md ring-2 ring-blue-400/40' : 'border-gray-150 bg-white hover:border-gray-300 hover:shadow-xs'"
            >
              <!-- Baris Atas: Info Jadwal dan Status Badge -->
              <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 pb-3 border-b border-gray-100">
                <div class="space-y-1.5 min-w-0 flex-1">
                  <!-- Waktu & Active Indicator -->
                  <div class="flex items-center gap-2 text-xs flex-wrap">
                    <span class="text-gray-400 font-medium text-[11px] w-20 shrink-0">Waktu</span>
                    <span class="text-gray-300">•</span>
                    <span class="inline-flex items-center gap-1.5 font-bold text-gray-800 tracking-tight">
                      <ClockIcon class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                      {{ item.time }}
                    </span>
                    <span
                      v-if="item.is_current_slot"
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-700 border border-blue-300 animate-pulse"
                    >
                      🔵 Sedang Berlangsung (Jam Pelajaran Ini)
                    </span>
                  </div>

                  <!-- Mata Pelajaran & Kelas -->
                  <div class="flex items-center gap-2 text-xs">
                    <span class="text-gray-400 font-medium text-[11px] w-20 shrink-0">Mata Pelajaran</span>
                    <span class="text-gray-300">•</span>
                    <span class="font-bold text-gray-900 text-xs sm:text-sm truncate">
                      {{ item.subject }}
                    </span>
                  </div>
                </div>

                <!-- Status Badge -->
                <div class="flex items-center gap-2 shrink-0">
                  <span
                    v-if="item.status === 'Terverifikasi'"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                  >
                    <CheckCircle2Icon class="w-3.5 h-3.5 text-emerald-600" />
                    <span>Terverifikasi</span>
                  </span>

                  <span
                    v-else-if="item.status.includes('Menunggu')"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200"
                  >
                    <ClockIcon class="w-3.5 h-3.5 text-amber-600" />
                    <span>Menunggu Paraf Guru</span>
                  </span>

                  <span
                    v-else
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-600 border border-gray-200"
                  >
                    <AlertCircleIcon class="w-3.5 h-3.5 text-gray-400" />
                    <span>Belum Diisi</span>
                  </span>
                </div>
              </div>

              <!-- Baris Bawah: Info Topik Ringkas, Modul Terdeteksi & Tombol Aksi -->
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-0.5">
                <div class="text-xs text-gray-500 min-w-0 flex-1 space-y-1.5">
                  <p v-if="item.topic" class="truncate text-[11px]">
                    <span class="font-semibold text-gray-700">Topik Materi:</span> {{ item.topic }}
                  </p>
                  <p v-else class="text-[11px] text-gray-400 italic">
                    Belum ada topik materi yang diinput hari ini.
                  </p>

                  <!-- Modul Belajar Terdeteksi Hari Ini -->
                  <div v-if="item.has_material" class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                      <BookOpenIcon class="w-3 h-3 text-emerald-600" />
                      <span>Modul Terdeteksi Hari Ini: {{ item.material_topic }}</span>
                    </span>
                    <a
                      v-if="item.material_url"
                      :href="item.material_url"
                      target="_blank"
                      class="inline-flex items-center gap-1 text-[11px] font-bold text-primary hover:underline cursor-pointer"
                    >
                      <DownloadIcon class="w-3 h-3" /> Unduh Dokumen
                    </a>
                  </div>
                </div>

                <!-- Tombol Aksi: jika sudah diisi berubah menjadi Detail Jurnal / Lihat Jurnal -->
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 shrink-0 w-full sm:w-auto justify-end sm:justify-start pt-1 sm:pt-0">
                  <!-- Jika Belum Diisi -> Tombol Isi Jurnal KBM -->
                  <button
                    v-if="item.status === 'Belum Diisi'"
                    type="button"
                    @click="openFillJournal(item)"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-primary bg-primary text-white hover:bg-primary-hover text-xs font-bold transition cursor-pointer shadow-2xs whitespace-nowrap active:scale-95"
                  >
                    <PlusIcon class="w-3.5 h-3.5 text-white" />
                    <span>Isi Jurnal KBM</span>
                  </button>

                  <!-- Jika Sudah Diisi -> Tombol Lihat Detail Jurnal -->
                  <button
                    v-else
                    type="button"
                    @click="openJournalDetail(item)"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-primary/20 bg-primary/10 hover:bg-primary text-primary hover:text-white text-xs font-bold transition cursor-pointer shadow-2xs whitespace-nowrap active:scale-95"
                  >
                    <EyeIcon class="w-3.5 h-3.5" />
                    <span>Lihat Detail Jurnal</span>
                  </button>

                  <!-- Edit Jurnal (jika Menunggu) -->
                  <button
                    v-if="item.status.includes('Menunggu')"
                    type="button"
                    @click="openFillJournal(item)"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100 text-xs font-semibold transition cursor-pointer shadow-2xs whitespace-nowrap"
                  >
                    <Edit2Icon class="w-3.5 h-3.5 text-amber-700" />
                    <span>Edit</span>
                  </button>

                  <!-- Konfirmasi Paraf (jika Menunggu) -->
                  <button
                    v-if="item.status.includes('Menunggu')"
                    type="button"
                    @click="openConfirmModal(item)"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-emerald-600 bg-emerald-600 text-white hover:bg-emerald-700 text-xs font-bold transition cursor-pointer shadow-2xs whitespace-nowrap active:scale-95"
                  >
                    <CheckIcon class="w-3.5 h-3.5" />
                    <span>Paraf / Sahkan</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ============================================== -->
      <!-- TAB 2: MONITORING & REKAP KELAS (ROMBEL)       -->
      <!-- ============================================== -->
      <div v-else-if="activeMainTab === 'monitoring'" class="space-y-6">
        <!-- Control Bar: Filter Kelas & Tanggal -->
        <div class="bg-white rounded-2xl border border-gray-150 p-4 sm:p-5 shadow-2xs space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100">
            <div>
              <h3 class="text-sm sm:text-base font-bold text-gray-900">Monitoring Riwayat KBM & Presensi Rombel</h3>
              <p class="text-[11px] text-gray-500">Pilih kelas/rombel untuk memantau kehadiran siswa dan riwayat agenda KBM</p>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-xs text-gray-400 font-medium">Terakhir diperbarui:</span>
              <span class="text-xs font-bold text-gray-700">{{ monitoringDateFormatted }}</span>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
            <!-- Selector Rombel Kelas -->
            <div>
              <label class="block font-semibold text-gray-700 mb-1">Pilih Rombel / Kelas</label>
              <select
                v-model="monitoringClassId"
                @change="fetchMonitoringData"
                class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
              >
                <option v-for="cls in teacherClasses" :key="cls.id" :value="cls.id">
                  {{ cls.name }}
                </option>
              </select>
            </div>

            <!-- Selector Tanggal -->
            <div>
              <label class="block font-semibold text-gray-700 mb-1">Tanggal KBM</label>
              <input
                v-model="monitoringDate"
                @change="fetchMonitoringData"
                type="date"
                class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
              />
            </div>

            <!-- Filter Status Jurnal -->
            <div>
              <label class="block font-semibold text-gray-700 mb-1">Status Verifikasi</label>
              <select
                v-model="monitoringFilterStatus"
                class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
              >
                <option value="ALL">Semua Status</option>
                <option value="TERVERIFIKASI">Terverifikasi</option>
                <option value="PENDING">Menunggu Paraf Guru</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Attendance Summary Rombel Hari Ini -->
        <div class="bg-white rounded-2xl border border-gray-150 p-4 sm:p-5 shadow-2xs space-y-3">
          <div class="flex items-center justify-between pb-2 border-b border-gray-100">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
              <h4 class="text-xs sm:text-sm font-bold text-gray-900">
                Ringkasan Presensi Harian Kelas ({{ currentMonitoringClassName }})
              </h4>
            </div>
            <span class="text-[11px] text-gray-500 font-mono">
              Total: {{ monitoringAttendanceStats.total }} Siswa
            </span>
          </div>

          <!-- 4 Grid Stats: Pure Numbers (Tanpa Label Subjektif) -->
          <div class="grid grid-cols-4 gap-2 text-center text-xs">
            <div class="p-2.5 bg-emerald-50 border border-emerald-100 rounded-xl">
              <span class="text-[10px] text-emerald-600 block font-semibold">Hadir</span>
              <span class="font-extrabold text-emerald-800 text-base sm:text-lg">{{ monitoringAttendanceStats.hadir }}</span>
            </div>
            <div class="p-2.5 bg-amber-50 border border-amber-100 rounded-xl">
              <span class="text-[10px] text-amber-600 block font-semibold">Sakit</span>
              <span class="font-extrabold text-amber-800 text-base sm:text-lg">{{ monitoringAttendanceStats.sakit }}</span>
            </div>
            <div class="p-2.5 bg-blue-50 border border-blue-100 rounded-xl">
              <span class="text-[10px] text-blue-600 block font-semibold">Izin</span>
              <span class="font-extrabold text-blue-800 text-base sm:text-lg">{{ monitoringAttendanceStats.izin }}</span>
            </div>
            <div class="p-2.5 bg-rose-50 border border-rose-100 rounded-xl">
              <span class="text-[10px] text-rose-600 block font-semibold">ALFA</span>
              <span class="font-extrabold text-rose-800 text-base sm:text-lg">{{ monitoringAttendanceStats.alpha }}</span>
            </div>
          </div>
        </div>

        <!-- Class Agenda List: Mobile Cards & Desktop Table -->
        <div class="bg-white rounded-2xl border border-gray-150 shadow-card p-4 sm:p-6 space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-gray-100">
            <div>
              <h3 class="text-sm sm:text-base font-bold text-gray-900">Riwayat Agenda KBM Kelas</h3>
              <p class="text-[11px] text-gray-400">Daftar tatap muka, aktivitas belajar, dan pantauan siswa yang meninggalkan jam KBM</p>
            </div>
            <span class="text-xs font-semibold text-gray-500">
              {{ filteredMonitoringJournals.length }} Agenda Ditemukan
            </span>
          </div>

          <div v-if="monitoringLoading" class="py-12 text-center text-gray-400 text-xs">
            Memuat riwayat KBM kelas...
          </div>

          <div v-else-if="filteredMonitoringJournals.length === 0" class="py-12 text-center rounded-2xl border border-dashed border-gray-200 bg-gray-50/50 space-y-2">
            <div class="w-10 h-10 mx-auto rounded-full bg-blue-50 text-primary flex items-center justify-center">
              <FileTextIcon class="w-5 h-5" />
            </div>
            <p class="text-xs font-bold text-gray-700">Belum Ada Agenda KBM Tercatat</p>
            <p class="text-[11px] text-gray-400">Tidak ada data agenda KBM untuk kelas dan tanggal yang dipilih.</p>
          </div>

          <div v-else>
            <!-- Mobile Card View -->
            <div class="block md:hidden space-y-3">
              <div
                v-for="j in filteredMonitoringJournals"
                :key="j.id"
                class="p-4 rounded-2xl border border-gray-150 bg-white space-y-2.5 shadow-2xs"
              >
                <div class="flex items-center justify-between gap-2 border-b border-gray-100 pb-2">
                  <span class="text-xs font-bold text-gray-900">{{ j.time_slot || j.date_time }}</span>
                  <span
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                    :class="j.status === 'TERVERIFIKASI' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                  >
                    {{ j.status === 'TERVERIFIKASI' ? 'Terverifikasi' : 'Menunggu Paraf' }}
                  </span>
                </div>

                <div>
                  <h4 class="text-xs sm:text-sm font-bold text-primary">{{ j.subject }}</h4>
                  <p class="text-[11px] text-gray-500">Guru: {{ j.teacher }}</p>
                </div>

                <div class="p-2.5 bg-gray-50 rounded-xl border border-gray-100 text-xs space-y-1">
                  <p class="font-semibold text-gray-800"><span class="text-gray-400 font-normal">Materi:</span> {{ j.topic }}</p>
                  <p class="text-gray-600 leading-relaxed">{{ j.activity }}</p>
                  <p v-if="j.notes" class="text-[11px] text-gray-500 italic pt-1 border-t border-gray-200/50">Catatan: {{ j.notes }}</p>
                </div>

                <!-- Alert Siswa Cabut Jam Ini -->
                <div v-if="j.absent_students && j.absent_students.length > 0" class="p-2 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
                  <div class="font-bold flex items-center gap-1.5 text-rose-700">
                    <AlertCircleIcon class="w-3.5 h-3.5 shrink-0" />
                    <span>{{ j.absent_students.length }} Siswa Tidak Mengikuti Jam Ini (Cabut):</span>
                  </div>
                  <div class="flex flex-wrap gap-1 pt-0.5">
                    <span v-for="s in j.absent_students" :key="s.id" class="px-2 py-0.5 rounded-md bg-white border border-rose-200 text-[11px] font-semibold text-rose-700 shadow-2xs">
                      {{ s.name }}
                    </span>
                  </div>
                </div>

                <!-- Action Button -->
                <div v-if="j.status !== 'TERVERIFIKASI'" class="pt-1 flex justify-end">
                  <button
                    type="button"
                    @click="confirmMonitoringJournal(j)"
                    class="w-full py-2 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-2xs min-h-[44px]"
                  >
                    <CheckIcon class="w-4 h-4" />
                    <span>Sahkan / Beri Paraf</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto">
              <table class="w-full text-left border-collapse text-xs">
                <thead>
                  <tr class="bg-gray-50/70 border-b border-gray-100 text-gray-500 uppercase tracking-wider font-semibold">
                    <th class="py-3 px-4 w-12 text-center whitespace-nowrap">No</th>
                    <th class="py-3 px-4 whitespace-nowrap">Waktu & Ruang</th>
                    <th class="py-3 px-4 whitespace-nowrap">Mata Pelajaran & Guru</th>
                    <th class="py-3 px-4 whitespace-nowrap">Pokok Bahasan / Materi</th>
                    <th class="py-3 px-4">Realisasi Pembelajaran</th>
                    <th class="py-3 px-4 whitespace-nowrap">Siswa Cabut Jam Ini</th>
                    <th class="py-3 px-4 text-center whitespace-nowrap">Status</th>
                    <th class="py-3 px-4 text-right whitespace-nowrap">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr
                    v-for="(j, idx) in filteredMonitoringJournals"
                    :key="j.id"
                    class="hover:bg-gray-50/50 transition"
                  >
                    <td class="py-3.5 px-4 text-center font-medium text-gray-400">{{ idx + 1 }}</td>
                    <td class="py-3.5 px-4 whitespace-nowrap">
                      <div class="font-bold text-gray-900">{{ j.time_slot || j.date_time }}</div>
                      <div class="text-[11px] text-gray-400 mt-0.5">{{ j.room || 'Kelas' }}</div>
                    </td>
                    <td class="py-3.5 px-4 whitespace-nowrap">
                      <div class="font-bold text-primary">{{ j.subject }}</div>
                      <div class="text-[11px] text-gray-500 mt-0.5">{{ j.teacher }}</div>
                    </td>
                    <td class="py-3.5 px-4 font-semibold text-gray-800 whitespace-nowrap">
                      {{ j.topic }}
                    </td>
                    <td class="py-3.5 px-4 text-gray-600 max-w-sm">
                      <div class="line-clamp-2 leading-relaxed" :title="j.activity">{{ j.activity }}</div>
                    </td>
                    <td class="py-3.5 px-4 whitespace-nowrap">
                      <span
                        v-if="j.absent_students && j.absent_students.length > 0"
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200"
                        :title="j.absent_students.map((s: any) => s.name).join(', ')"
                      >
                        <AlertCircleIcon class="w-3 h-3 text-rose-600 shrink-0" />
                        <span>{{ j.absent_students.length }} Siswa Cabut</span>
                      </span>
                      <span v-else class="text-gray-400 text-[11px]">-</span>
                    </td>
                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                      <span
                        class="px-2.5 py-1 rounded-full text-[10px] font-bold inline-block"
                        :class="j.status === 'TERVERIFIKASI' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                      >
                        {{ j.status === 'TERVERIFIKASI' ? 'Terverifikasi' : 'Menunggu Paraf' }}
                      </span>
                    </td>
                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                      <button
                        v-if="j.status !== 'TERVERIFIKASI'"
                        type="button"
                        @click="confirmMonitoringJournal(j)"
                        class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold transition shadow-2xs cursor-pointer"
                      >
                        <CheckIcon class="w-3 h-3" />
                        <span>Sahkan</span>
                      </button>
                      <span v-else class="text-xs text-gray-400 font-semibold">Tersahkan</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- ============================================== -->
    <!-- MODAL 1: DETAIL JURNAL REALISASI KBM           -->
    <!-- ============================================== -->
    <div
      v-if="activeModalItem"
      class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-xs"
    >
      <div class="bg-white rounded-t-3xl sm:rounded-3xl max-w-lg w-full p-5 sm:p-7 shadow-2xl space-y-4 max-h-[85vh] sm:max-h-[90vh] overflow-y-auto pb-8 sm:pb-7 pb-safe">
        <!-- Mobile drag handle -->
        <div class="sm:hidden w-10 h-1 bg-gray-300 rounded-full mx-auto -mt-2 mb-3"></div>
        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
          <div>
            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-100 text-primary">
              {{ activeModalItem.subject }}
            </span>
            <h3 class="text-base font-bold text-gray-900 mt-1">Detail Jurnal Realisasi KBM</h3>
          </div>
          <button @click="activeModalItem = null" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">
            <XIcon class="w-4 h-4" />
          </button>
        </div>

        <div class="space-y-3.5 text-xs text-gray-700">
          <!-- Status Banner -->
          <div
            v-if="activeModalItem.status === 'Terverifikasi'"
            class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs flex items-start gap-2.5"
          >
            <LockIcon class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
            <div>
              <p class="font-bold text-emerald-900">Jurnal KBM Terverifikasi Resmi</p>
              <p class="text-[11px] text-emerald-700 mt-0.5">
                Paraf telah disahkan dan tercatat di buku agenda kelas.
              </p>
            </div>
          </div>

          <div
            v-else
            class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-xs flex items-start gap-2.5"
          >
            <ClockIcon class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
            <div>
              <p class="font-bold text-amber-900">Menunggu Konfirmasi / Paraf Guru</p>
              <p class="text-[11px] text-amber-700 mt-0.5">
                Periksa kesesuaian materi di bawah sebelum memberikan paraf pengesahan.
              </p>
            </div>
          </div>

          <div>
            <span class="font-bold text-gray-400 uppercase text-[10px] block mb-1">Mata Pelajaran & Sesi</span>
            <p class="font-semibold text-gray-800">{{ activeModalItem.time }} • {{ activeModalItem.subject }}</p>
          </div>

          <div>
            <span class="font-bold text-gray-400 uppercase text-[10px] block mb-1">Pokok Bahasan / KD</span>
            <p class="bg-gray-50 p-3 rounded-xl border border-gray-150 text-gray-800 font-medium">
              {{ activeModalItem.topic || 'Belum ada pokok bahasan tercatat.' }}
            </p>
          </div>

          <div>
            <span class="font-bold text-gray-400 uppercase text-[10px] block mb-1">Realisasi Aktivitas KBM di Kelas</span>
            <p class="bg-gray-50 p-3 rounded-xl border border-gray-150 text-gray-700 leading-relaxed whitespace-pre-line">
              {{ activeModalItem.activity || 'Pengenalan konsep dan latihan praktikum terbimbing di laboratorium.' }}
            </p>
          </div>

          <div>
            <span class="font-bold text-gray-400 uppercase text-[10px] block mb-1">Catatan Kelas / Ruang Praktikum</span>
            <p class="bg-gray-50 p-3 rounded-xl border border-gray-150 text-gray-600">
              {{ activeModalItem.notes || 'Pembelajaran berjalan tertib, kendala nihil.' }}
            </p>
          </div>

          <!-- Alert Siswa Tidak Berada di Jam Ini -->
          <div v-if="activeModalItem.absent_students && activeModalItem.absent_students.length > 0" class="p-3 bg-rose-50 border border-rose-200 rounded-xl space-y-1.5">
            <span class="font-bold text-rose-800 text-xs flex items-center gap-1.5">
              <AlertCircleIcon class="w-4 h-4 text-rose-600 shrink-0" />
              Siswa Tidak di Kelas pada Jam Ini ({{ activeModalItem.absent_students.length }} siswa):
            </span>
            <div class="flex flex-wrap gap-1.5 pt-0.5">
              <span v-for="s in activeModalItem.absent_students" :key="s.id" class="px-2.5 py-1 rounded-md bg-white border border-rose-200 text-rose-700 font-semibold text-[11px] shadow-2xs">
                {{ s.name }} (NIS: {{ s.nis }})
              </span>
            </div>
          </div>
        </div>

        <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
          <button
            type="button"
            @click="activeModalItem = null"
            class="px-4 py-2 rounded-xl border border-gray-200 text-xs font-semibold text-gray-600 hover:bg-gray-50 cursor-pointer"
          >
            Tutup
          </button>

          <!-- Edit button if Menunggu -->
          <button
            v-if="activeModalItem.status.includes('Menunggu')"
            type="button"
            @click="editFromDetailModal"
            class="px-4 py-2 rounded-xl border border-amber-300 bg-amber-50 hover:bg-amber-100 text-amber-900 text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer"
          >
            <Edit2Icon class="w-3.5 h-3.5 text-amber-700" />
            <span>Edit Jurnal</span>
          </button>

          <!-- Konfirmasi button if Menunggu -->
          <button
            v-if="activeModalItem.status.includes('Menunggu')"
            type="button"
            @click="confirmModalJournal"
            :disabled="confirming"
            class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-semibold transition flex items-center gap-1.5 disabled:opacity-50 cursor-pointer"
          >
            <CheckIcon class="w-3.5 h-3.5" />
            <span>{{ confirming ? 'Menyahkan...' : 'Beri Paraf / Sahkan' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ============================================== -->
    <!-- MODAL 2: ISI JURNAL KBM + CHECKLIST SISWA CABUT-->
    <!-- ============================================== -->
    <div
      v-if="fillModalItem"
      class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-xs"
    >
      <div class="bg-white rounded-t-3xl sm:rounded-3xl max-w-lg w-full p-5 sm:p-7 shadow-2xl space-y-4 max-h-[85vh] sm:max-h-[90vh] overflow-y-auto pb-8 sm:pb-7 pb-safe">
        <!-- Mobile drag handle -->
        <div class="sm:hidden w-10 h-1 bg-gray-300 rounded-full mx-auto -mt-2 mb-3"></div>
        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
          <div>
            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800">
              {{ fillModalItem.subject }}
            </span>
            <h3 class="text-base font-bold text-gray-900 mt-1">
              {{ fillModalItem.status === 'Menunggu' ? 'Edit Jurnal KBM (Revisi Sesi)' : 'Isi Jurnal KBM Hari Ini' }}
            </h3>
            <p class="text-xs text-gray-400">Jadwal: {{ fillModalItem.time }}</p>
          </div>
          <button @click="fillModalItem = null" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">
            <XIcon class="w-4 h-4" />
          </button>
        </div>

        <form @submit.prevent="submitFillJournal" class="space-y-3.5 text-xs">
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Pokok Bahasan / KD</label>
            <input
              v-model="fillJournalForm.topic"
              type="text"
              placeholder="Contoh: Implementasi State Management Pinia & Vue 3"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
              required
            />
          </div>

          <div>
            <label class="block font-semibold text-gray-700 mb-1">Rangkuman Aktivitas Pembelajaran</label>
            <textarea
              v-model="fillJournalForm.activity"
              rows="3"
              placeholder="Jelaskan ringkasan materi, praktikum laboratorium, dan ketercapaian kelas hari ini..."
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
              required
            ></textarea>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 mb-1">Catatan Khusus / Kondisi Kelas</label>
            <input
              v-model="fillJournalForm.notes"
              type="text"
              placeholder="Contoh: Praktikum tuntas 100%, seluruh PC lab berjalan normal"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
            />
          </div>

          <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
            <button
              type="button"
              @click="fillModalItem = null"
              class="px-4 py-2 rounded-xl border border-gray-200 text-xs font-semibold text-gray-600 hover:bg-gray-50 cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="savingFillJournal"
              class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-semibold transition disabled:opacity-50 cursor-pointer"
            >
              {{ savingFillJournal ? 'Menyimpan...' : (fillModalItem.status === 'Menunggu' ? 'Simpan Perubahan' : 'Simpan & Sahkan Jurnal') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================== -->
    <!-- MODAL 3: TAMBAH JADWAL MENGAJAR BARU           -->
    <!-- ============================================== -->
    <div
      v-if="openAddScheduleModal"
      class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-xs"
    >
      <div class="bg-white rounded-t-3xl sm:rounded-3xl max-w-md w-full p-5 sm:p-7 shadow-2xl space-y-4 max-h-[85vh] sm:max-h-[90vh] overflow-y-auto pb-8 sm:pb-7 pb-safe">
        <!-- Mobile drag handle -->
        <div class="sm:hidden w-10 h-1 bg-gray-300 rounded-full mx-auto -mt-2 mb-3"></div>
        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
          <h3 class="text-base font-bold text-gray-900">Tambah Jadwal Mengajar Baru</h3>
          <button @click="openAddScheduleModal = false" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">
            <XIcon class="w-4 h-4" />
          </button>
        </div>

        <form @submit.prevent="submitNewSchedule" class="space-y-3.5 text-xs">
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Pilih Kelas / Rombel</label>
            <select
              v-model="newScheduleForm.class_id"
              class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
              required
            >
              <option v-for="cls in availableClasses" :key="cls.id" :value="cls.id">
                {{ cls.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 mb-1">Mata Pelajaran</label>
            <input
              v-model="newScheduleForm.subject_name"
              type="text"
              placeholder="Contoh: Pemrograman Web & Perangkat Bergerak"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
              required
            />
          </div>

          <div>
            <label class="block font-semibold text-gray-700 mb-1">Hari Pembelajaran</label>
            <select
              v-model="newScheduleForm.day"
              class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
              required
            >
              <option value="Senin">Senin</option>
              <option value="Selasa">Selasa</option>
              <option value="Rabu">Rabu</option>
              <option value="Kamis">Kamis</option>
              <option value="Jumat">Jumat</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-gray-700 mb-1">Jam Mulai</label>
              <input
                v-model="newScheduleForm.start_time"
                type="time"
                class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                required
              />
            </div>
            <div>
              <label class="block font-semibold text-gray-700 mb-1">Jam Selesai</label>
              <input
                v-model="newScheduleForm.end_time"
                type="time"
                class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                required
              />
            </div>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 mb-1">Ruangan / Lab</label>
            <input
              v-model="newScheduleForm.room"
              type="text"
              placeholder="Contoh: Lab Komputer 2"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
            />
          </div>

          <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
            <button
              type="button"
              @click="openAddScheduleModal = false"
              class="px-4 py-2 rounded-xl border border-gray-200 text-xs font-semibold text-gray-600 hover:bg-gray-50 cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-semibold transition cursor-pointer"
            >
              Simpan Jadwal
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================== -->
    <!-- MODAL 4: UNGGAH MATERI & MODUL BELAJAR GURU   -->
    <!-- ============================================== -->
    <div
      v-if="openUploadMaterialModal"
      class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-xs"
    >
      <div class="bg-white rounded-t-3xl sm:rounded-3xl max-w-lg w-full p-5 sm:p-7 shadow-2xl space-y-4 max-h-[85vh] sm:max-h-[90vh] overflow-y-auto pb-8 sm:pb-7 pb-safe">
        <!-- Mobile drag handle -->
        <div class="sm:hidden w-10 h-1 bg-gray-300 rounded-full mx-auto -mt-2 mb-3"></div>
        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
          <div>
            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-100 text-primary uppercase tracking-wider">
              Modul Digital
            </span>
            <h3 class="text-base font-bold text-gray-900 mt-1">Unggah Materi & Modul Belajar</h3>
            <p class="text-xs text-gray-400">Berkas dapat diakses dan diunduh langsung oleh siswa di kelas.</p>
          </div>
          <button @click="openUploadMaterialModal = false" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">
            <XIcon class="w-4 h-4" />
          </button>
        </div>

        <form @submit.prevent="submitUploadMaterial" class="space-y-3.5 text-xs">
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Pilih Kelas & Mapel Tujuan *</label>
            <select
              v-model="uploadMaterialForm.schedule_id"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary cursor-pointer"
              required
            >
              <option value="" disabled>-- Pilih Jadwal & Kelas --</option>
              <option v-for="sch in allTeacherSchedules" :key="sch.id" :value="sch.id">
                {{ sch.school_class?.name || sch.class_name }} — {{ sch.subject?.name || sch.subject_name }} ({{ sch.day }}, {{ sch.start_time }}-{{ sch.end_time }})
              </option>
            </select>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 mb-1">Pokok Bahasan / Topik KD *</label>
            <input
              v-model="uploadMaterialForm.topic"
              type="text"
              placeholder="Contoh: Modul Praktikum REST API & Vue 3"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
              required
            />
          </div>

          <div>
            <label class="block font-semibold text-gray-700 mb-1">Instruksi Tugas / Catatan Tambahan</label>
            <textarea
              v-model="uploadMaterialForm.assignment"
              rows="2"
              placeholder="Contoh: Baca materi bab 2 dan kerjakan latihan mandiri..."
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
            ></textarea>
          </div>

          <!-- File Dropzone -->
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Berkas Dokumen Materi (PDF, DOCX, PPTX, JPG)</label>
            <div
              @dragover.prevent="isDraggingUpload = true"
              @dragleave.prevent="isDraggingUpload = false"
              @drop.prevent="handleUploadFileDrop"
              class="border-2 border-dashed rounded-2xl p-4 text-center transition cursor-pointer"
              :class="isDraggingUpload ? 'border-primary bg-blue-50/50' : 'border-gray-200 hover:border-gray-300 bg-gray-50/40'"
              @click="triggerUploadFileInput"
            >
              <input
                ref="uploadFileInputRef"
                type="file"
                class="hidden"
                accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png,.zip"
                @change="handleUploadFileSelect"
              />

              <div v-if="!uploadSelectedFile" class="space-y-1 py-1">
                <div class="w-8 h-8 rounded-full bg-blue-50 text-primary flex items-center justify-center mx-auto">
                  <UploadCloudIcon class="w-4 h-4" />
                </div>
                <p class="text-xs font-semibold text-primary">Klik untuk Pilih File atau Tarik ke Sini</p>
                <p class="text-[10px] text-gray-400">Format PDF, DOCX, PPTX, JPG (Maks 10MB)</p>
              </div>

              <div v-else class="flex items-center justify-between bg-white p-2.5 rounded-xl border border-gray-200 text-left">
                <div class="flex items-center gap-2 min-w-0">
                  <FileTextIcon class="w-5 h-5 text-primary shrink-0" />
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-gray-800 truncate">{{ uploadSelectedFile.name }}</p>
                    <p class="text-[10px] text-gray-400">{{ (uploadSelectedFile.size / 1024).toFixed(1) }} KB</p>
                  </div>
                </div>
                <button
                  type="button"
                  @click.stop="removeUploadFile"
                  class="p-1 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition"
                >
                  <XIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
            <button
              type="button"
              @click="openUploadMaterialModal = false"
              class="px-4 py-2 rounded-xl border border-gray-200 text-xs font-semibold text-gray-600 hover:bg-gray-50 cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="uploadingMaterial"
              class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-semibold transition cursor-pointer flex items-center gap-1.5 disabled:opacity-50"
            >
              <UploadCloudIcon class="w-3.5 h-3.5" />
              <span>{{ uploadingMaterial ? 'Mengunggah...' : 'Publikasikan ke Siswa' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  FileText as FileTextIcon,
  CheckCircle2 as CheckCircle2Icon,
  X as XIcon,
  Plus as PlusIcon,
  Lock as LockIcon,
  AlertCircle as AlertCircleIcon,
  Edit2 as Edit2Icon,
  Clock as ClockIcon,
  Eye as EyeIcon,
  Check as CheckIcon,
  Calendar as CalendarIcon,
  BarChart2 as BarChart2Icon,
  Search as SearchIcon,
  UploadCloud as UploadCloudIcon,
  Download as DownloadIcon,
  BookOpen as BookOpenIcon,
} from 'lucide-vue-next'

const route = useRoute()
const config = useRuntimeConfig()
const authStore = useAuthStore()

const successMsg = ref('')
const activeMainTab = ref<'input' | 'monitoring'>(route.query.tab === 'monitoring' ? 'monitoring' : 'input')

const switchMainTab = (tab: 'input' | 'monitoring') => {
  activeMainTab.value = tab
  if (tab === 'monitoring') {
    fetchMonitoringData()
  }
}

// ----------------------------------------------------
// TAB 1: STATE & METHODS
// ----------------------------------------------------
const pendingCount = ref(0)
const filledCount = ref(0)
const confirming = ref(false)
const savingFillJournal = ref(false)

const allTeacherSchedules = ref<any[]>([])
const allTeacherJournals = ref<any[]>([])
const allTeacherMaterials = ref<any[]>([])
const availableClasses = ref<any[]>([])
const scheduleItems = ref<any[]>([])

const daysList = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']
const currentDayName = computed(() => daysList[new Date().getDay()])
const activeDayFilter = ref('Hari Ini')

const taughtClassesList = computed(() => {
  const set = new Set<string>()
  allTeacherSchedules.value.forEach((sch: any) => {
    const name = sch.school_class?.name || sch.class_name
    if (name) set.add(name)
  })
  return Array.from(set)
})

const openAddScheduleModal = ref(false)
const openUploadMaterialModal = ref(false)
const uploadingMaterial = ref(false)
const isDraggingUpload = ref(false)
const uploadSelectedFile = ref<File | null>(null)
const uploadFileInputRef = ref<HTMLInputElement | null>(null)
const uploadMaterialForm = ref({
  schedule_id: '',
  topic: '',
  assignment: ''
})

const triggerUploadFileInput = () => {
  uploadFileInputRef.value?.click()
}

const handleUploadFileSelect = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    uploadSelectedFile.value = target.files[0]
  }
}

const handleUploadFileDrop = (e: DragEvent) => {
  isDraggingUpload.value = false
  if (e.dataTransfer?.files && e.dataTransfer.files[0]) {
    uploadSelectedFile.value = e.dataTransfer.files[0]
  }
}

const removeUploadFile = () => {
  uploadSelectedFile.value = null
  if (uploadFileInputRef.value) uploadFileInputRef.value.value = ''
}

const submitUploadMaterial = async () => {
  if (!uploadMaterialForm.value.schedule_id || !uploadMaterialForm.value.topic) return
  uploadingMaterial.value = true
  try {
    const formData = new FormData()
    formData.append('schedule_id', uploadMaterialForm.value.schedule_id)
    formData.append('topic', uploadMaterialForm.value.topic)
    formData.append('plan', uploadMaterialForm.value.topic)
    formData.append('assignment', uploadMaterialForm.value.assignment || '')
    if (uploadSelectedFile.value) {
      formData.append('file', uploadSelectedFile.value)
    }

    await $fetch(`${config.public.apiBase}/journals/material`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: formData
    })

    successMsg.value = `Modul & bahan ajar '${uploadMaterialForm.value.topic}' berhasil diunggah dan dapat diunduh siswa!`
    openUploadMaterialModal.value = false
    uploadMaterialForm.value = { schedule_id: '', topic: '', assignment: '' }
    uploadSelectedFile.value = null
    if (uploadFileInputRef.value) uploadFileInputRef.value.value = ''
    fetchTeacherSchedulesAndJournals()
  } catch (err: any) {
    console.error('Error uploading material:', err)
    alert(err?.data?.message || 'Gagal mengunggah materi.')
  } finally {
    uploadingMaterial.value = false
  }
}
const newScheduleForm = ref({
  class_id: '',
  subject_name: '',
  day: 'Senin',
  start_time: '07:30',
  end_time: '09:00',
  room: 'Lab Komputer 1'
})

const fillModalItem = ref<any>(null)
const fillJournalForm = ref({
  topic: '',
  activity: '',
  notes: ''
})

// Siswa Cabut State in Fill Journal Modal
const modalClassStudents = ref<any[]>([])
const selectedModalAbsentStudents = ref<any[]>([])
const searchModalStudentQuery = ref('')

const filteredModalClassStudents = computed(() => {
  if (!searchModalStudentQuery.value) return modalClassStudents.value
  const q = searchModalStudentQuery.value.toLowerCase()
  return modalClassStudents.value.filter(s => s.name?.toLowerCase().includes(q) || s.nis?.includes(q))
})

const fetchModalClassStudents = async (classId: number | string) => {
  if (!classId) return
  try {
    const res: any = await $fetch(`${config.public.apiBase}/classes/${classId}/students`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    modalClassStudents.value = Array.isArray(res) ? res : []
  } catch (e) {
    modalClassStudents.value = []
  }
}

const toggleModalAbsentStudent = (student: any) => {
  const idx = selectedModalAbsentStudents.value.findIndex(s => s.id === student.id)
  if (idx >= 0) {
    selectedModalAbsentStudents.value.splice(idx, 1)
  } else {
    selectedModalAbsentStudents.value.push({
      id: student.id,
      name: student.name,
      nis: student.nis
    })
  }
}

const isModalStudentAbsent = (studentId: any) => {
  return selectedModalAbsentStudents.value.some(s => s.id === studentId)
}

const activeModalItem = ref<any>(null)

const openJournalDetail = (item: any) => {
  activeModalItem.value = item
}

const openConfirmModal = (item: any) => {
  activeModalItem.value = item
}

const editFromDetailModal = () => {
  if (!activeModalItem.value) return
  const item = activeModalItem.value
  activeModalItem.value = null
  openFillJournal(item)
}

const confirmModalJournal = async () => {
  if (!activeModalItem.value) return
  confirming.value = true
  try {
    const journalId = activeModalItem.value.journal_id || activeModalItem.value.id
    await $fetch(`${config.public.apiBase}/journals/class/${journalId}/confirm`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {}
    })
    activeModalItem.value.status = 'Terverifikasi'
    const target = scheduleItems.value.find(s => s.id === activeModalItem.value.id || s.journal_id === journalId)
    if (target) {
      target.status = 'Terverifikasi'
    }
    pendingCount.value = Math.max(0, pendingCount.value - 1)
    successMsg.value = `Jurnal pelajaran ${activeModalItem.value.subject} berhasil disahkan & diverifikasi!`
    activeModalItem.value = null
    fetchTeacherSchedulesAndJournals()
  } catch (e) {
    console.warn('Fallback local verify note:', e)
    activeModalItem.value.status = 'Terverifikasi'
    pendingCount.value = Math.max(0, pendingCount.value - 1)
    successMsg.value = `Jurnal pelajaran ${activeModalItem.value.subject} disahkan!`
    activeModalItem.value = null
  } finally {
    confirming.value = false
  }
}

const openFillJournal = async (item: any) => {
  fillModalItem.value = item
  fillJournalForm.value.topic = item.topic || ''
  fillJournalForm.value.activity = item.activity || ''
  fillJournalForm.value.notes = item.notes || ''
  selectedModalAbsentStudents.value = item.absent_students ? [...item.absent_students] : []
  searchModalStudentQuery.value = ''
  await fetchModalClassStudents(item.class_id)
}

const applyScheduleFilter = () => {
  let filtered = allTeacherSchedules.value
  const todayStr = new Date().toISOString().slice(0, 10)

  if (activeDayFilter.value === 'Hari Ini') {
    filtered = allTeacherSchedules.value.filter((sch: any) => !sch.day || sch.day === currentDayName.value)
  } else if (activeDayFilter.value !== 'Semua') {
    filtered = allTeacherSchedules.value.filter((sch: any) => sch.day === activeDayFilter.value)
  }

  // Waktu sekarang WIB untuk deteksi jam pelajaran aktif (Current Time Slot)
  const now = new Date()
  const wibHour = (now.getUTCHours() + 7) % 24
  const wibMin = now.getUTCMinutes()
  const nowMinutes = wibHour * 60 + wibMin

  scheduleItems.value = filtered.map((sch: any) => {
    const match = allTeacherJournals.value.find((j: any) =>
      (j.schedule_id === sch.id || (j.class === sch.school_class?.name && j.subject === sch.subject?.name)) &&
      (!j.date || j.date === todayStr)
    ) || allTeacherJournals.value.find((j: any) => j.schedule_id === sch.id)

    let status = 'Belum Diisi'
    if (match) {
      status = match.status === 'TERVERIFIKASI' ? 'Terverifikasi' : 'Menunggu Paraf Guru'
    }

    const startParts = (sch.start_time || '07:30').slice(0, 5).split(':').map(Number)
    const endParts = (sch.end_time || '09:00').slice(0, 5).split(':').map(Number)
    const startMinutes = (startParts[0] || 0) * 60 + (startParts[1] || 0)
    const endMinutes = (endParts[0] || 0) * 60 + (endParts[1] || 0)

    const isToday = (!sch.day || sch.day === currentDayName.value)
    const isCurrentTimeSlot = isToday && (nowMinutes >= startMinutes && nowMinutes <= endMinutes)

    const matchMat = allTeacherMaterials.value.find((m: any) =>
      (m.schedule_id === sch.id || (m.subject === sch.subject?.name && (!m.class_id || m.class_id === sch.class_id))) &&
      (!m.date || m.date === todayStr)
    )

    return {
      id: sch.id,
      journal_id: match ? match.id : null,
      class_id: sch.class_id || sch.school_class?.id,
      day: sch.day || '-',
      time: `${sch.start_time?.slice(0, 5) || '07:30'} - ${sch.end_time?.slice(0, 5) || '09:00'}`,
      subject: `${sch.subject?.name || 'Mata Pelajaran'} (${sch.school_class?.name || '-'})`,
      status,
      topic: match ? match.topic : '',
      activity: match ? match.activity : '',
      notes: match ? match.notes : '',
      absent_students: match ? (match.absent_students || []) : [],
      created_by: match ? match.created_by : 'Sekretaris',
      is_current_slot: isCurrentTimeSlot,
      has_material: !!matchMat,
      material_topic: matchMat ? matchMat.topic : null,
      material_url: matchMat ? matchMat.attachment_url : null,
      material_attachment: matchMat ? matchMat.attachment_name : null,
    }
  })

  filledCount.value = scheduleItems.value.filter(s => s.status !== 'Belum Diisi').length
  pendingCount.value = scheduleItems.value.filter(s => s.status.includes('Menunggu')).length
}

const setDayFilter = (day: string) => {
  activeDayFilter.value = day
  applyScheduleFilter()
}

const fetchTeacherSchedulesAndJournals = async () => {
  try {
    const [schedulesRes, journalsRes, materialsRes] = await Promise.all([
      $fetch<any>(`${config.public.apiBase}/schedules`, {
        headers: { Authorization: `Bearer ${authStore.token}` }
      }),
      $fetch<any>(`${config.public.apiBase}/journals/class`, {
        headers: { Authorization: `Bearer ${authStore.token}` }
      }),
      $fetch<any>(`${config.public.apiBase}/journals/material`, {
        headers: { Authorization: `Bearer ${authStore.token}` }
      }).catch(() => [])
    ])

    const scheduleList = Array.isArray(schedulesRes) ? schedulesRes : []
    allTeacherSchedules.value = scheduleList
    allTeacherJournals.value = journalsRes?.data || (Array.isArray(journalsRes) ? journalsRes : [])
    allTeacherMaterials.value = Array.isArray(materialsRes) ? materialsRes : (materialsRes?.data || [])

    applyScheduleFilter()
  } catch (e) {
    console.error('Error fetching schedules/journals:', e)
    scheduleItems.value = []
    filledCount.value = 0
    pendingCount.value = 0
  }
}

const fetchAvailableClasses = async () => {
  try {
    const res = await $fetch<any>(`${config.public.apiBase}/classes`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    availableClasses.value = Array.isArray(res) ? res : []
    if (availableClasses.value.length > 0 && !newScheduleForm.value.class_id) {
      newScheduleForm.value.class_id = String(availableClasses.value[0].id)
    }
    if (availableClasses.value.length > 0 && !monitoringClassId.value) {
      monitoringClassId.value = String(availableClasses.value[0].id)
    }
  } catch (err) {
    console.warn('Fetch classes warning:', err)
  }
}

const submitNewSchedule = async () => {
  try {
    await $fetch(`${config.public.apiBase}/schedules`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {
        class_id: newScheduleForm.value.class_id,
        subject_name: newScheduleForm.value.subject_name,
        day: newScheduleForm.value.day,
        start_time: newScheduleForm.value.start_time,
        end_time: newScheduleForm.value.end_time,
        room: newScheduleForm.value.room,
      }
    })
    openAddScheduleModal.value = false
    successMsg.value = `Jadwal baru berhasil ditambahkan!`
    fetchTeacherSchedulesAndJournals()
  } catch (err) {
    console.error(err)
    openAddScheduleModal.value = false
  }
}

const submitFillJournal = async () => {
  if (!fillModalItem.value) return
  savingFillJournal.value = true
  try {
    await $fetch(`${config.public.apiBase}/journals/class`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {
        schedule_id: fillModalItem.value.id,
        class_id: fillModalItem.value.class_id,
        type: 'class_agenda',
        topic: fillJournalForm.value.topic,
        activity: fillJournalForm.value.activity,
        notes: fillJournalForm.value.notes,
        absent_students: selectedModalAbsentStudents.value,
        date: new Date().toISOString().slice(0, 10)
      }
    })
  } catch (err) {
    console.error(err)
  } finally {
    fillModalItem.value.status = 'Terverifikasi'
    fillModalItem.value.topic = fillJournalForm.value.topic
    fillModalItem.value.notes = fillJournalForm.value.notes || fillJournalForm.value.activity
    fillModalItem.value.absent_students = [...selectedModalAbsentStudents.value]
    filledCount.value += 1
    successMsg.value = `Jurnal pelajaran ${fillModalItem.value.subject} berhasil disimpan & disahkan!`
    fillModalItem.value = null
    savingFillJournal.value = false
    fetchTeacherSchedulesAndJournals()
  }
}

// ----------------------------------------------------
// TAB 2: MONITORING & REKAP KELAS STATE & METHODS
// ----------------------------------------------------
const monitoringClassId = ref<string | number>('')
const monitoringDate = ref(new Date().toISOString().slice(0, 10))
const monitoringFilterStatus = ref('ALL')
const monitoringLoading = ref(false)
const monitoringJournals = ref<any[]>([])
const monitoringAttendanceStats = ref({
  total: 0,
  hadir: 0,
  sakit: 0,
  izin: 0,
  alpha: 0,
})

const teacherClasses = computed(() => {
  const map = new Map<number, string>()
  allTeacherSchedules.value.forEach((sch: any) => {
    const id = sch.class_id || sch.school_class?.id
    const name = sch.school_class?.name || sch.class_name
    if (id && name) map.set(Number(id), name)
  })
  if (map.size === 0 && availableClasses.value.length > 0) {
    availableClasses.value.forEach((c: any) => map.set(Number(c.id), c.name))
  }
  return Array.from(map.entries()).map(([id, name]) => ({ id, name }))
})

const currentMonitoringClassName = computed(() => {
  const found = teacherClasses.value.find(c => String(c.id) === String(monitoringClassId.value))
  return found ? found.name : 'Kelas Terpilih'
})

const monitoringDateFormatted = computed(() => {
  if (!monitoringDate.value) return 'Hari Ini'
  return new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  }).format(new Date(monitoringDate.value))
})

const filteredMonitoringJournals = computed(() => {
  if (monitoringFilterStatus.value === 'ALL') return monitoringJournals.value
  return monitoringJournals.value.filter(j => j.status === monitoringFilterStatus.value)
})

const fetchMonitoringData = async () => {
  if (!monitoringClassId.value) {
    if (teacherClasses.value.length > 0) {
      monitoringClassId.value = teacherClasses.value[0].id
    }
  }
  if (!monitoringClassId.value) return

  monitoringLoading.value = true
  try {
    const [journalsRes, attendanceRes] = await Promise.all([
      $fetch<any>(`${config.public.apiBase}/journals/class`, {
        headers: { Authorization: `Bearer ${authStore.token}` },
        query: {
          type: 'class_agenda',
          class_id: monitoringClassId.value,
          date: monitoringDate.value
        }
      }),
      $fetch<any>(`${config.public.apiBase}/attendance/today`, {
        headers: { Authorization: `Bearer ${authStore.token}` },
        query: {
          class_id: monitoringClassId.value,
          date: monitoringDate.value
        }
      })
    ])

    monitoringJournals.value = journalsRes?.data || []

    const students = attendanceRes?.students || []
    monitoringAttendanceStats.value = {
      total: students.length,
      hadir: students.filter((s: any) => s.status === 'HADIR').length,
      sakit: students.filter((s: any) => s.status === 'SAKIT').length,
      izin: students.filter((s: any) => s.status === 'IZIN').length,
      alpha: students.filter((s: any) => s.status === 'ALPHA').length,
    }
  } catch (e) {
    console.warn('Fetch monitoring error:', e)
    monitoringJournals.value = []
  } finally {
    monitoringLoading.value = false
  }
}

const confirmMonitoringJournal = async (journal: any) => {
  try {
    await $fetch(`${config.public.apiBase}/journals/class/${journal.id}/confirm`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {}
    })
    journal.status = 'TERVERIFIKASI'
    successMsg.value = `Agenda KBM ${journal.subject} berhasil disahkan!`
    fetchMonitoringData()
    fetchTeacherSchedulesAndJournals()
  } catch (err: any) {
    console.error('Confirm error:', err)
  }
}

onMounted(async () => {
  await fetchAvailableClasses()
  await fetchTeacherSchedulesAndJournals()
  if (teacherClasses.value.length > 0) {
    monitoringClassId.value = teacherClasses.value[0].id
  }
  if (activeMainTab.value === 'monitoring') {
    fetchMonitoringData()
  }
})
</script>
