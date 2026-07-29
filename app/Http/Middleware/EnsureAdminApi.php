<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminApi
{
    /**
     * Allow only User model (admin) with super_admin or store_manager role.
     * Used for API admin routes (auth:sanctum must run first).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => __('errors.admin_only'),
                ],
            ], 403);
        }

        if (!$user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_STORE_MANAGER])) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => __('errors.admin_only'),
                ],
            ], 403);
        }

        return $next($request);
    }
}
