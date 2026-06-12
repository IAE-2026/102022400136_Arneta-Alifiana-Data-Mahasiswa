<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Models\SsoUser;
use App\Services\SoapAuditService;
use App\Services\RabbitMQService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Info(
    title: "Service A - Mahasiswa API",
    version: "1.0.0",
    description: "API untuk manajemen data mahasiswa dalam ekosistem Education System"
)]
#[OA\SecurityScheme(
    securityScheme: "ApiKeyAuth",
    type: "apiKey",
    in: "header",
    name: "X-API-KEY"
)]
class MahasiswaController extends Controller
{
    protected $soapService;
    protected $rabbitMQService;

    public function __construct(SoapAuditService $soapService, RabbitMQService $rabbitMQService)
    {
        $this->soapService     = $soapService;
        $this->rabbitMQService = $rabbitMQService;
    }

    #[OA\Get(
        path: "/api/v1/mahasiswa",
        summary: "Lihat seluruh daftar mahasiswa",
        security: [["ApiKeyAuth" => []]],
        tags: ["Mahasiswa"],
        responses: [
            new OA\Response(response: 200, description: "Berhasil mengambil daftar mahasiswa")
        ]
    )]
    public function index()
    {
        $mahasiswa = Mahasiswa::all();

        return response()->json([
            'success' => true,
            'message' => 'Daftar mahasiswa berhasil diambil.',
            'data'    => $mahasiswa,
        ], 200);
    }

    #[OA\Get(
        path: "/api/v1/mahasiswa/{nim}",
        summary: "Lihat detail mahasiswa berdasarkan NIM",
        security: [["ApiKeyAuth" => []]],
        tags: ["Mahasiswa"],
        parameters: [
            new OA\Parameter(name: "nim", in: "path", required: true, schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Data mahasiswa ditemukan"),
            new OA\Response(response: 404, description: "Mahasiswa tidak ditemukan")
        ]
    )]
    public function show(string $nim)
    {
        $mahasiswa = Mahasiswa::where('nim', $nim)->first();

        if (!$mahasiswa) {
            return response()->json([
                'success' => false,
                'message' => "Mahasiswa dengan NIM {$nim} tidak ditemukan.",
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data mahasiswa berhasil diambil.',
            'data'    => $mahasiswa,
        ], 200);
    }

    #[OA\Post(
        path: "/api/v1/mahasiswa",
        summary: "Catat mahasiswa baru",
        security: [["ApiKeyAuth" => []]],
        tags: ["Mahasiswa"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["nim", "nama", "email", "prodi", "angkatan"],
                properties: [
                    new OA\Property(property: "nim", type: "string", example: "1301210001"),
                    new OA\Property(property: "nama", type: "string", example: "Budi Santoso"),
                    new OA\Property(property: "email", type: "string", example: "budi@student.tel.ac.id"),
                    new OA\Property(property: "prodi", type: "string", example: "S1 Sistem Informasi"),
                    new OA\Property(property: "angkatan", type: "integer", example: 2021),
                    new OA\Property(property: "status", type: "string", example: "aktif"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Mahasiswa berhasil dicatat"),
            new OA\Response(response: 422, description: "Validasi gagal")
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nim'      => 'required|string|max:20|unique:mahasiswas,nim',
            'nama'     => 'required|string|max:100',
            'email'    => 'required|email|unique:mahasiswas,email',
            'prodi'    => 'required|string|max:100',
            'angkatan' => 'required|integer|digits:4',
            'status'   => ['nullable', Rule::in(['aktif', 'cuti', 'lulus', 'do'])],
        ]);

        // Simpan data mahasiswa
        $mahasiswa = Mahasiswa::create($validated);

        // Ambil JWT Token (utamakan M2M token dari API Key, fallback ke SSO User yang login)
        $token = $this->getM2MToken();
        if (!$token) {
            $ssoUser = SsoUser::latest()->first();
            if ($ssoUser) {
                $token = $ssoUser->jwt_token;
            }
        }

        $receiptNumber = null;
        $rabbitStatus  = null;

        if ($token) {
            // Kirim Audit SOAP
            $auditResult = $this->soapService->sendAudit(
                'MahasiswaBaru',
                [
                    'nim'      => $mahasiswa->nim,
                    'nama'     => $mahasiswa->nama,
                    'prodi'    => $mahasiswa->prodi,
                    'angkatan' => $mahasiswa->angkatan,
                    'status'   => $mahasiswa->status,
                    'waktu'    => now()->toISOString(),
                ],
                $token
            );
            $receiptNumber = $auditResult['receipt_number'];

            // Publish Event RabbitMQ
            $rabbitResult = $this->rabbitMQService->publish(
                'mahasiswa.created',
                [
                    'nim'      => $mahasiswa->nim,
                    'nama'     => $mahasiswa->nama,
                    'prodi'    => $mahasiswa->prodi,
                    'angkatan' => $mahasiswa->angkatan,
                    'status'   => $mahasiswa->status,
                ],
                $token
            );
            $rabbitStatus = $rabbitResult['success'] ? 'terkirim' : 'gagal';
        }

        return response()->json([
            'success' => true,
            'message' => 'Mahasiswa berhasil dicatat.',
            'data'    => [
                'mahasiswa'      => $mahasiswa,
                'receipt_number' => $receiptNumber,
                'rabbit_status'  =>  $rabbitStatus,
            ],
        ], 201);
    }

    /**
     * Ambil M2M token secara dinamis menggunakan API Key
     */
    private function getM2MToken(): ?string
    {
        $ssoUrl = 'https://iae-sso.virtualfri.id';
        $apiKey = config('app.api_key');

        if (!$apiKey) {
            return null;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::post("{$ssoUrl}/api/v1/auth/token", [
                'api_key' => $apiKey,
            ]);

            if ($response->successful()) {
                return $response->json()['token'] ?? null;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to retrieve M2M token: " . $e->getMessage());
        }

        return null;
    }
}