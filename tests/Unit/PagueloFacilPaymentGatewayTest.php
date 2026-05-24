<?php

use Plinth\MultiTenantBilling\Providers\PagueloFacil\PagueloFacilPaymentGateway;
use Plinth\MultiTenantBilling\Core\Client\PagueloFacilClient;
use Illuminate\Http\Request;

it('generates a paguelofacil checkout url', function () {
    $client = new PagueloFacilClient('test_cclw', 'sandbox');
    $gateway = new PagueloFacilPaymentGateway($client);

    $payload = [
        'amount' => 10.50,
        'description' => 'Test Order',
        'success_url' => 'https://example.com/success',
        'order_id' => 'order_123'
    ];

    $result = $gateway->createCheckout($payload);

    expect($result['url'])->toContain('https://sandbox.paguelofacil.com/LinkDeamon.cfm')
        ->and($result['url'])->toContain('CCLW=test_cclw')
        ->and($result['url'])->toContain('CMTN=10.50')
        ->and($result['url'])->toContain('RETURN_URL=' . bin2hex('https://example.com/success'));
});

it('verifies a valid paguelofacil webhook', function () {
    $client = new PagueloFacilClient('test_cclw', 'sandbox');
    $gateway = new PagueloFacilPaymentGateway($client);

    $request = Request::create('/webhooks', 'POST', [
        'CCLW' => 'test_cclw',
        'Estado' => 'COMPLETED'
    ]);

    expect($gateway->verifyWebhook($request))->toBeTrue();
});

it('rejects a paguelofacil webhook with invalid CCLW', function () {
    $client = new PagueloFacilClient('test_cclw', 'sandbox');
    $gateway = new PagueloFacilPaymentGateway($client);

    $request = Request::create('/webhooks', 'POST', [
        'CCLW' => 'wrong_cclw',
        'Estado' => 'COMPLETED'
    ]);

    expect($gateway->verifyWebhook($request))->toBeFalse();
});
