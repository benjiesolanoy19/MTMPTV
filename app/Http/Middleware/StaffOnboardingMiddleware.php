<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffOnboardingMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user
            && $user->role === 'staff'
            && $user->staffApplicationIsApproved()
            && ! $user->canAccessStaff(),
            403
        );

        return $next($request);
    }
}
