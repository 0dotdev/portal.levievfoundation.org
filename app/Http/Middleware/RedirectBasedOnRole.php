<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectBasedOnRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only redirect authenticated users
        if (Auth::check()) {
            $user = Auth::user();
            $currentPath = $request->path();

            // Skip redirects for API routes, logout, asset requests, and auth routes
            if (
                str_starts_with($currentPath, 'api/') ||
                str_contains($currentPath, 'logout') ||
                str_contains($currentPath, 'login') ||
                str_contains($currentPath, 'register') ||
                str_contains($currentPath, 'password') ||
                str_contains($currentPath, 'email/verify') ||
                str_contains($currentPath, 'assets') ||
                str_contains($currentPath, 'css') ||
                str_contains($currentPath, 'js') ||
                str_contains($currentPath, 'images') ||
                $request->ajax()
            ) {
                return $next($request);
            }

            // If admin user is trying to access dashboard panel, redirect to admin
            if ($user->isAdmin() && (str_starts_with($currentPath, 'dashboard') || $currentPath === 'dashboard')) {
                return redirect('/admin');
            }

            // If non-admin user is trying to access admin panel, redirect to dashboard  
            if (!$user->isAdmin() && (str_starts_with($currentPath, 'admin') || $currentPath === 'admin')) {
                return redirect('/dashboard');
            }
        }

        return $next($request);
    }
}
