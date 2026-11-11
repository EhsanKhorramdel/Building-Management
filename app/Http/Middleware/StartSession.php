<?php

namespace App\Http\Middleware;

use App\Http\Controllers\GroupController;
use Illuminate\Session\Middleware\StartSession as LaravelStartSession;

class StartSession extends LaravelStartSession
{
    protected function saveSession($request)
    {
        // Restoring a panel starts background requests that can overlap a form redirect.
        if ($request->expectsJson() && ($request->routeIs('building.preferences', 'building.payments', 'announcements.read')
            || $request->route()?->getControllerClass() === GroupController::class)) {
            return;
        }

        parent::saveSession($request);
    }
}
