<?php

namespace Plinth\MultiTenantBilling\Core\Client;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;

class StripeClient
{
    protected string $baseUrl = 'https://api.stripe.com/v1';
    protected string $secretKey;

    public function __construct(string $secretKey)
    {
        $this->secretKey = $secretKey;
    }

    /**
     * Prepare a request with Stripe authentication and headers.
     */
    public function request(string $method = 'GET', string $path = '', array $body = [], array $extraHeaders = []): PendingRequest
    {
        $headers = array_merge([
            'Content-Type' => 'application/x-www-form-urlencoded',
        ], $extraHeaders);

        return Http::withToken($this->secretKey)
            ->baseUrl($this->baseUrl)
            ->withHeaders($headers)
            ->asForm();
    }
}
