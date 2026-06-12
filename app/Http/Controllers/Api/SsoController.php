<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SsoUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SsoController extends Controller
{
    protected $ssoUrl = 'https://iae-sso.virtualfri.id';

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // Kirim request login ke SSO Cloud Dosen
        $response = Http::post("{$this->ssoUrl}/api/v1/auth/token", [
            'email'    => $validated['email'],
            'password' => $validated['password'],
        ]);

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Login SSO gagal. Periksa email dan password.',
                'data'    => null,
            ], 401);
        }

        $data    = $response->json();
        $profile = $data['profile'];
        $token   = $data['token'];

        // Tentukan role lokal
        $role = 'mahasiswa';
        if (str_contains($profile['email'], 'dosen')) {
            $role = 'dosen';
        } elseif (str_contains($profile['email'], 'admin')) {
            $role = 'admin';
        }

        // Simpan atau update user ke database lokal
        $user = SsoUser::updateOrCreate(
            ['email' => $profile['email']],
            [
                'name'      => $profile['name'],
                'nim'       => $profile['nim'] ?? null,
                'role'      => $role,
                'jwt_token' => $token,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Login SSO berhasil.',
            'data'    => [
                'user'       => $user,
                'token'      => $token,
                'expires_in' => $data['expires_in'],
            ],
        ], 200);
    }

    public function profile(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak ditemukan.',
                'data'    => null,
            ], 401);
        }

        $user = SsoUser::where('jwt_token', $token)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid.',
                'data'    => null,
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profil pengguna berhasil diambil.',
            'data'    => $user,
        ], 200);
    }
}