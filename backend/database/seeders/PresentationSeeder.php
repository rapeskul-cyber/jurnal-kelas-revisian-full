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
     * Data Set Ringkas Khusus Presentasi:
     * - 1 Superadmin
     * - 3 Guru Pengajar & Wali Kelas
     * - 1 Kelas: XI RPL 1 (Tepat 36 Siswa: 1 Sekre + 35 Siswa)
     * - 3 Akun Orang Tua
     * - Jadwal & Sesi Presensi Hari Ini
     */
    public function run(): void
    {
        $defaultPassword = Hash::make('password123');

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

        // 2. Konfigurasi Instansi Resmi
        Setting::set('school_name', 'SMK Negeri 71 Jakarta', 'general', 'Nama Resmi Sekolah');
        Setting::set('school_npsn', '20104567', 'general', 'Nomor Pokok Sekolah Nasional');
        Setting::set('school_address', 'Jl. Raden Inten II No. 71, Duren Sawit, Jakarta Timur', 'general', 'Alamat Sekolah');
        Setting::set('headmaster_name', 'Dr. H. Bambang Soeprapto, M.Pd', 'general', 'Nama Kepala Sekolah');
        Setting::set('headmaster_nip', '197505101999031002', 'general', 'NIP Kepala Sekolah');
        Setting::set('default_school_hours', '07:30 - 15:30', 'academic', 'Jam Masuk Sekolah Standar');
        Setting::set('attendance_lock_pin', '1234', 'security', 'PIN Buka Kunci Presensi');

        // 3. 1 Kelas Utama: XI RPL 1
        $class = SchoolClass::updateOrCreate(
            ['name' => 'XI RPL 1'],
            ['major' => 'Rekayasa Perangkat Lunak']
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
        // Guru 1: Walas XI RPL 1 + Guru Pemrograman Web
        $guru1 = User::updateOrCreate(
            ['email' => 'fadilah@smkn71.sch.id'],
            [
                'name' => 'Fadilah, S.Pd',
                'password' => $defaultPassword,
                'phone' => '081210000002',
                'nip_nis' => '198503122010012015',
                'role' => 'GURU_PEMBIMBING',
                'specialization' => 'Pemrograman Web & Perangkat Bergerak',
                'class_id' => $class->id,
                'is_active' => true,
            ]
        );
        $class->update(['advisor_id' => $guru1->id]);

        // Guru 2: Guru Basis Data
        $guru2 = User::updateOrCreate(
            ['email' => 'bambang.guru@smkn71.sch.id'],
            [
                'name' => 'Bambang H., M.Kom',
                'password' => $defaultPassword,
                'phone' => '081210000003',
                'nip_nis' => '198007152006041008',
                'role' => 'GURU_PENGAJAR',
                'specialization' => 'Basis Data & Cloud Database',
                'is_active' => true,
            ]
        );

        // Guru 3: Guru Pemodelan Perangkat Lunak
        $guru3 = User::updateOrCreate(
            ['email' => 'siti.rahma@smkn71.sch.id'],
            [
                'name' => 'Siti Rahma, S.T',
                'password' => $defaultPassword,
                'phone' => '081210000004',
                'nip_nis' => '198811202014022003',
                'role' => 'GURU_PENGAJAR',
                'specialization' => 'Pemodelan Perangkat Lunak (PPL)',
                'is_active' => true,
            ]
        );

        // 6. Mata Pelajaran
        $mapelWeb = Subject::firstOrCreate(['code' => 'PWEB-01'], ['name' => 'Pemrograman Web']);
        $mapelDb = Subject::firstOrCreate(['code' => 'BD-01'], ['name' => 'Basis Data']);
        $mapelPpl = Subject::firstOrCreate(['code' => 'PPL-01'], ['name' => 'Pemodelan Perangkat Lunak']);

        // 7. Jadwal Pelajaran (Hari Ini / Senin-Jumat)
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $scheduleItems = [
            ['subject_id' => $mapelWeb->id, 'teacher_id' => $guru1->id, 'time' => '07:00 - 08:30', 'room' => 'Lab RPL 1'],
            ['subject_id' => $mapelDb->id, 'teacher_id' => $guru2->id, 'time' => '08:30 - 10:00', 'room' => 'Lab RPL 1'],
            ['subject_id' => $mapelPpl->id, 'teacher_id' => $guru3->id, 'time' => '10:15 - 12:00', 'room' => 'Lab RPL 1'],
            ['subject_id' => $mapelWeb->id, 'teacher_id' => $guru1->id, 'time' => '12:45 - 14:15', 'room' => 'Lab RPL 1'],
        ];

        foreach ($days as $day) {
            foreach ($scheduleItems as $idx => $s) {
                Schedule::updateOrCreate(
                    [
                        'class_id' => $class->id,
                        'day' => $day,
                        'time_slot' => $s['time'],
                    ],
                    [
                        'subject_id' => $s['subject_id'],
                        'teacher_id' => $s['teacher_id'],
                        'room' => $s['room'],
                        'term_id' => $term->id,
                    ]
                );
            }
        }

        // 8. Tepat 36 Siswa (1 Sekretaris + 35 Siswa)
        $studentNames = [
            'Raffi Ahmad (Sekretaris)',
            'Adinda Salsa Maharani',
            'Ahmad Fauzi Rahman',
            'Aisyah Nur Rohmah',
            'Aldy Rizky Pratama',
            'Amanda Clara Shinta',
            'Arya Bima Sena',
            'Bayu Pratama Yudha',
            'Cantika Dewi Lestari',
            'Danuarta Wicaksana',
            'Dhea Ananda Putri',
            'Dimas Anggara Saputra',
            'Fajar Maulana Ibrahim',
            'Farhan Kamil Nugraha',
            'Gita Gutawa Permata',
            'Hafiz Ramadhan Al-Fikri',
            'Hendra Wijaya Kusuma',
            'Ilham Saputra Pratama',
            'Indah Permata Sari',
            'Kevin Sanjaya Sukamuljo',
            'Lutfi Hakim Ramadhan',
            'Maya Safitri Gunawan',
            'Muhammad Rian Ardianto',
            'Nabila Syakieb Maharani',
            'Nadia Vega Putri',
            'Putra Ramadhan Santoso',
            'Raditya Dika Firmansyah',
            'Raisa Andriana Dewi',
            'Riko Simanjuntak',
            'Rizky Febian Maulana',
            'Sarah Sechan Melati',
            'Syifa Hadju Apsari',
            'Taufik Hidayat Alamsyah',
            'Tiara Andini Maharani',
            'Yoga Pratama Aditya',
            'Zaskia Sungkar Paramitha',
        ];

        $studentEntities = [];
        foreach ($studentNames as $idx => $rawName) {
            $num = $idx + 1;
            $nis = 1000 + $num;
            $isSekre = ($idx === 0);
            $cleanName = str_replace(' (Sekretaris)', '', $rawName);
            $email = $isSekre ? 'sekretaris@smkn71.sch.id' : ('siswa' . $num . '@smkn71.sch.id');

            $std = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $cleanName,
                    'password' => $defaultPassword,
                    'phone' => '0812' . str_pad($num, 8, '0', STR_PAD_LEFT),
                    'nip_nis' => (string)$nis,
                    'role' => $isSekre ? 'SEKRETARIS' : 'SISWA',
                    'class_id' => $class->id,
                    'is_active' => true,
                ]
            );
            $studentEntities[] = $std;
        }

        // 9. 3 Akun Orang Tua
        $parentPairs = [
            ['email' => 'ortu.raffi@smkn71.sch.id', 'name' => 'Bapak/Ibu Raffi Ahmad', 'std' => $studentEntities[0]],
            ['email' => 'ortu.ahmad@smkn71.sch.id', 'name' => 'Bapak/Ibu Ahmad Fauzi', 'std' => $studentEntities[2]],
            ['email' => 'ortu.tiara@smkn71.sch.id', 'name' => 'Bapak/Ibu Tiara Andini', 'std' => $studentEntities[33]],
        ];

        foreach ($parentPairs as $idx => $p) {
            User::updateOrCreate(
                ['email' => $p['email']],
                [
                    'name' => $p['name'],
                    'password' => $defaultPassword,
                    'phone' => '08139000000' . ($idx + 1),
                    'nip_nis' => (string)$p['std']->nip_nis,
                    'student_id' => $p['std']->id,
                    'role' => 'ORANG_TUA',
                    'class_id' => $class->id,
                    'is_active' => true,
                ]
            );
        }

        // 10. Sesi Presensi Mandiri Hari Ini (Auto-Verified)
        $today = Carbon::today()->toDateString();
        $session = AttendanceSession::firstOrCreate(
            ['class_id' => $class->id, 'date' => $today],
            [
                'status' => 'LOCKED',
                'verification_status' => 'TERVERIFIKASI',
                'school_hours' => '07:30 - 15:30',
                'room' => 'Lab RPL 1',
                'locked_at' => Carbon::now(),
            ]
        );

        // Buat sample presensi mandiri realistis untuk 36 siswa
        foreach ($studentEntities as $i => $s) {
            $status = 'HADIR';
            $note = 'Presensi Mandiri Pukul 06:4' . ($i % 10) . ' WIB';
            if ($i === 5) {
                $status = 'SAKIT';
                $note = 'Sakit (Surat Dokter Terverifikasi)';
            } elseif ($i === 12) {
                $status = 'IZIN';
                $note = 'Izin Dispensasi Lomba';
            }

            AttendanceRecord::updateOrCreate(
                ['session_id' => $session->id, 'student_id' => $s->id],
                [
                    'status' => $status,
                    'note' => $note,
                ]
            );
        }
    }
}

