<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Http;

class SoapAuditService
{
    protected $ssoUrl = 'https://iae-sso.virtualfri.id';
    protected $teamId = 'TEAM-09';

    public function sendAudit(string $activityName, array $logData, string $token): array
    {
        $logContent  = json_encode($logData);
        $xmlEnvelope = $this->buildSoapEnvelope($activityName, $logContent);

        $response = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'text/xml'])
            ->withBody($xmlEnvelope, 'text/xml')
            ->post("{$this->ssoUrl}/soap/v1/audit");

        if (!$response->successful()) {
            AuditLog::create([
                'activity_name'  => $activityName,
                'log_content'    => $logContent,
                'receipt_number' => null,
                'status'         => 'failed',
            ]);

            return [
                'success'        => false,
                'receipt_number' => null,
            ];
        }

        $receiptNumber = $this->parseReceiptNumber($response->body());

        AuditLog::create([
            'activity_name'  => $activityName,
            'log_content'    => $logContent,
            'receipt_number' => $receiptNumber,
            'status'         => 'success',
        ]);

        return [
            'success'        => true,
            'receipt_number' => $receiptNumber,
        ];
    }

    private function buildSoapEnvelope(string $activityName, string $logContent): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:iae="http://iae.central/audit">
    <soap:Body>
        <iae:AuditRequest>
            <iae:TeamID>' . $this->teamId . '</iae:TeamID>
            <iae:ActivityName>' . $activityName . '</iae:ActivityName>
            <iae:LogContent><![CDATA[' . $logContent . ']]></iae:LogContent>
        </iae:AuditRequest>
    </soap:Body>
</soap:Envelope>';
    }

    private function parseReceiptNumber(string $xmlResponse): ?string
    {
        preg_match('/<iae:ReceiptNumber>(.*?)<\/iae:ReceiptNumber>/', $xmlResponse, $matches);
        return $matches[1] ?? null;
    }
}