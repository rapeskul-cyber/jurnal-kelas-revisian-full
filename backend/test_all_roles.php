<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

echo "========================================================\n";
echo "   COMPREHENSIVE MULTI-ROLE BACKEND PERMISSION TEST     \n";
echo "========================================================\n\n";

$testUsers = [
    'SISWA' => 'ahmad.fadillah@gmail.com',
    'SEKRETARIS' => 'raffi@sekolah.sch.id',
    'GURU_PENGAJAR' => 'budi.santoso@gmail.com',
    'GURU_PEMBIMBING' => 'hendra.gunawan@gmail.com',
    'ORANG_TUA' => User::where('role', 'ORANG_TUA')->value('email') ?? 'ortu.jimmy@sekolah.sch.id',
    'SUPERADMIN' => 'superadmin@sekolah.sch.id',
];

$passCount = 0;
$failCount = 0;

function assertCondition($testName, $condition, &$passCount, &$failCount, $details = '') {
    if ($condition) {
        echo "  [PASS] {$testName}\n";
        $passCount++;
    } else {
        echo "  [FAIL] {$testName}" . ($details ? " - {$details}" : "") . "\n";
        $failCount++;
    }
}

foreach ($testUsers as $role => $email) {
    echo "--------------------------------------------------------\n";
    echo "TESTING ROLE: {$role} ({$email})\n";
    echo "--------------------------------------------------------\n";

    $user = User::where('email', $email)->first();
    if (!$user) {
        echo "  [ERROR] User {$email} not found in database!\n";
        $failCount++;
        continue;
    }

    assertCondition("User exists with correct role", $user->role === $role, $passCount, $failCount, "Found role: {$user->role}");

    // Authenticate user for the test context
    auth('sanctum')->setUser($user);
    $token = $user->createToken('test_token')->plainTextToken;

    // Test 1: Dashboard API
    try {
        $dashController = app(\App\Http\Controllers\Api\DashboardController::class);
        $request = Request::create('/api/dashboard', 'GET');
        $request->setUserResolver(fn() => $user);
        $res = $dashController->index($request);
        $status = $res->getStatusCode();
        $data = $res->getData(true);
        assertCondition("Dashboard API returns 200 OK", $status === 200, $passCount, $failCount, "Status: {$status}");
        
        if ($role === 'SISWA') {
            assertCondition("Siswa dashboard has stats and timeline", isset($data['stats']) && isset($data['today_timeline']), $passCount, $failCount);
        } elseif ($role === 'SEKRETARIS') {
            assertCondition("Sekretaris dashboard has stats and timeline", isset($data['stats']) && isset($data['today_timeline']), $passCount, $failCount);
        } elseif ($role === 'GURU_PENGAJAR') {
            assertCondition("Guru Pengajar dashboard has stats and teaching schedule", isset($data['stats']) && isset($data['schedules']), $passCount, $failCount);
        } elseif ($role === 'GURU_PEMBIMBING') {
            assertCondition("Wali Kelas dashboard has class stats and pending leaves", isset($data['stats']) && isset($data['pending_leaves']), $passCount, $failCount);
        } elseif ($role === 'ORANG_TUA') {
            assertCondition("Orang Tua dashboard has child info and attendance stats", isset($data['child']) && isset($data['stats']), $passCount, $failCount);
        } elseif ($role === 'SUPERADMIN') {
            assertCondition("Superadmin dashboard has total users and system stats", isset($data['stats']['total_siswa']) && isset($data['classes_monitoring']), $passCount, $failCount);
        }
    } catch (\Throwable $e) {
        assertCondition("Dashboard API executed without exception", false, $passCount, $failCount, $e->getMessage());
    }

    // Test 2: Journal Isolation
    try {
        $journalController = app(\App\Http\Controllers\Api\JournalController::class);
        $request = Request::create('/api/journals/class', 'GET', ['type' => 'personal']);
        $request->setUserResolver(fn() => $user);
        $res = $journalController->getClassJournals($request);
        $journals = $res->getData(true)['data'] ?? [];
        
        if ($role === 'SISWA') {
            $allMine = true;
            foreach ($journals as $j) {
                if (isset($j['created_by_id']) && $j['created_by_id'] != $user->id) {
                    $allMine = false;
                    break;
                }
            }
            assertCondition("Siswa only sees own personal journals", $allMine, $passCount, $failCount);
        } elseif ($role === 'ORANG_TUA') {
            assertCondition("Orang Tua can view child journals without exception", $res->getStatusCode() === 200, $passCount, $failCount);
        }
    } catch (\Throwable $e) {
        assertCondition("Journal API executed without exception", false, $passCount, $failCount, $e->getMessage());
    }

    // Test 3: User Management Access Control (RBAC)
    try {
        $userController = app(\App\Http\Controllers\Api\UserController::class);
        $request = Request::create('/api/users', 'GET');
        $request->setUserResolver(fn() => $user);
        $res = $userController->index($request);
        $status = $res->getStatusCode();
        
        if ($role === 'SUPERADMIN') {
            assertCondition("Superadmin can access user list", $status === 200, $passCount, $failCount);
        } elseif ($role === 'GURU_PEMBIMBING') {
            assertCondition("Wali Kelas can access their class student list", $status === 200, $passCount, $failCount);
            $usersList = $res->getData(true)['data'] ?? [];
            $onlyClass = true;
            foreach ($usersList as $u) {
                if (!in_array($u['role'], ['SISWA', 'SEKRETARIS']) && $u['role'] !== 'ORANG_TUA') {
                    $onlyClass = false;
                    break;
                }
            }
            assertCondition("Wali Kelas cannot see other teachers or superadmin", $onlyClass, $passCount, $failCount);
        } else {
            // SISWA, SEKRETARIS, GURU_PENGAJAR, ORANG_TUA must get 403 Forbidden
            assertCondition("Role {$role} is FORBIDDEN from accessing user management", $status === 403, $passCount, $failCount, "Got status: {$status}");
        }
    } catch (\Throwable $e) {
        assertCondition("User Management API handled correctly", false, $passCount, $failCount, $e->getMessage());
    }

    // Test 4: Master Data Read Access Control
    try {
        $masterController = app(\App\Http\Controllers\Api\MasterDataController::class);
        $request = Request::create('/api/master/classes', 'GET');
        $request->setUserResolver(fn() => $user);
        $res = $masterController->getClasses($request);
        $status = $res->getStatusCode();

        if (in_array($role, ['SUPERADMIN', 'GURU_PEMBIMBING', 'GURU_PENGAJAR'])) {
            assertCondition("Staff role {$role} can read master classes for reporting", $status === 200, $passCount, $failCount);
        } else {
            assertCondition("Student/Parent role {$role} is FORBIDDEN from reading master classes", false, $passCount, $failCount, "Got status: {$status}");
        }
    } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
        $status = $e->getResponse()->getStatusCode();
        if (in_array($role, ['SISWA', 'SEKRETARIS', 'ORANG_TUA']) && $status === 403) {
            assertCondition("Student/Parent role {$role} is FORBIDDEN from reading master classes", true, $passCount, $failCount);
        } else {
            assertCondition("Master classes read access handled properly", false, $passCount, $failCount, "Got status: {$status}");
        }
    } catch (\Throwable $e) {
        assertCondition("Master classes read access handled properly", false, $passCount, $failCount, $e->getMessage());
    }

    // Test 5: Master Data Mutation Access Control (Only Superadmin can modify/create)
    try {
        $masterController = app(\App\Http\Controllers\Api\MasterDataController::class);
        $request = Request::create('/api/master/classes', 'POST', ['name' => 'Test Dummy Class ' . uniqid()]);
        $request->setUserResolver(fn() => $user);
        $res = $masterController->storeClass($request);
        $status = $res->getStatusCode();

        if ($role === 'SUPERADMIN') {
            assertCondition("Superadmin has mutation access to master classes", in_array($status, [200, 422]), $passCount, $failCount);
        } else {
            assertCondition("Non-superadmin role {$role} is FORBIDDEN from creating/mutating classes", false, $passCount, $failCount, "Got status: {$status}");
        }
    } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
        $status = $e->getResponse()->getStatusCode();
        if ($role !== 'SUPERADMIN' && $status === 403) {
            assertCondition("Non-superadmin role {$role} is FORBIDDEN from creating/mutating classes", true, $passCount, $failCount);
        } else {
            assertCondition("Master mutation access handled properly", false, $passCount, $failCount, $e->getMessage());
        }
    } catch (\Throwable $e) {
        assertCondition("Master mutation access handled properly", false, $passCount, $failCount, $e->getMessage());
    }

    // Clean up test token
    $user->tokens()->delete();
    SchoolClass::where('name', 'like', 'Test Dummy Class%')->delete();
}

echo "\n========================================================\n";
echo "TEST RESULTS SUMMARY: {$passCount} PASSED, {$failCount} FAILED\n";
echo "========================================================\n";

exit($failCount > 0 ? 1 : 0);
