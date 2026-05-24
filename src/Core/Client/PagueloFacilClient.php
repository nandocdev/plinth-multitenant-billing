<?php

namespace Plinth\MultiTenantBilling\Core\Client;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;

class PagueloFacilClient
{
    protected string $baseUrl;
    protected string $cclw;

    public function __construct(string $cclw, string $environment = 'sandbox')
    {
        $this->cclw = $cclw;
        $this->baseUrl = $environment === 'production'
            ? 'https://secure.paguelofacil.com'
            : 'https://sandbox.paguelofacil.com';
    }

    /**
     * Get the base URL for PagueloFacil.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Get the CCLW (Merchant ID).
     */
    public function getCclw(): string
    {
        return $this->cclw;
    }

    /**
     * Encode a URL to Hexadecimal format required by PagueloFacil.
     */
    public function encodeUrl(string $url): string
    {
        return bin2hex($url);
    }

    /**
     * Prepare a request for the PagueloFacil API (REST).
     */
    public function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $this->cclw,
            ]);
    }
}
