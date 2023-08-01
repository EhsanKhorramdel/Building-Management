<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use App\Models\UserRole;

class AccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role)
    {
        $userId = auth()->id();

        $access = UserRole::where('user_id', $userId)->where('role', $role)->exists();

        if (!$access) {
            abort(403, 'اجازه ی دسترسی به این صفحه را ندارید');
        }

        return $next($request);
    }
}
