<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Schedule;
use App\Models\Term;
use App\Models\AttendanceSession;
use App\Models\AttendanceRecord;
use App\Models\Setting;
use Carbon\Carbon;

class PresentationSeeder extends Seeder
{
    /**
     * Data Set Khusus Presentasi (Sangat Bersih & Ringkas):
     * - 1 Superadmin: Dr. H. Bambang Soeprapto, M.Pd
     * - 3 Guru Pengajar & Wali Kelas:
     *     1. Nurhayatul Fadilla, S.Pd (Wali Kelas XI RPL 1 & Guru MKK-RPL)
     *     2. Nurina Kartika Ayu, S.Pd (Wali Kelas XI RPL 2 & Guru PJOK/Kejuruan)
     *     3. Faradillah Aryani, S.T (Guru Pengajar KIK-RPL / PKK)
     * - 2 Kelas: XI RPL 1 dan XI RPL 2
     * - Siswa: Tepat 3 Siswa Regular per kelas (Sekre belum ditentukan, semua role SISWA)
     * - Akun Orang Tua: Ditiadakan (belum dibuat)
     * - Jadwal Pelajaran Hari Rabu (Aktif Real-time Sesuai Jadwal SMKN 71)
     */
    public function run(): void
    {
        $defaultPassword = Hash::make('password123');

        // Bersihkan data lama agar bersih total
        User::whereIn('role', ['ORANG_TUA', 'SISWA', 'SEKRETARIS'])->delete();

        // 1. Tahun Ajaran Aktif
        $term = Term::firstOrCreate(
            ['name' => '2026/2027 - Ganjil'],
            [
                'start_date' => '2026-07-15',
                'end_date' => '2026-12-20',
                'effective_days' => 108,
                'is_active' => true,
            ]
        );

        // 2. Konfigurasi Instansi Resmi SMKN 71 Jakarta
        Setting::set('school_name', 'SMK Negeri 71 Jakarta', 'general', 'Nama Resmi Sekolah');
        Setting::set('school_npsn', '20104567', 'general', 'Nomor Pokok Sekolah Nasional');
        Setting::set('school_address', 'Jl. Raden Inten II No. 71, Duren Sawit, Jakarta Timur', 'general', 'Alamat Sekolah');
        Setting::set('headmaster_name', 'Dr. H. Bambang Soeprapto, M.Pd', 'general', 'Nama Kepala Sekolah');
        Setting::set('headmaster_nip', '197505101999031002', 'general', 'NIP Kepala Sekolah');
        Setting::set('default_school_hours', '06:45 - 15:00', 'academic', 'Jam Masuk Sekolah Standar');
        Setting::set('attendance_lock_pin', '1234', 'security', 'PIN Buka Kunci Presensi');

        // 3. Dua Kelas Utama: XI RPL 1 dan XI RPL 2 (Sekre belum ditentukan)
        $class1 = SchoolClass::updateOrCreate(
            ['name' => 'XI RPL 1'],
            ['major' => 'Rekayasa Perangkat Lunak', 'secretary_id' => null]
        );

        $class2 = SchoolClass::updateOrCreate(
            ['name' => 'XI RPL 2'],
            ['major' => 'Rekayasa Perangkat Lunak', 'secretary_id' => null]
        );

        // 4. Superadmin (1 Akun)
        $superadmin = User::updateOrCreate(
            ['email' => 'admin@smkn71.sch.id'],
            [
                'name' => 'Dr. H. Bambang Soeprapto, M.Pd',
                'password' => $defaultPassword,
                'phone' => '081210000001',
                'nip_nis' => '197505101999031002',
                'role' => 'SUPERADMIN',
                'is_active' => true,
            ]
        );

        // 5. Tepat 3 Guru Pengajar & Walas
        // Guru 1: Nurhayatul Fadilla, S.Pd (Walas XI RPL 1)
        $guru1 = User::updateOrCreate(
            ['email' => 'fadilah@smkn71.sch.id'],
            [
                'name' => 'Nurhayatul Fadilla, S.Pd',
                'password' => $defaultPassword,
                'phone' => '081210000002',
                'nip_nis' => '19850000000023',
                'role' => 'GURU_PEMBIMBING',
                'specialization' => 'Konsentrasi Keahlian RPL & DPK',
                'class_id' => $class1->id,
                'is_active' => true,
            ]
        );
        $class1->update(['advisor_id' => $guru1->id, 'secretary_id' => null]);

        // Guru 2: Nurina Kartika Ayu, S.Pd (Walas XI RPL 2)
        $guru2 = User::updateOrCreate(
            ['email' => 'nurina@smkn71.sch.id'],
            [
                'name' => 'Nurina Kartika Ayu, S.Pd',
                'password' => $defaultPassword,
                'phone' => '081210000003',
                'nip_nis' => '19850000000010',
                'role' => 'GURU_PEMBIMBING',
                'specialization' => 'Pendidikan Jasmani Olahraga & Kesehatan (PJOK)',
                'class_id' => $class2->id,
                'is_active' => true,
            ]
        );
        $class2->update(['advisor_id' => $guru2->id, 'secretary_id' => null]);

        // Guru 3: Faradillah Aryani, S.T (Guru Pengajar KIK-RPL / PKK)
        $guru3 = User::updateOrCreate(
            ['email' => 'faradillah@smkn71.sch.id'],
            [
                'name' => 'Faradillah Aryani, S.T',
                'password' => $defaultPassword,
                'phone' => '081210000004',
                'nip_nis' => '19850000000016',
                'role' => 'GURU_PENGAJAR',
                'specialization' => 'Kreativitas & Inovasi Kejuruan (KIK/PKK)',
                'is_active' => true,
            ]
        );

        // 6. Mata Pelajaran Real SMKN 71
        $mapelMkk = Subject::firstOrCreate(['code' => 'MKK-RPL'], ['name' => 'Mata Pelajaran Konsentrasi Keahlian RPL']);
        $mapelPkk = Subject::firstOrCreate(['code' => 'KIK-RPL'], ['name' => 'Kreativitas & Inovasi Kejuruan (PKK) RPL']);
        $mapelPjok = Subject::firstOrCreate(['code' => 'PJOK-RPL'], ['name' => 'Pendidikan Jasmani Olahraga & Kesehatan']);

        // 7. Jadwal Pelajaran Hari Rabu & Seluruh Hari Sekolah
        $days = ['Rabu', 'Wednesday', 'Senin', 'Selasa', 'Kamis', 'Jumat', 'Monday', 'Tuesday', 'Thursday', 'Friday'];

        $schedulesRpl1 = [
            ['subject_id' => $mapelMkk->id, 'teacher_id' => $guru1->id, 'start' => '06:45', 'end' => '09:45', 'room' => 'R.402'],
            ['subject_id' => $mapelMkk->id, 'teacher_id' => $guru1->id, 'start' => '10:00', 'end' => '12:15', 'room' => 'R.402'],
            ['subject_id' => $mapelPkk->id, 'teacher_id' => $guru3->id, 'start' => '12:45', 'end' => '15:00', 'room' => 'R.405'],
        ];

        $schedulesRpl2 = [
            ['subject_id' => $mapelPjok->id, 'teacher_id' => $guru2->id, 'start' => '06:45', 'end' => '08:15', 'room' => 'R.409'],
            ['subject_id' => $mapelPkk->id, 'teacher_id' => $guru3->id, 'start' => '08:15', 'end' => '09:45', 'room' => 'R.406'],
            ['subject_id' => $mapelMkk->id, 'teacher_id' => $guru3->id, 'start' => '10:00', 'end' => '12:15', 'room' => 'R.406'],
            ['subject_id' => $mapelPjok->id, 'teacher_id' => $guru2->id, 'start' => '12:45', 'end' => '14:15', 'room' => 'R.406'],
        ];

        foreach ($days as $day) {
            foreach ($schedulesRpl1 as $s) {
                Schedule::updateOrCreate(
                    [
                        'class_id' => $class1->id,
                        'day' => $day,
                        'start_time' => $s['start'],
                        'end_time' => $s['end'],
                    ],
                    [
                        'subject_id' => $s['subject_id'],
                        'teacher_id' => $s['teacher_id'],
                        'room' => $s['room'],
                    ]
                );
            }

            foreach ($schedulesRpl2 as $s) {
                Schedule::updateOrCreate(
                    [
                        'class_id' => $class2->id,
                        'day' => $day,
                        'start_time' => $s['start'],
                        'end_time' => $s['end'],
                    ],
                    [
                        'subject_id' => $s['subject_id'],
                        'teacher_id' => $s['teacher_id'],
                        'room' => $s['room'],
                    ]
                );
            }
        }

        // 8. Tepat 3 Siswa Regular untuk XI RPL 1 (Semua Role SISWA, Sekre Belum Ditentukan)
        $studentNamesRpl1 = [
            ['name' => 'Ahmad Fauzi Rahman', 'nis' => '1001', 'email' => 'siswa1@smkn71.sch.id', 'phone' => '081211110001'],
            ['name' => 'Adinda Salsa Maharani', 'nis' => '1002', 'email' => 'siswa2@smkn71.sch.id', 'phone' => '081211110002'],
            ['name' => 'Raffi Ahmad', 'nis' => '1003', 'email' => 'siswa3@smkn71.sch.id', 'phone' => '081211110003'],
        ];

        $studentsRpl1 = [];
        foreach ($studentNamesRpl1 as $stdData) {
            $std = User::updateOrCreate(
                ['email' => $stdData['email']],
                [
                    'name' => $stdData['name'],
                    'password' => $defaultPassword,
                    'phone' => $stdData['phone'],
                    'nip_nis' => $stdData['nis'],
                    'role' => 'SISWA', // Regular, sekre belum ditentukan
                    'class_id' => $class1->id,
                    'is_active' => true,
                ]
            );
            $studentsRpl1[] = $std;
        }

        // 9. Tepat 3 Siswa Regular untuk XI RPL 2 (Semua Role SISWA, Sekre Belum Ditentukan)
        $studentNamesRpl2 = [
            ['name' => 'Bagus Tri Pamungkas', 'nis' => '2001', 'email' => 'rpl2.siswa1@smkn71.sch.id', 'phone' => '081222220001'],
            ['name' => 'Bella Safira Wardani', 'nis' => '2002', 'email' => 'rpl2.siswa2@smkn71.sch.id', 'phone' => '081222220002'],
            ['name' => 'Siti Aisyah', 'nis' => '2003', 'email' => 'rpl2.siswa3@smkn71.sch.id', 'phone' => '081222220003'],
        ];

        $studentsRpl2 = [];
        foreach ($studentNamesRpl2 as $stdData) {
            $std = User::updateOrCreate(
                ['email' => $stdData['email']],
                [
                    'name' => $stdData['name'],
                    'password' => $defaultPassword,
                    'phone' => $stdData['phone'],
                    'nip_nis' => $stdData['nis'],
                    'role' => 'SISWA', // Regular, sekre belum ditentukan
                    'class_id' => $class2->id,
                    'is_active' => true,
                ]
            );
            $studentsRpl2[] = $std;
        }

        // Catatan: Akun Orang Tua ditiadakan (belum dibuat)

        // 10. Sesi Presensi Mandiri Hari Ini (Rabu) untuk Kedua Kelas
        $today = Carbon::today()->format('Y-m-d');

        // Presensi XI RPL 1
        $session1 = AttendanceSession::where('class_id', $class1->id)->whereDate('date', $today)->first();
        if (!$session1) {
            $session1 = AttendanceSession::create([
                'class_id' => $class1->id,
                'date' => $today,
                'status' => 'LOCKED',
                'verification_status' => 'TERVERIFIKASI',
                'school_hours' => '06:45 - 15:00',
                'room' => 'R.402',
                'locked_at' => Carbon::now(),
            ]);
        }

        foreach ($studentsRpl1 as $i => $s) {
            $status = ($i === 2) ? 'IZIN' : 'HADIR';
            $note = ($i === 2) ? 'Izin Dispensasi Lomba' : 'Presensi Mandiri Pukul 06:4' . $i . ' WIB';

            AttendanceRecord::updateOrCreate(
                ['session_id' => $session1->id, 'student_id' => $s->id],
                [
                    'status' => $status,
                    'note' => $note,
                ]
            );
        }

        // Presensi XI RPL 2
        $session2 = AttendanceSession::where('class_id', $class2->id)->whereDate('date', $today)->first();
        if (!$session2) {
            $session2 = AttendanceSession::create([
                'class_id' => $class2->id,
                'date' => $today,
                'status' => 'LOCKED',
                'verification_status' => 'TERVERIFIKASI',
                'school_hours' => '06:45 - 15:00',
                'room' => 'R.406',
                'locked_at' => Carbon::now(),
            ]);
        }

        foreach ($studentsRpl2 as $i => $s) {
            $status = ($i === 1) ? 'SAKIT' : 'HADIR';
            $note = ($i === 1) ? 'Sakit' : 'Presensi Mandiri Pukul 06:4' . ($i + 1) . ' WIB';

            AttendanceRecord::updateOrCreate(
                ['session_id' => $session2->id, 'student_id' => $s->id],
                [
                    'status' => $status,
                    'note' => $note,
                ]
            );
        }
    }
}
