<?php

namespace Tests\Feature;

use App\Models\Mahasiswa;
use App\Models\SsoUser;
use App\Services\RabbitMQService;
use App\Services\SoapAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IntegrationContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock external HTTP integrations dynamically based on request data
        Http::fake([
            'https://iae-sso.virtualfri.id/api/v1/auth/token' => function (\Illuminate\Http\Client\Request $request) {
                // Handle M2M Token requests using api_key
                if (isset($request['api_key'])) {
                    return Http::response([
                        'token' => 'mocked-jwt-token',
                        'success' => true
                    ], 200);
                }

                // Handle SSO User Login requests
                $email = $request['email'] ?? '';
                if (str_contains($email, 'wrong') || $email === '') {
                    return Http::response([
                        'success' => false,
                        'message' => 'Login SSO gagal. Periksa email dan password.'
                    ], 401);
                }
                return Http::response([
                    'token' => 'mocked-jwt-token',
                    'profile' => [
                        'name' => 'Warga Test',
                        'email' => $email ?: 'warga@ktp.iae.id',
                        'nim' => '102022400136'
                    ],
                    'expires_in' => 3600,
                    'success' => true
                ], 200);
            },
            'https://iae-sso.virtualfri.id/*' => Http::response([
                'token' => 'mocked-jwt-token',
                'profile' => [
                    'name' => 'Warga Test',
                    'email' => 'warga@ktp.iae.id',
                    'nim' => '102022400136'
                ],
                'expires_in' => 3600,
                'receipt_number' => 'IAE-LOG-MOCK-12345',
                'success' => true
            ], 200)
        ]);

        // Mock SOAP and RabbitMQ service class responses to simplify post test verification
        $this->mock(SoapAuditService::class, function ($mock) {
            $mock->shouldReceive('sendAudit')
                ->andReturn([
                    'success' => true,
                    'receipt_number' => 'IAE-LOG-MOCK-12345'
                ]);
        });

        $this->mock(RabbitMQService::class, function ($mock) {
            $mock->shouldReceive('publish')
                ->andReturn([
                    'success' => true,
                    'message' => 'Event dipublikasikan'
                ]);
        });
    }

    /**
     * Test access is unauthorized without X-IAE-KEY.
     */
    public function test_access_requires_api_key(): void
    {
        $response = $this->getJson('/api/v1/mahasiswa');

        $response->assertStatus(401);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Unauthorized. Invalid or missing API Key.',
            'errors' => null
        ]);
    }

    /**
     * Test access is unauthorized with invalid X-IAE-KEY.
     */
    public function test_access_denied_with_invalid_api_key(): void
    {
        $response = $this->getJson('/api/v1/mahasiswa', [
            'X-IAE-KEY' => 'WRONG-KEY'
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Unauthorized. Invalid or missing API Key.',
            'errors' => null
        ]);
    }

    /**
     * Test retrieval of collection with valid API Key.
     */
    public function test_get_all_mahasiswa_with_correct_wrapper(): void
    {
        // Seed database
        Mahasiswa::create([
            'nim' => '102022400136',
            'nama' => 'Arneta Alifiana',
            'email' => 'arneta@student.telkomuniversity.ac.id',
            'prodi' => 'S1 Sistem Informasi',
            'angkatan' => 2024,
            'status' => 'aktif'
        ]);

        $response = $this->getJson('/api/v1/mahasiswa', [
            'X-IAE-KEY' => '102022400136'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                '*' => [
                    'id', 'nim', 'nama', 'email', 'prodi', 'angkatan', 'status', 'created_at', 'updated_at'
                ]
            ],
            'meta' => [
                'service_name', 'api_version'
            ]
        ]);
        
        $response->assertJson([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'meta' => [
                'service_name' => 'Data-Mahasiswa-Service',
                'api_version' => 'v1'
            ]
        ]);
    }

    /**
     * Test retrieval of specific resource.
     */
    public function test_get_specific_mahasiswa_by_nim(): void
    {
        $mhs = Mahasiswa::create([
            'nim' => '102022400136',
            'nama' => 'Arneta Alifiana',
            'email' => 'arneta@student.telkomuniversity.ac.id',
            'prodi' => 'S1 Sistem Informasi',
            'angkatan' => 2024,
            'status' => 'aktif'
        ]);

        $response = $this->getJson("/api/v1/mahasiswa/{$mhs->nim}", [
            'X-IAE-KEY' => '102022400136'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => [
                'nim' => '102022400136',
                'nama' => 'Arneta Alifiana'
            ]
        ]);
    }

    /**
     * Test error wrapper for not found resource.
     */
    public function test_get_not_found_returns_correct_error_wrapper(): void
    {
        $response = $this->getJson('/api/v1/mahasiswa/999999', [
            'X-IAE-KEY' => '102022400136'
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Mahasiswa dengan ID atau NIM 999999 tidak ditemukan.',
            'errors' => null
        ]);
    }

    /**
     * Test successful creation of resource.
     */
    public function test_post_create_mahasiswa_successful(): void
    {
        $payload = [
            'nim' => '102022400137',
            'nama' => 'Budi Santoso',
            'email' => 'budi@student.telkomuniversity.ac.id',
            'prodi' => 'S1 Sistem Informasi',
            'angkatan' => 2024,
            'status' => 'aktif'
        ];

        $response = $this->postJson('/api/v1/mahasiswa', $payload, [
            'X-IAE-KEY' => '102022400136'
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Mahasiswa berhasil dicatat.',
            'data' => [
                'mahasiswa' => [
                    'nim' => '102022400137',
                    'nama' => 'Budi Santoso'
                ],
                'receipt_number' => 'IAE-LOG-MOCK-12345',
                'rabbit_status' => 'terkirim'
            ]
        ]);

        $this->assertDatabaseHas('mahasiswas', [
            'nim' => '102022400137'
        ]);
    }

    /**
     * Test validation error wrapper.
     */
    public function test_post_create_mahasiswa_validation_fails(): void
    {
        $payload = [
            'nim' => '', // Nim is required
            'nama' => 'Budi Santoso',
            'email' => 'not-an-email', // Invalid email format
            'prodi' => 'S1 Sistem Informasi',
            'angkatan' => 24, // Angkatan must be 4 digits
            'status' => 'aktif'
        ];

        $response = $this->postJson('/api/v1/mahasiswa', $payload, [
            'X-IAE-KEY' => '102022400136'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nim', 'email', 'angkatan']);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Validation failed.'
        ]);
    }

    /**
     * Test successful SSO login.
     */
    public function test_sso_login_successful(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'warga@ktp.iae.id',
            'password' => 'secret-password'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Login SSO berhasil.',
            'data' => [
                'token' => 'mocked-jwt-token'
            ]
        ]);
    }

    /**
     * Test failed SSO login.
     */
    public function test_sso_login_failed(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'wrong@ktp.iae.id',
            'password' => 'wrong-password'
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Login SSO gagal. Periksa email dan password.'
        ]);
    }

    /**
     * Test successful SSO profile retrieval.
     */
    public function test_sso_profile_successful(): void
    {
        // First, create the user in local database
        SsoUser::create([
            'name' => 'Warga Test',
            'email' => 'warga@ktp.iae.id',
            'role' => 'mahasiswa',
            'jwt_token' => 'mocked-jwt-token'
        ]);

        $response = $this->getJson('/api/auth/profile', [
            'Authorization' => 'Bearer mocked-jwt-token'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Profil pengguna berhasil diambil.',
            'data' => [
                'email' => 'warga@ktp.iae.id'
            ]
        ]);
    }

    /**
     * Test failed SSO profile retrieval (invalid or missing token).
     */
    public function test_sso_profile_unauthorized(): void
    {
        $response = $this->getJson('/api/auth/profile', [
            'Authorization' => 'Bearer invalid-token'
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Token tidak valid.'
        ]);
    }
}
