<?php

namespace App\Services\Payments;

use Illuminate\Support\Manager;

class GatewayManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return config('payments.driver');
    }

    public function mockSelected(): bool
    {
        // A production deployment must explicitly opt in to simulated payments.
        return $this->getDefaultDriver() === 'mock'
            && (! app()->isProduction() || config('payments.mock_explicit'));
    }

    public function callbackAuthority(string $driver): string
    {
        return config("payments.drivers.$driver.fields.callback_authority", 'authority');
    }

    protected function createMockDriver(): MockGateway
    {
        if (! $this->mockSelected()) {
            throw new PaymentException('unconfigured');
        }

        return new MockGateway;
    }

    protected function createGatewayDriver(): GatewayDriver
    {
        return new GatewayDriver(config('payments.drivers.gateway'));
    }
}
