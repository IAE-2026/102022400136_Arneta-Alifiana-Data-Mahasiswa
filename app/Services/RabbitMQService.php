<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RabbitMQService
{
    protected $ssoUrl = 'https://iae-sso.virtualfri.id';

    public function publish(string $eventName, array $data, string $token): array
    {
        // Format payload sesuai requirement Cloud Dosen
        $payload = [
        'routing_key' => $eventName,
        'message'     => [
        'event'     => $eventName,
        'timestamp' => now()->toISOString(),
        'team_id'   => 'TEAM-09',
        'data'      => $data,
    ],
        ];

        $response = Http::withToken($token)
            ->post("{$this->ssoUrl}/api/v1/messages/publish", $payload);

        if (!$response->successful()) {
            return [
                'success' => false,
                'message' => 'Gagal publish event ke RabbitMQ.',
                'error'   => $response->body(),
            ];
        }

        return [
            'success' => true,
            'message' => 'Event berhasil dipublish ke RabbitMQ.',
            'data'    => $response->json(),
        ];
    }
}