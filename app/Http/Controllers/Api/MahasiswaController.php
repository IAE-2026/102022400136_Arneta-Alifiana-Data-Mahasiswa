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
    name: "X-IAE-KEY"
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
            new OA\Response(
                response: 200,
                description: "Berhasil mengambil daftar mahasiswa",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "message", type: "string", example: "Data retrieved successfully"),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "nim", type: "string", example: "1301210001"),
                                    new OA\Property(property: "nama", type: "string", example: "Budi Santoso"),
                                    new OA\Property(property: "email", type: "string", example: "budi@student.tel.ac.id"),
                                    new OA\Property(property: "prodi", type: "string", example: "S1 Sistem Informasi"),
                                    new OA\Property(property: "angkatan", type: "integer", example: 2021),
                                    new OA\Property(property: "status", type: "string", example: "aktif")
                                ]
                            )
                        ),
                        new OA\Property(
                            property: "meta",
                            type: "object",
                            properties: [
                                new OA\Property(property: "service_name", type: "string", example: "Data-Mahasiswa-Service"),
                                new OA\Property(property: "api_version", type: "string", example: "v1")
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function index()
    {
        $mahasiswa = Mahasiswa::all();

        return response()->json([
            'status'  => 'success',
            'message' => 'Data retrieved successfully',
            'data'    => $mahasiswa,
            'meta'    => [
                'service_name' => 'Data-Mahasiswa-Service',
                'api_version'  => 'v1',
            ],
        ], 200);
    }

    #[OA\Get(
        path: "/api/v1/mahasiswa/{id}",
        summary: "Lihat detail mahasiswa berdasarkan ID or NIM",
        security: [["ApiKeyAuth" => []]],
        tags: ["Mahasiswa"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Data mahasiswa ditemukan",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "message", type: "string", example: "Data retrieved successfully"),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "nim", type: "string", example: "1301210001"),
                                new OA\Property(property: "nama", type: "string", example: "Budi Santoso"),
                                new OA\Property(property: "email", type: "string", example: "budi@student.tel.ac.id"),
                                new OA\Property(property: "prodi", type: "string", example: "S1 Sistem Informasi"),
                                new OA\Property(property: "angkatan", type: "integer", example: 2021),
                                new OA\Property(property: "status", type: "string", example: "aktif"),
                                new OA\Property(property: "created_at", type: "string", example: "2026-06-25T03:47:40.000000Z"),
                                new OA\Property(property: "updated_at", type: "string", example: "2026-06-25T03:47:40.000000Z")
                            ]
                        ),
                        new OA\Property(
                            property: "meta",
                            type: "object",
                            properties: [
                                new OA\Property(property: "service_name", type: "string", example: "Data-Mahasiswa-Service"),
                                new OA\Property(property: "api_version", type: "string", example: "v1")
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Mahasiswa tidak ditemukan",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "error"),
                        new OA\Property(property: "message", type: "string", example: "Mahasiswa dengan ID atau NIM 999 tidak ditemukan."),
                        new OA\Property(property: "errors", type: "object", nullable: true, example: null)
                    ]
                )
            )
        ]
    )]
    public function show(string $id)
    {
        // Support searching by both database ID and student NIM
        $mahasiswa = Mahasiswa::where('nim', $id)->orWhere('id', $id)->first();

        if (!$mahasiswa) {
            return response()->json([
                'status'  => 'error',
                'message' => "Mahasiswa dengan ID atau NIM {$id} tidak ditemukan.",
                'errors'  => null,
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Data retrieved successfully',
            'data'    => $mahasiswa,
            'meta'    => [
                'service_name' => 'Data-Mahasiswa-Service',
                'api_version'  => 'v1',
            ],
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
            new OA\Response(
                response: 201,
                description: "Mahasiswa berhasil dicatat",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "message", type: "string", example: "Mahasiswa berhasil dicatat."),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(
                                    property: "mahasiswa",
                                    type: "object",
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(property: "nim", type: "string", example: "1301210001"),
                                        new OA\Property(property: "nama", type: "string", example: "Budi Santoso"),
                                        new OA\Property(property: "email", type: "string", example: "budi@student.tel.ac.id"),
                                        new OA\Property(property: "prodi", type: "string", example: "S1 Sistem Informasi"),
                                        new OA\Property(property: "angkatan", type: "integer", example: 2021),
                                        new OA\Property(property: "status", type: "string", example: "aktif"),
                                        new OA\Property(property: "created_at", type: "string", example: "2026-06-25T03:47:40.000000Z"),
                                        new OA\Property(property: "updated_at", type: "string", example: "2026-06-25T03:47:40.000000Z")
                                    ]
                                ),
                                new OA\Property(property: "receipt_number", type: "string", example: "IAE-LOG-12345", nullable: true),
                                new OA\Property(property: "rabbit_status", type: "string", example: "terkirim", nullable: true)
                            ]
                        ),
                        new OA\Property(
                            property: "meta",
                            type: "object",
                            properties: [
                                new OA\Property(property: "service_name", type: "string", example: "Data-Mahasiswa-Service"),
                                new OA\Property(property: "api_version", type: "string", example: "v1")
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validasi gagal",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "error"),
                        new OA\Property(property: "message", type: "string", example: "Validation failed."),
                        new OA\Property(
                            property: "errors",
                            type: "object",
                            properties: [
                                new OA\Property(
                                    property: "nim",
                                    type: "array",
                                    items: new OA\Items(type: "string", example: "The nim has already been taken.")
                                )
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function store(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'nim'      => 'required|string|max:20|unique:mahasiswas,nim',
            'nama'     => 'required|string|max:100',
            'email'    => 'required|email|unique:mahasiswas,email',
            'prodi'    => 'required|string|max:100',
            'angkatan' => 'required|integer|digits:4',
            'status'   => ['nullable', Rule::in(['aktif', 'cuti', 'lulus', 'do'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $validator->errors()->toArray(),
            ], 422);
        }

        $validated = $validator->validated();

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
            'status'  => 'success',
            'message' => 'Mahasiswa berhasil dicatat.',
            'data'    => [
                'mahasiswa'      => $mahasiswa,
                'receipt_number' => $receiptNumber,
                'rabbit_status'  =>  $rabbitStatus,
            ],
            'meta'    => [
                'service_name' => 'Data-Mahasiswa-Service',
                'api_version'  => 'v1',
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