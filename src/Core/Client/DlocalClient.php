<?php

namespace Plinth\MultiTenantBilling\Core\Client;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;

class DlocalClient
{
    protected string $baseUrl;
    protected string $login;
    protected string $transKey;
    protected string $secretKey;
    
    public function __construct(string $login, string $transKey, string $secretKey, string $environment = 'sandbox')
    {
        $this->login = $login;
        $this->transKey = $transKey;
        $this->secretKey = $secretKey;
        $this->baseUrl = $environment === 'production' 
            ? 'https://api.dlocal.com' 
            : 'https://sandbox.dlocal.com';
    }

    public function request(string $method = 'GET', string $path = '', array $body = [], array $extraHeaders = []): PendingRequest
    {
        $date = now()->setTimezone('UTC')->format('Y-m-d\TH:i:s.v\Z');
        
        $headers = array_merge([
            'X-Date' => $date,
            'X-Login' => $this->login,
            'X-Trans-Key' => $this->transKey,
            'Content-Type' => 'application/json',
        ], $extraHeaders);

        if (!empty($this->secretKey)) {
            $headers['Authorization'] = $this->generateSignature($date, $body);
        }

        return Http::baseUrl($this->baseUrl)->withHeaders($headers);
    }
    
    public function generateSignature(string $date, array $body = []): string
    {
        $bodyString = empty($body) ? '' : json_encode($body);
        $message = $this->login . $date . $bodyString;
        $signature = hash_hmac('sha256', $message, $this->secretKey);
        
        return "V21-HMAC-SHA256 Signature={$signature}";
    }
}
