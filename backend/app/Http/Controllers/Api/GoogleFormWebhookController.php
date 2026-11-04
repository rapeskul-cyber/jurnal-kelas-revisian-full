<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class GoogleFormWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $secret = $request->header('X-Webhook-Secret');
        $expectedSecret = env('GOOGLE_FORM_WEBHOOK_SECRET', 'rapiuw_secret_2026');

        if ($secret !== $expectedSecret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'nip_nis'    => 'required|string|max:50',
            'email'      => 'required|email|max:255',
            'phone'      => 'nullable|string|max:25',
            'class_name' => 'required|string|max:100',
        ]);

        $class = SchoolClass::firstOrCreate(
            ['name' => trim($validated['class_name'])],
            ['grade' => 'X', 'major' => 'Umum']
        );

        $user = User::where('nip_nis', $validated['nip_nis'])
            ->orWhere('email', $validated['email'])
            ->first();

        if ($user) {
            $user->update([
                'name'      => $validated['name'],
                'phone'     => $validated['phone'] ?? $user->phone,
                'class_id'  => $class->id,
                'is_active' => true,
            ]);
            $status = 'updated';
        } else {
            $defaultPassword = 'Siswa@' . trim($validated['nip_nis']);
            $user = User::create([
                'name'      => $validated['name'],
                'nip_nis'   => $validated['nip_nis'],
                'email'     => strtolower(trim($validated['email'])),
                'phone'     => $validated['phone'],
                'password'  => Hash::make($defaultPassword),
                'role'      => 'student',
                'class_id'  => $class->id,
                'is_active' => true,
            ]);
            $status = 'created';
        }

        Log::info("Google Form Webhook: Student {$user->nip_nis} {$status}");

        return response()->json([
            'success' => true,
            'status'  => $status,
            'user_id' => $user->id,
            'name'    => $user->name,
            'nip_nis' => $user->nip_nis,
            'class'   => $class->name
        ]);
    }
}
