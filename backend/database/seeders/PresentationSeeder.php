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
     * Data Set Ringkas Khusus Presentasi SMKN 71 Jakarta:
     * - 1 Superadmin: Dr. H. Bambang Soeprapto, M.Pd
     * - 3 Guru Pengajar & Wali Kelas:
     *     1. Nurhayatul Fadilla, S.Pd (Wali Kelas XI RPL 1 & Guru MKK-RPL)
     *     2. Nurina Kartika Ayu, S.Pd (Wali Kelas XI RPL 2 & Guru PJOK/Kejuruan)
     *     3. Faradillah Aryani, S.T (Guru Pengajar KIK-RPL / PKK)
     * - 2 Kelas: XI RPL 1 dan XI RPL 2
     * - Siswa: 36 siswa per kelas (NIS 1001-1036 dan 2001-2036)
     * - Jadwal Pelajaran Hari Rabu (Aktif Real-time Sesuai Jadwal SMKN 71)
     * - Sesi Presensi Mandiri Hari Ini (Auto-Verified)
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

        // 2. Konfigurasi Instansi Resmi SMKN 71 Jakarta
        Setting::set('school_name', 'SMK Negeri 71 Jakarta', 'general', 'Nama Resmi Sekolah');
        Setting::set('school_npsn', '20104567', 'general', 'Nomor Pokok Sekolah Nasional');
        Setting::set('school_address', 'Jl. Raden Inten II No. 71, Duren Sawit, Jakarta Timur', 'general', 'Alamat Sekolah');
        Setting::set('headmaster_name', 'Dr. H. Bambang Soeprapto, M.Pd', 'general', 'Nama Kepala Sekolah');
        Setting::set('headmaster_nip', '197505101999031002', 'general', 'NIP Kepala Sekolah');
        Setting::set('default_school_hours', '06:45 - 15:00', 'academic', 'Jam Masuk Sekolah Standar');
        Setting::set('attendance_lock_pin', '1234', 'security', 'PIN Buka Kunci Presensi');

        // 3. Dua Kelas Utama: XI RPL 1 dan XI RPL 2
        $class1 = SchoolClass::updateOrCreate(
            ['name' => 'XI RPL 1'],
            ['major' => 'Rekayasa Perangkat Lunak']
        );

        $class2 = SchoolClass::updateOrCreate(
            ['name' => 'XI RPL 2'],
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

        // 5. Tepat 3 Guru Pengajar & Walas Sesuai Permintaan
        // Guru 1: Nurhayatul Fadilla, S.Pd (Walas XI RPL 1 & Guru MKK-RPL)
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
        $class1->update(['advisor_id' => $guru1->id]);

        // Guru 2: Nurina Kartika Ayu, S.Pd (Walas XI RPL 2 & Guru PJOK/Kejuruan)
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
        $class2->update(['advisor_id' => $guru2->id]);

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

        // 7. Jadwal Pelajaran Hari Rabu (Rabu / Wednesday) & Hari Lainnya
        $days = ['Rabu', 'Wednesday', 'Senin', 'Selasa', 'Kamis', 'Jumat', 'Monday', 'Tuesday', 'Thursday', 'Friday'];

        // Jadwal XI RPL 1
        $schedulesRpl1 = [
            ['subject_id' => $mapelMkk->id, 'teacher_id' => $guru1->id, 'start' => '06:45', 'end' => '09:45', 'room' => 'R.402'],
            ['subject_id' => $mapelMkk->id, 'teacher_id' => $guru1->id, 'start' => '10:00', 'end' => '12:15', 'room' => 'R.402'],
            ['subject_id' => $mapelPkk->id, 'teacher_id' => $guru3->id, 'start' => '12:45', 'end' => '15:00', 'room' => 'R.405'],
        ];

        // Jadwal XI RPL 2
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

        // 8. Siswa XI RPL 1 (Tepat 36 Siswa: 1 Sekre Raffi Ahmad + 35 Siswa)
        $studentNamesRpl1 = [
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

        $studentsRpl1 = [];
        foreach ($studentNamesRpl1 as $idx => $rawName) {
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
                    'class_id' => $class1->id,
                    'is_active' => true,
                ]
            );
            $studentsRpl1[] = $std;
        }

        // 9. Siswa XI RPL 2 (Tepat 36 Siswa: 1 Sekre + 35 Siswa)
        $studentNamesRpl2 = [
            'Siti Aisyah (Sekretaris XI RPL 2)',
            'Bagus Tri Pamungkas',
            'Bella Safira Wardani',
            'Chandra Wijaya',
            'Citra Kirana Lestari',
            'Dendi Santoso',
            'Dini Kurniawati',
            'Eko Prasetyo',
            'Elvira Devinamira',
            'Fahri Hamzah Pratama',
            'Febby Rastanty',
            'Galih Ginanjar',
            'Ghea Indrawari',
            'Gilang Dirga',
            'Habibi Nur Rahman',
            'Hana Saraswati',
            'Hary Tanoe Wicaksono',
            'Imam Darto',
            'Intan Nuraini',
            'Jefri Nichol',
            'Karin Novilda',
            'Lukman Sardi',
            'Luna Maya Safitri',
            'Marcell Siahaan',
            'Maudy Ayunda Putri',
            'Morgan Oey Pratama',
            'Najwa Shihab Hidayat',
            'Nicholas Saputra',
            'Olla Ramlan',
            'Pevita Pearce',
            'Reza Rahadian Pratama',
            'Sule Sutisna',
            'Tasya Kamila',
            'Vino G Bastian',
            'Wulan Guritno',
            'Yuki Kato Paramitha',
        ];

        $studentsRpl2 = [];
        foreach ($studentNamesRpl2 as $idx => $rawName) {
            $num = $idx + 1;
            $nis = 2000 + $num;
            $isSekre = ($idx === 0);
            $cleanName = str_replace(' (Sekretaris XI RPL 2)', '', $rawName);
            $email = $isSekre ? 'sekretaris.rpl2@smkn71.sch.id' : ('rpl2.siswa' . $num . '@smkn71.sch.id');

            $std = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $cleanName,
                    'password' => $defaultPassword,
                    'phone' => '0813' . str_pad($num, 8, '0', STR_PAD_LEFT),
                    'nip_nis' => (string)$nis,
                    'role' => $isSekre ? 'SEKRETARIS' : 'SISWA',
                    'class_id' => $class2->id,
                    'is_active' => true,
                ]
            );
            $studentsRpl2[] = $std;
        }

        // 10. Akun Orang Tua Siswa
        $parentPairs = [
            ['email' => 'ortu.raffi@smkn71.sch.id', 'name' => 'Bapak/Ibu Raffi Ahmad', 'std' => $studentsRpl1[0]],
            ['email' => 'ortu.ahmad@smkn71.sch.id', 'name' => 'Bapak/Ibu Ahmad Fauzi', 'std' => $studentsRpl1[2]],
            ['email' => 'ortu.tiara@smkn71.sch.id', 'name' => 'Bapak/Ibu Tiara Andini', 'std' => $studentsRpl1[33]],
            ['email' => 'ortu.aisyah@smkn71.sch.id', 'name' => 'Bapak/Ibu Siti Aisyah', 'std' => $studentsRpl2[0]],
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
                    'class_id' => $p['std']->class_id,
                    'is_active' => true,
                ]
            );
        }

        // 11. Sesi Presensi Mandiri Hari Ini (Rabu) untuk Kedua Kelas
        $today = Carbon::today()->toDateString();

        // Presensi Hari Ini XI RPL 1
        $session1 = AttendanceSession::firstOrCreate(
            ['class_id' => $class1->id, 'date' => $today],
            [
                'status' => 'LOCKED',
                'verification_status' => 'TERVERIFIKASI',
                'school_hours' => '06:45 - 15:00',
                'room' => 'R.402',
                'locked_at' => Carbon::now(),
            ]
        );

        foreach ($studentsRpl1 as $i => $s) {
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
                ['session_id' => $session1->id, 'student_id' => $s->id],
                [
                    'status' => $status,
                    'note' => $note,
                ]
            );
        }

        // Presensi Hari Ini XI RPL 2
        $session2 = AttendanceSession::firstOrCreate(
            ['class_id' => $class2->id, 'date' => $today],
            [
                'status' => 'LOCKED',
                'verification_status' => 'TERVERIFIKASI',
                'school_hours' => '06:45 - 15:00',
                'room' => 'R.406',
                'locked_at' => Carbon::now(),
            ]
        );

        foreach ($studentsRpl2 as $i => $s) {
            $status = 'HADIR';
            $note = 'Presensi Mandiri Pukul 06:4' . (($i + 2) % 10) . ' WIB';
            if ($i === 8) {
                $status = 'SAKIT';
                $note = 'Sakit';
            } elseif ($i === 15) {
                $status = 'IZIN';
                $note = 'Izin Urusan Keluarga';
            }

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
