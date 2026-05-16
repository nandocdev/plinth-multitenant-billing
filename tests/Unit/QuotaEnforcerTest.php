<?php

use Plinth\MultiTenantBilling\Billing\Usage\UsageManager;
use Plinth\MultiTenantBilling\Billing\Quotas\QuotaEnforcer;
use Plinth\MultiTenantBilling\Billing\Exceptions\QuotaExceededException;
use Illuminate\Support\Facades\Redis;

it('consumes quota correctly and atomically', function () {
    $tenantId = 1;
    $feature = 'api_calls';
    $limit = 5;

    // Mocking Redis
    Redis::shouldReceive('set')
        ->with("tenant:{$tenantId}:limit:{$feature}", $limit)
        ->andReturn(true);

    Redis::shouldReceive('get')
        ->with("tenant:{$tenantId}:limit:{$feature}")
        ->times(3)
        ->andReturn($limit);

    Redis::shouldReceive('incrby')
        ->with("tenant:{$tenantId}:usage:{$feature}", 3)
        ->once()
        ->andReturn(3);
        
    Redis::shouldReceive('incrby')
        ->with("tenant:{$tenantId}:usage:{$feature}", 2)
        ->once()
        ->andReturn(5);
        
    Redis::shouldReceive('incrby')
        ->with("tenant:{$tenantId}:usage:{$feature}", 1)
        ->once()
        ->andReturn(6);

    Redis::shouldReceive('incrby')
        ->with("tenant:{$tenantId}:usage:{$feature}", -1)
        ->once()
        ->andReturn(5);

    $usage = new UsageManager();
    $enforcer = new QuotaEnforcer($usage);
    
    $enforcer->setLimit($tenantId, $feature, $limit);
    
    // 1st consume
    $enforcer->consume($tenantId, $feature, 3);
    
    // 2nd consume
    $enforcer->consume($tenantId, $feature, 2);
    
    // 3rd consume (triggers exception)
    expect(fn() => $enforcer->consume($tenantId, $feature, 1))
        ->toThrow(QuotaExceededException::class);
});

it('allows unlimited consumption when limit is -1', function () {
    $tenantId = 1;
    $feature = 'storage';

    Redis::shouldReceive('set')->andReturn(true);
    Redis::shouldReceive('get')->with("tenant:{$tenantId}:limit:{$feature}")->andReturn(-1);
    Redis::shouldReceive('incrby')->with("tenant:{$tenantId}:usage:{$feature}", 1000)->andReturn(1000);

    $usage = new UsageManager();
    $enforcer = new QuotaEnforcer($usage);
    
    $enforcer->setLimit($tenantId, $feature, -1);
    
    $usageValue = $enforcer->consume($tenantId, $feature, 1000);
    expect($usageValue)->toBe(1000);
});
