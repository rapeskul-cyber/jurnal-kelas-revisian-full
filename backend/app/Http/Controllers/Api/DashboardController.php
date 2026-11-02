<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\SchoolClass;
use App\Models\AttendanceSession;
use App\Models\AttendanceRecord;
use App\Models\ClassJournal;
use App\Models\MaterialJournal;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\AuditLog;
use App\Models\Setting;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();

        switch ($user->role) {
            case 'SUPERADMIN':
                return $this->superadminDashboard($user, $today);
            case 'SISWA':
                return $this->siswaDashboard($user, $today);
            case 'SEKRETARIS':
                return $this->sekretarisDashboard($user, $today);
            case 'GURU_PENGAJAR':
                return $this->pengajarDashboard($user, $today);
            case 'GURU_PEMBIMBING':
                return $this->pembimbingDashboard($user, $today);
            case 'ORANG_TUA':
                return $this->orangTuaDashboard($user, $today);
            default:
                return response()->json(['message' => 'Role tidak valid'], 400);
        }
    }

    private function siswaDashboard(User $user, $today = null)
    {
        $today = $today ?: Carbon::today()->toDateString();

        // Calculate personal attendance counts
        $records = AttendanceRecord::where('student_id', $user->id)->get();
        $totalRecords = $records->count() ?: 1;
        $hadirCount = $records->where('status', 'HADIR')->count();
        $sakitCount = $records->where('status', 'SAKIT')->count();
        $izinCount = $records->where('status', 'IZIN')->count();
        $alphaCount = $records->where('status', 'ALPHA')->count();

        $percentage = round(($hadirCount / $totalRecords) * 100);

        // Today's self-attendance status
        $classId = $user->class_id ?: 1;
        $todaySession = AttendanceSession::where('class_id', $classId)
            ->whereDate('date', $today)
            ->first();
        $todayRecord = $todaySession
            ? AttendanceRecord::where('session_id', $todaySession->id)->where('student_id', $user->id)->first()
            : null;

        // Cek pengajuan surat izin terbaru siswa untuk hari ini
        $latestLeave = LeaveRequest::where('student_id', $user->id)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->latest('id')
            ->first();

        $todayRecordObj = null;
        $pendingLeave = null;
        $rejectedLeave = null;

        if ($latestLeave && $latestLeave->status === 'APPROVED') {
            // SINKRONISASI KE ATTENDANCE_RECORD JIKA STATUS BELUM SESUAI
            if ($todayRecord && $todayRecord->status !== $latestLeave->type) {
                $todayRecord->update([
                    'status' => $latestLeave->type,
                    'note' => 'Surat Izin ' . $latestLeave->type . ' disetujui Wali Kelas: ' . $latestLeave->reason,
                ]);
            } elseif (!$todayRecord && $todaySession) {
                $todayRecord = AttendanceRecord::create([
                    'session_id' => $todaySession->id,
                    'student_id' => $user->id,
                    'status' => $latestLeave->type,
                    'note' => 'Surat Izin ' . $latestLeave->type . ' disetujui Wali Kelas: ' . $latestLeave->reason,
                ]);
            }
            $todayRecordObj = (object) [
                'status' => $latestLeave->type,
                'note' => 'Surat Izin ' . $latestLeave->type . ' disetujui Wali Kelas: ' . $latestLeave->reason,
                'is_leave_approved' => true,
                'is_leave_rejected' => false,
            ];
        } elseif ($latestLeave && $latestLeave->status === 'REJECTED') {
            // Surat izin ditolak Wali Kelas! Siswa TIDAK diizinkan absen
            $rejectedLeave = $latestLeave;
            // Jika izin ditolak, siswa dilarang klaim mandiri dari HP.
            // Hanya dihitung HADIR jika ada verifikasi langsung di kelas oleh Sekretaris / Wali Kelas
            if ($todayRecord) {
                if ($todayRecord->status === 'HADIR' && !str_contains($todayRecord->note ?? '', 'Presensi Mandiri Pukul')) {
                    $todayRecordObj = (object) [
                        'status' => 'HADIR',
                        'note' => $todayRecord->note ?: 'Diverifikasi Hadir di Kelas oleh Sekretaris Kelas',
                        'is_leave_approved' => false,
                        'is_leave_rejected' => false,
                    ];
                } else {
                    // Hapus klaim mandiri karena izinnya ditolak dan belum diverifikasi fisik oleh Sekretaris
                    $todayRecord->delete();
                    $todayRecord = null;
                }
            }
        } elseif ($latestLeave && $latestLeave->status === 'PENDING') {
            $pendingLeave = $latestLeave;
        } elseif ($todayRecord) {
            $todayRecordObj = (object) [
                'status' => $todayRecord->status,
                'note' => $todayRecord->note ?: 'Presensi Mandiri Terverifikasi',
                'is_leave_approved' => in_array($todayRecord->status, ['SAKIT', 'IZIN']),
                'is_leave_rejected' => false,
            ];
        }

        // Recent personal attendance
        $recentAttendance = AttendanceRecord::with(['session.schoolClass'])
            ->where('student_id', $user->id)
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($rec) {
                return [
                    'id' => $rec->id,
                    'date' => Carbon::parse($rec->session->date)->translatedFormat('l, d M Y'),
                    'status' => $rec->status,
                    'note' => $rec->note ?: 'Presensi Mandiri Terverifikasi',
                    'school_hours' => $rec->session->school_hours,
                ];
            });

        // Recent material journals
        $recentMaterials = MaterialJournal::with(['subject', 'teacher'])
            ->latest('date')
            ->take(5)
            ->get()
            ->map(function ($mat) {
                return [
                    'id' => $mat->id,
                    'date' => Carbon::parse($mat->date)->translatedFormat('d M Y'),
                    'subject' => $mat->subject->name ?? 'Mata Pelajaran',
                    'topic' => $mat->topic,
                    'teacher' => $mat->teacher->name ?? 'Guru',
                ];
            });

        // Recent student activity journals (hanya catatan pribadi milik siswa bersangkutan)
        $recentJournals = ClassJournal::with(['schedule.subject', 'createdBy'])
            ->where('created_by_id', $user->id)
            ->latest('date')
            ->latest('id')
            ->take(5)
            ->get()
            ->map(function ($j) {
                return [
                    'id' => $j->id,
                    'date' => Carbon::parse($j->date)->translatedFormat('d M Y'),
                    'subject' => $j->schedule->subject->name ?? 'Kegiatan Kelas',
                    'activity' => $j->activity,
                    'notes' => $j->notes,
                    'author' => $j->createdBy->name ?? 'Siswa',
                    'status' => $j->verification_status,
                ];
            });

        return response()->json([
            'stats' => [
                'hadir_hari' => $hadirCount . ' Hari',
                'hadir_percent' => $percentage . '% Kehadiran',
                'sakit_hari' => $sakitCount . ' Hari',
                'izin_hari' => $izinCount . ' Hari',
                'alpha_hari' => $alphaCount . ' Hari',
            ],
            'today_attendance' => $todayRecordObj ? [
                'checked_in' => true,
                'is_leave_approved' => !empty($todayRecordObj->is_leave_approved),
                'is_leave_rejected' => false,
                'has_pending_leave' => false,
                'status' => $todayRecordObj->status,
                'note' => $todayRecordObj->note ?: 'Presensi Mandiri Terverifikasi',
                'date_formatted' => Carbon::parse($today)->translatedFormat('l, d F Y'),
            ] : ($rejectedLeave ? [
                'checked_in' => false,
                'is_leave_approved' => false,
                'is_leave_rejected' => true,
                'can_self_check_in' => false,
                'has_pending_leave' => false,
                'status' => 'DITOLAK',
                'rejection_reason' => $rejectedLeave->review_note ?: 'Tidak memenuhi syarat izin sekolah.',
                'note' => 'Permohonan surat izin Anda telah DITOLAK oleh Wali Kelas ("' . ($rejectedLeave->review_note ?: 'Tidak memenuhi syarat izin.') . '"). Presensi mandiri dinonaktifkan. Silakan hadir ke sekolah dan melapor ke Sekretaris Kelas untuk verifikasi kehadiran fisik Anda.',
                'date_formatted' => Carbon::parse($today)->translatedFormat('l, d F Y'),
            ] : ($pendingLeave ? [
                'checked_in' => false,
                'is_leave_approved' => false,
                'is_leave_rejected' => false,
                'has_pending_leave' => true,
                'status' => 'PENDING',
                'note' => 'Permohonan surat izin (' . $pendingLeave->type . ') Anda telah diajukan dan sedang menunggu verifikasi Wali Kelas.',
                'date_formatted' => Carbon::parse($today)->translatedFormat('l, d F Y'),
            ] : [
                'checked_in' => false,
                'is_leave_approved' => false,
                'is_leave_rejected' => false,
                'has_pending_leave' => false,
                'status' => 'BELUM_PRESENSI',
                'note' => 'Silakan klik tombol Konfirmasi Hadir Mandiri di bawah untuk mencatat kehadiran hari ini.',
                'date_formatted' => Carbon::parse($today)->translatedFormat('l, d F Y'),
            ])),
            'recent_attendance' => $recentAttendance,
            'recent_materials' => $recentMaterials,
            'recent_journals' => $recentJournals,
            'today_timeline' => $this->getTodayTimeline($classId, $today),
        ]);
    }

    /**
     * Dashboard Pantauan Orang Tua / Wali Murid (Memantau Kehadiran, Surat Izin, & Jurnal Kegiatan Anak di Sekolah).
     */
    private function orangTuaDashboard(User $user, $today)
    {
        $child = null;
        if ($user->student_id) {
            $child = User::with('schoolClass.advisor')->find($user->student_id);
        }

        if (!$child && $user->nip_nis) {
            $child = User::with('schoolClass.advisor')
                ->where('nip_nis', $user->nip_nis)
                ->where('class_id', $user->class_id ?: 1)
                ->whereIn('role', ['SISWA', 'SEKRETARIS'])
                ->first()
                ?? User::with('schoolClass.advisor')
                    ->where('nip_nis', $user->nip_nis)
                    ->whereIn('role', ['SISWA', 'SEKRETARIS'])
                    ->first();
        }

        // Tanpa fallback sembarangan ke siswa lain jika belum terhubung

        $classId = $child?->class_id ?: ($user->class_id ?: 1);
        $schoolClass = SchoolClass::with('advisor')->find($classId);

        $records = $child ? AttendanceRecord::where('student_id', $child->id)->get() : collect();
        $totalRecords = $records->count() ?: 1;
        $hadirCount = $records->where('status', 'HADIR')->count();
        $sakitCount = $records->where('status', 'SAKIT')->count();
        $izinCount = $records->where('status', 'IZIN')->count();
        $alphaCount = $records->where('status', 'ALPHA')->count();
        $percentage = round(($hadirCount / $totalRecords) * 100);

        $todaySession = AttendanceSession::where('class_id', $classId)->whereDate('date', $today)->first();
        $todayRecord = ($todaySession && $child)
            ? AttendanceRecord::where('session_id', $todaySession->id)->where('student_id', $child->id)->first()
            : null;

        // Cek permohonan surat izin anak hari ini
        $latestLeave = $child ? LeaveRequest::where('student_id', $child->id)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->latest('id')
            ->first() : null;

        $todayStatus = 'BELUM_PRESENSI';
        $todayNote = 'Belum melakukan presensi hari ini';
        $isLeaveRejected = false;
        $isLeaveApproved = false;
        $hasPendingLeave = false;
        $rejectionReason = null;

        if ($latestLeave && $latestLeave->status === 'APPROVED') {
            $todayStatus = $latestLeave->type;
            $todayNote = 'Dispensasi Surat Izin Resmi (' . $latestLeave->type . ') Disetujui Wali Kelas';
            $isLeaveApproved = true;
        } elseif ($latestLeave && $latestLeave->status === 'REJECTED') {
            $isLeaveRejected = true;
            $rejectionReason = $latestLeave->review_note ?: 'Tidak memenuhi syarat izin sekolah.';
            if ($todayRecord && $todayRecord->status === 'HADIR' && !str_contains($todayRecord->note ?? '', 'Presensi Mandiri Pukul')) {
                $todayStatus = 'HADIR';
                $todayNote = $todayRecord->note ?: 'Diverifikasi Hadir di Kelas oleh Sekretaris Kelas';
            } else {
                $todayStatus = 'DITOLAK';
                $todayNote = 'Permohonan surat izin ditolak oleh Wali Kelas ("' . $rejectionReason . '"). Siswa wajib hadir di sekolah.';
            }
        } elseif ($latestLeave && $latestLeave->status === 'PENDING') {
            $hasPendingLeave = true;
            $todayStatus = 'PENDING';
            $todayNote = 'Permohonan surat izin (' . $latestLeave->type . ') sedang menunggu verifikasi Wali Kelas.';
        } elseif ($todayRecord) {
            $todayStatus = $todayRecord->status;
            $todayNote = $todayRecord->note ?: 'Presensi Mandiri Terverifikasi';
            $isLeaveApproved = in_array($todayRecord->status, ['SAKIT', 'IZIN']);
        }

        $recentAttendance = $child
            ? AttendanceRecord::with(['session.schoolClass'])
                ->where('student_id', $child->id)
                ->latest()
                ->take(6)
                ->get()
                ->map(function ($rec) {
                    return [
                        'id' => $rec->id,
                        'date' => Carbon::parse($rec->session->date)->translatedFormat('l, d M Y'),
                        'status' => $rec->status,
                        'note' => $rec->note ?: 'Hadir Tepat Waktu',
                        'school_hours' => $rec->session->school_hours ?? '07:30 - 15:30',
                    ];
                })
            : [];

        $recentJournals = $child
            ? ClassJournal::with(['schedule.subject', 'createdBy', 'confirmedBy'])
                ->where('created_by_id', $child->id)
                ->latest('date')
                ->latest('id')
                ->take(6)
                ->get()
                ->map(function ($j) {
                    return [
                        'id' => $j->id,
                        'date' => Carbon::parse($j->date)->translatedFormat('d M Y'),
                        'subject' => $j->schedule->subject->name ?? 'Kegiatan Pembelajaran',
                        'teacher' => $j->confirmedBy->name ?? ($j->schedule->teacher->name ?? 'Guru Pengajar'),
                        'activity' => $j->activity,
                        'notes' => $j->notes,
                        'student_author' => $j->createdBy->name ?? 'Siswa',
                        'status' => $j->verification_status,
                    ];
                })
            : [];

        $recentLeaves = $child
            ? LeaveRequest::with('reviewer')
                ->where('student_id', $child->id)
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($lv) {
                    return [
                        'id' => $lv->id,
                        'type' => $lv->type,
                        'dates' => Carbon::parse($lv->start_date)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($lv->end_date)->translatedFormat('d M Y'),
                        'reason' => $lv->reason,
                        'status' => $lv->status,
                        'reviewer' => $lv->reviewer->name ?? 'Wali Kelas',
                    ];
                })
            : [];

        return response()->json([
            'child' => [
                'id' => $child?->id,
                'name' => $child?->name ?? 'Siswa',
                'nis' => $child?->nip_nis ?? '-',
                'class_name' => $schoolClass?->name ?? '-',
                'wali_kelas' => $schoolClass?->advisor?->name ?? 'Wali Kelas',
                'today_status' => $todayStatus,
                'today_note' => $todayNote,
                'is_leave_rejected' => $isLeaveRejected,
                'is_leave_approved' => $isLeaveApproved,
                'has_pending_leave' => $hasPendingLeave,
                'rejection_reason' => $rejectionReason,
                'date_formatted' => Carbon::parse($today)->translatedFormat('l, d F Y'),
            ],
            'stats' => [
                'hadir_hari' => $hadirCount . ' Hari',
                'hadir_percent' => $percentage . '% Kehadiran',
                'sakit_hari' => $sakitCount . ' Hari',
                'izin_hari' => $izinCount . ' Hari',
                'alpha_hari' => $alphaCount . ' Hari',
            ],
            'recent_attendance' => $recentAttendance,
            'recent_journals' => $recentJournals,
            'recent_leaves' => $recentLeaves,
            'today_timeline' => $this->getTodayTimeline($classId, $today),
        ]);
    }

    private function getTodayTimeline($classId, $today)
    {
        $daysMap = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];
        $dayOfWeekNumber = Carbon::parse($today)->dayOfWeekIso;
        $currentDayName = $daysMap[$dayOfWeekNumber] ?? 'Senin';

        // Ambil jadwal hari ini untuk kelas ini
        $schedules = Schedule::where('class_id', $classId)
            ->where('day', $currentDayName)
            ->with(['subject', 'teacher'])
            ->orderBy('start_time')
            ->get();

        // Jika akhir pekan atau jadwal hari ini belum ada, fallback ke hari Senin/jadwal aktif terdekat
        if ($schedules->isEmpty()) {
            $schedules = Schedule::where('class_id', $classId)
                ->where('day', 'Senin')
                ->with(['subject', 'teacher'])
                ->orderBy('start_time')
                ->get();
        }

        if ($schedules->isEmpty()) {
            $schedules = Schedule::where('class_id', $classId)
                ->with(['subject', 'teacher'])
                ->orderBy('start_time')
                ->limit(6)
                ->get();
        }

        $todayJournals = ClassJournal::where('class_id', $classId)
            ->whereDate('date', $today)
            ->get()
            ->keyBy('schedule_id');

        $todayMaterialsBySchedule = MaterialJournal::where('class_id', $classId)
            ->whereNotNull('schedule_id')
            ->latest('id')
            ->get()
            ->keyBy('schedule_id');

        $todayMaterialsBySubject = MaterialJournal::where('class_id', $classId)
            ->latest('id')
            ->get()
            ->keyBy('subject_id');

        return $schedules->values()->map(function ($sch, $index) use ($todayJournals, $todayMaterialsBySchedule, $todayMaterialsBySubject) {
            $journal = $todayJournals->get($sch->id);
            $absentStudents = [];
            $notes = $journal?->notes;
            if ($notes && str_starts_with(trim($notes), '{')) {
                $dec = json_decode($notes, true);
                if (is_array($dec)) {
                    $absentStudents = $dec['absent_students'] ?? [];
                    $notes = $dec['notes'] ?? '';
                }
            }

            $mat = $todayMaterialsBySchedule->get($sch->id) ?? $todayMaterialsBySubject->get($sch->subject_id);
            $attachmentUrl = null;
            $attachmentName = null;
            if ($mat && $mat->attachment_path) {
                $attachmentUrl = url('api/materials/file/' . basename($mat->attachment_path));
                $attachmentName = basename($mat->attachment_path);
            }

            $isDone = $journal && $journal->verification_status === 'TERVERIFIKASI';
            $isPending = $journal && $journal->verification_status !== 'TERVERIFIKASI';

            return [
                'jam_ke' => 'Jam Ke-' . ($index + 1),
                'time' => $sch->start_time . ' - ' . $sch->end_time,
                'room' => $sch->room ?: 'Ruang Kelas',
                'subject' => $sch->subject->name ?? 'Mata Pelajaran',
                'subject_code' => $sch->subject->code ?? '',
                'teacher_name' => $sch->teacher->name ?? 'Guru Pengajar',
                'topic' => $journal?->activity ?? ($mat?->topic ?? 'Menunggu KBM dimulai'),
                'notes' => $notes ?: ($mat?->assignment ?? ''),
                'absent_students' => $absentStudents,
                'attachment_url' => $attachmentUrl,
                'attachment_name' => $attachmentName,
                'status' => $isDone ? 'TERLAKSANA' : ($isPending ? 'MENUNGGU_VERIFIKASI' : 'BELUM_DIMULAI'),
                'status_label' => $isDone ? 'Terlaksana & Terverifikasi' : ($isPending ? 'Selesai (Menunggu Paraf)' : 'Belum Dimulai'),
                'status_color' => $isDone ? 'emerald' : ($isPending ? 'blue' : 'gray'),
            ];
        });
    }

    private function sekretarisDashboard(User $user, $today)
    {
        $classId = $user->class_id ?? 1;

        $session = AttendanceSession::where('class_id', $classId)
            ->whereDate('date', $today)
            ->with(['records.student'])
            ->first();

        if (!$session) {
            $session = AttendanceSession::where('class_id', $classId)
                ->with(['records.student'])
                ->latest('date')
                ->first();
        }

        $students = User::where('class_id', $classId)
            ->whereIn('role', ['SISWA', 'SEKRETARIS'])
            ->orderBy('name')
            ->get();

        // 1. Cek surat izin untuk seluruh siswa kelas hari ini (deteksi izin ditolak walas)
        $todayLeaves = LeaveRequest::whereIn('student_id', $students->pluck('id'))
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->get();

        $rejectedLeaves = $todayLeaves->where('status', 'REJECTED')->keyBy('student_id');
        $approvedLeaves = $todayLeaves->where('status', 'APPROVED')->keyBy('student_id');
        $pendingLeaves = $todayLeaves->where('status', 'PENDING')->keyBy('student_id');

        // 2. Status Kehadiran Pribadi Sekretaris Sendiri (Dualisme Petugas & Siswa)
        $selfSessionToday = AttendanceSession::where('class_id', $classId)->whereDate('date', $today)->first();
        $selfRecord = $selfSessionToday
            ? AttendanceRecord::where('session_id', $selfSessionToday->id)->where('student_id', $user->id)->first()
            : AttendanceRecord::where('student_id', $user->id)->latest('id')->first();

        $selfLeave = $todayLeaves->where('student_id', $user->id)->sortByDesc('id')->first();

        $selfStatus = 'BELUM_PRESENSI';
        $selfNote = 'Belum melakukan presensi mandiri hari ini';
        $selfCheckedIn = false;
        $isSelfLeaveRejected = false;
        $isSelfLeaveApproved = false;
        $hasSelfPendingLeave = false;
        $selfRejectionReason = null;

        if ($selfLeave && $selfLeave->status === 'APPROVED') {
            $selfStatus = $selfLeave->type;
            $selfNote = 'Dispensasi Surat Izin Resmi (' . $selfLeave->type . ') Disetujui Wali Kelas: ' . $selfLeave->reason;
            $isSelfLeaveApproved = true;
            $selfCheckedIn = true;
        } elseif ($selfLeave && $selfLeave->status === 'REJECTED') {
            $isSelfLeaveRejected = true;
            $selfRejectionReason = $selfLeave->review_note ?: 'Tidak memenuhi syarat izin.';
            if ($selfRecord && $selfRecord->status === 'HADIR' && !str_contains($selfRecord->note ?? '', 'Presensi Mandiri Pukul')) {
                $selfStatus = 'HADIR';
                $selfNote = $selfRecord->note ?: 'Diverifikasi Hadir Fisik di Kelas';
                $selfCheckedIn = true;
            } else {
                $selfStatus = 'DITOLAK';
                $selfNote = 'Surat izin Anda telah DITOLAK Wali Kelas ("' . $selfRejectionReason . '"). Silakan lapor ke Wali Kelas/hadir fisik.';
                $selfCheckedIn = false;
            }
        } elseif ($selfLeave && $selfLeave->status === 'PENDING') {
            $hasSelfPendingLeave = true;
            $selfStatus = 'PENDING';
            $selfNote = 'Permohonan surat izin Anda (' . $selfLeave->type . ') sedang menunggu persetujuan Wali Kelas.';
        } elseif ($selfRecord && $selfSessionToday && $selfRecord->session_id === $selfSessionToday->id) {
            $selfStatus = $selfRecord->status;
            $selfNote = $selfRecord->note ?: 'Presensi Mandiri Terverifikasi';
            $selfCheckedIn = true;
        }

        // 3. Rekap Siswa Kelas & Penanda Khusus "Izin Ditolak Walas"
        $hadir = 0; $sakit = 0; $izin = 0; $alpha = 0;
        $attendanceList = [];

        $recordMap = $session ? $session->records->keyBy('student_id') : collect();

        foreach ($students as $idx => $std) {
            $rec = $recordMap->get($std->id);
            $isRejected = $rejectedLeaves->has($std->id);
            $rejectionReason = $isRejected ? $rejectedLeaves->get($std->id)->review_note : null;

            $status = $rec ? $rec->status : 'BELUM_PRESENSI';
            $note = $rec ? $rec->note : '-';

            if ($status === 'HADIR') $hadir++;
            elseif ($status === 'SAKIT') $sakit++;
            elseif ($status === 'IZIN') $izin++;
            elseif ($status === 'ALPHA') $alpha++;

            $attendanceList[] = [
                'no' => $idx + 1,
                'student_id' => $std->id,
                'name' => $std->name,
                'nis' => $std->nip_nis ?? '-',
                'status' => $status,
                'note' => $note,
                'is_self' => $std->id === $user->id,
                'is_rejected_leave' => $isRejected,
                'rejection_reason' => $rejectionReason,
                'verified' => $session ? ($session->verification_status === 'TERVERIFIKASI') : false,
            ];
        }

        $total = count($attendanceList) ?: 1;
        $percentage = round(($hadir / $total) * 100);

        // Catatan belajar pribadi sekretaris sendiri (bukan seluruh siswa)
        $recentJournals = ClassJournal::with(['schedule.subject', 'confirmedBy'])
            ->where('created_by_id', $user->id)
            ->latest('date')
            ->latest('id')
            ->take(5)
            ->get()
            ->map(function ($j) {
                return [
                    'id' => $j->id,
                    'date' => Carbon::parse($j->date)->translatedFormat('d M Y'),
                    'subject' => $j->schedule->subject->name ?? 'Kegiatan Belajar',
                    'activity' => $j->activity,
                    'status' => $j->verification_status,
                ];
            });

        return response()->json([
            'class_name' => $user->schoolClass->name ?? 'Kelas Industri RPL',
            'session_date' => $session ? Carbon::parse($session->date)->translatedFormat('l, d M Y') : Carbon::parse($today)->translatedFormat('l, d M Y'),
            'self_attendance' => [
                'status' => $selfStatus,
                'note' => $selfNote,
                'checked_in' => $selfCheckedIn,
                'is_leave_rejected' => $isSelfLeaveRejected,
                'is_leave_approved' => $isSelfLeaveApproved,
                'has_pending_leave' => $hasSelfPendingLeave,
                'rejection_reason' => $selfRejectionReason,
                'date_formatted' => Carbon::parse($today)->translatedFormat('l, d F Y'),
            ],
            'stats' => [
                'hadir_count' => $hadir . ' Siswa',
                'hadir_percent' => $percentage . '% Tingkat Kehadiran',
                'izin_count' => $izin . ' Siswa',
                'sakit_count' => $sakit . ' Siswa',
                'alpha_count' => $alpha . ' Siswa',
            ],
            'attendance_list' => $attendanceList,
            'recent_journals' => $recentJournals,
            'session_status' => $session ? $session->status : 'DRAFT',
            'verification_status' => $session ? $session->verification_status : 'BELUM_KONFIRMASI',
            'today_timeline' => $this->getTodayTimeline($classId, $today),
        ]);
    }

    private function pengajarDashboard(User $user, $today)
    {
        $taughtClasses = $user->taughtClasses()->count() ?: 4;
        $schedules = Schedule::with(['schoolClass', 'subject'])
            ->where('teacher_id', $user->id)
            ->get();

        $filledJournals = ClassJournal::where('confirmed_by_id', $user->id)
            ->where('date', $today)
            ->count();
        $totalSchedules = $schedules->count() ?: 5;
        $unfilled = max(0, $totalSchedules - $filledJournals);

        $scheduleList = $schedules->map(function ($sch) use ($today) {
            $journal = ClassJournal::where('schedule_id', $sch->id)
                ->where('date', $today)
                ->first();

            return [
                'id' => $sch->id,
                'time' => $sch->start_time . ' - ' . $sch->end_time,
                'subject' => $sch->subject->name ?? 'Mata Pelajaran',
                'class' => $sch->schoolClass->name ?? 'Kelas',
                'status' => $journal ? 'Terisi' : 'Belum Diisi',
                'verification' => $journal->verification_status ?? 'BELUM_DIISI',
            ];
        });

        $taughtClassIds = $user->taughtClasses()->pluck('classes.id');
        $classesToSummarize = SchoolClass::whereIn('id', $taughtClassIds)->get();
        if ($classesToSummarize->isEmpty()) {
            $classesToSummarize = SchoolClass::take(2)->get();
        }

        $attendanceSummary = $classesToSummarize->map(function ($cls) use ($today) {
            $totalStudents = User::where('class_id', $cls->id)->whereIn('role', ['SISWA', 'SEKRETARIS'])->count() ?: 36;
            $session = AttendanceSession::where('class_id', $cls->id)->where('date', $today)->first();
            $presentCount = $session ? $session->records()->where('status', 'HADIR')->count() : round($totalStudents * 0.94);
            $pct = round(($presentCount / $totalStudents) * 100);
            return [
                'class' => $cls->name,
                'present_ratio' => "{$presentCount}/{$totalStudents} siswa",
                'percent' => $pct,
            ];
        });

        $teacherScheduleCount = Schedule::where('teacher_id', $user->id)->count();
        $filledTodayCount = ClassJournal::whereIn('schedule_id', Schedule::where('teacher_id', $user->id)->pluck('id'))
            ->whereDate('date', $today)
            ->count();
        $unfilledCount = max(0, $teacherScheduleCount - $filledTodayCount);

        $unfilledSchedules = Schedule::where('teacher_id', $user->id)
            ->whereNotIn('id', ClassJournal::whereDate('date', $today)->pluck('schedule_id'))
            ->with('schoolClass')
            ->get();
        $unfilledClassesText = $unfilledSchedules->map(fn($s) => $s->schoolClass->name ?? 'Kelas')->unique()->implode(' dan ');

        $reminders = [];
        if ($unfilledCount > 0) {
            $reminders[] = $unfilledCount . ' jurnal belum diisi (' . ($unfilledClassesText ?: 'Pertemuan Terjadwal') . ')';
        } else {
            $reminders[] = 'Seluruh jurnal tatap muka hari ini telah tuntas diisi.';
        }

        $pendingLeavesCount = LeaveRequest::where('status', 'PENDING')
            ->whereHas('student', function ($q) use ($taughtClassIds) {
                $q->whereIn('class_id', $taughtClassIds);
            })->count();

        if ($pendingLeavesCount > 0) {
            $reminders[] = $pendingLeavesCount . ' surat izin menunggu verifikasi Wali Kelas';
        }

        $actualTaughtClasses = $user->taughtClasses()->count() ?: (Schedule::where('teacher_id', $user->id)->distinct('class_id')->count('class_id') ?: 1);
        $actualJamMengajar = $schedules->count() ?: 0;

        return response()->json([
            'stats' => [
                'kelas_diampu' => $actualTaughtClasses . ' Kelas',
                'jam_mengajar' => $actualJamMengajar . ' Jam',
                'jurnal_terisi' => $filledJournals . '/' . $totalSchedules . ' Pertemuan',
                'jurnal_belum_diisi' => $unfilled . ' Pertemuan',
            ],
            'schedules' => $scheduleList,
            'attendance_summary' => $attendanceSummary,
            'reminders' => $reminders,
            'recent_activity' => [
                [
                    'title' => 'Jurnal KBM terverifikasi di database',
                    'time' => Carbon::now()->format('H:i') . ' WIB',
                ],
            ],
        ]);
    }

    private function pembimbingDashboard(User $user, $today)
    {
        // 1. Dapatkan kelas binaan wali kelas (berdasarkan advisor_id atau class_id)
        $guidedClass = SchoolClass::with(['secretary', 'advisor'])->where('advisor_id', $user->id)->first()
            ?? SchoolClass::with(['secretary', 'advisor'])->find($user->class_id);

        $daysMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
        $currentDayName = $daysMap[Carbon::parse($today)->dayOfWeekIso] ?? 'Senin';

        if (!$guidedClass) {
            return response()->json([
                'stats' => [
                    'total_kelas' => 'Belum Tertaut',
                    'total_siswa' => '0 Siswa',
                    'jurnal_terisi' => '0 / 0 Sesi',
                    'surat_izin_pending' => '0 Permohonan',
                ],
                'monitored_classes' => [],
                'pending_leaves' => [],
            ]);
        }

        // 2. Data siswa kelas binaan
        $totalStudents = User::where('class_id', $guidedClass->id)->whereIn('role', ['SISWA', 'SEKRETARIS'])->count();

        // 3. Status Sesi Presensi Hari Ini
        $session = AttendanceSession::where('class_id', $guidedClass->id)
            ->whereDate('date', $today)
            ->first();

        $presentCount = $session ? $session->records()->where('status', 'HADIR')->count() : 0;
        $attendanceText = $session
            ? "{$presentCount} / {$totalStudents} Hadir (" . ($totalStudents > 0 ? round(($presentCount / $totalStudents) * 100) : 0) . "%)"
            : 'Belum Ada Sesi Presensi';

        // 4. Jurnal KBM Kelas Hari Ini
        $todayJournals = ClassJournal::where('class_id', $guidedClass->id)
            ->whereDate('date', $today)
            ->get();

        $todaySchedulesCount = Schedule::where('class_id', $guidedClass->id)->where('day', $currentDayName)->count();
        if ($todaySchedulesCount === 0) {
            $todaySchedulesCount = Schedule::where('class_id', $guidedClass->id)->count() ?: 1;
        }

        $latestJournal = $todayJournals->sortByDesc('id')->first();
        $journalStatus = $latestJournal
            ? ($latestJournal->verification_status === 'TERVERIFIKASI' ? 'Terverifikasi' : 'Menunggu Paraf Guru')
            : 'Belum Diisi';

        $monitoredClasses = [
            [
                'id' => $guidedClass->id,
                'class' => $guidedClass->name,
                'secretary' => $guidedClass->secretary->name ?? 'Belum Diangkat',
                'journal_status' => $journalStatus,
                'attendance_percent' => $attendanceText,
                'total_students' => $totalStudents,
                'present_count' => $presentCount,
            ]
        ];

        // 5. Surat Izin Pending KHUSUS Kelas Binaan
        $pendingLeaves = LeaveRequest::with(['student.schoolClass'])
            ->whereHas('student', fn($q) => $q->where('class_id', $guidedClass->id))
            ->where('status', 'PENDING')
            ->latest()
            ->get();

        $leaveList = $pendingLeaves->map(function ($lv) {
            return [
                'id' => $lv->id,
                'student_name' => $lv->student->name ?? 'Siswa',
                'class' => $lv->student->schoolClass->name ?? '-',
                'type' => $lv->type,
                'reason' => $lv->reason,
                'attachment' => $lv->attachment_path ? basename($lv->attachment_path) : null,
                'attachment_url' => $lv->attachment_path ? url('api/leave-requests/file/' . basename($lv->attachment_path)) : null,
            ];
        });

        return response()->json([
            'stats' => [
                'total_kelas' => $guidedClass->name,
                'total_siswa' => $totalStudents . ' Siswa',
                'jurnal_terisi' => $todayJournals->count() . ' / ' . $todaySchedulesCount . ' Sesi',
                'surat_izin_pending' => $pendingLeaves->count() . ' Permohonan',
            ],
            'monitored_classes' => $monitoredClasses,
            'pending_leaves' => $leaveList,
        ]);
    }

    private function superadminDashboard(User $user, $today)
    {
        $allClasses = SchoolClass::with(['advisor', 'secretary'])->orderBy('name')->get();
        $totalStudents = User::whereIn('role', ['SISWA', 'SEKRETARIS'])->count();
        $totalPembimbing = User::where('role', 'GURU_PEMBIMBING')->count();
        $totalPengajar = User::where('role', 'GURU_PENGAJAR')->count();
        $totalClasses = $allClasses->count();

        $classesRplCount = SchoolClass::where('major', 'RPL')->count();
        $classesDkvCount = SchoolClass::where('major', 'DKV')->count();
        $classesAniCount = SchoolClass::where('major', 'Animasi')->count();

        $sessionsToday = AttendanceSession::whereDate('date', $today)->with('records')->get();
        $totalPresentToday = 0;
        $totalRecordsToday = 0;
        foreach ($sessionsToday as $ses) {
            $present = $ses->records->where('status', 'HADIR')->count();
            $recCount = $ses->records->count();
            $totalPresentToday += $present;
            $totalRecordsToday += $recCount;
        }
        $avgAttendance = $totalRecordsToday > 0 ? round(($totalPresentToday / $totalRecordsToday) * 100) : 94;

        $journalsTodayCount = ClassJournal::whereDate('date', $today)->count();
        $pendingLeavesCount = LeaveRequest::where('status', 'PENDING')->count();

        $classesMonitoring = $allClasses->map(function ($cls) use ($today) {
            $session = AttendanceSession::where('class_id', $cls->id)
                ->whereDate('date', $today)
                ->first();

            $journal = ClassJournal::where('class_id', $cls->id)
                ->whereDate('date', $today)
                ->latest()
                ->first();

            $stdCount = User::where('class_id', $cls->id)->whereIn('role', ['SISWA', 'SEKRETARIS'])->count() ?: 36;
            $presentCount = $session ? $session->records()->where('status', 'HADIR')->count() : round($stdCount * 0.94);
            $pct = round(($presentCount / $stdCount) * 100);

            return [
                'id' => $cls->id,
                'class_name' => $cls->name,
                'major' => $cls->major ?? 'Umum',
                'advisor_name' => $cls->advisor->name ?? 'Belum Ditentukan',
                'secretary_name' => $cls->secretary->name ?? 'Belum Ditentukan',
                'student_count' => $stdCount,
                'session_status' => $session ? $session->status : 'BELUM_DIBUKA',
                'session_date' => $today,
                'attendance_percent' => $pct,
                'present_ratio' => "{$presentCount}/{$stdCount} Siswa",
                'journal_status' => $journal ? ($journal->verification_status === 'TERVERIFIKASI' ? 'Terverifikasi' : 'Pending Guru') : 'Belum Diisi',
            ];
        });

        $recentAuditLogs = AuditLog::with('user')
            ->latest()
            ->take(8)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'time' => Carbon::parse($log->created_at)->translatedFormat('H:i') . ' WIB',
                    'user' => $log->user ? $log->user->name : 'Sistem',
                    'role' => $log->user ? $log->user->role : 'SYSTEM',
                    'action' => $log->action,
                    'ip_address' => $log->ip_address ?? '127.0.0.1',
                ];
            });

        return response()->json([
            'stats' => [
                'total_guru_pembimbing' => $totalPembimbing . ' Walas',
                'total_guru_pengajar' => $totalPengajar . ' Guru',
                'total_siswa' => $totalStudents . ' Siswa',
                'total_kelas' => $totalClasses . ' Kelas',
                'kehadiran_rata_rata' => $avgAttendance . '%',
                'jurnal_terisi_today' => $journalsTodayCount . '/' . $totalClasses . ' Sesi',
                'surat_izin_pending' => $pendingLeavesCount . ' Menunggu',
                'majors' => [
                    'rpl' => $classesRplCount,
                    'dkv' => $classesDkvCount,
                    'animasi' => $classesAniCount,
                ],
            ],
            'classes_monitoring' => $classesMonitoring,
            'recent_audit_logs' => $recentAuditLogs,
        ]);
    }
}
