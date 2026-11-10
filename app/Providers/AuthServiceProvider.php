<?php

namespace App\Providers;

use App\Models\AuditEvent;
use App\Models\Complex;
use App\Policies\AuditEventPolicy;
use App\Policies\ComplexPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        AuditEvent::class => AuditEventPolicy::class,
        Complex::class => ComplexPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
