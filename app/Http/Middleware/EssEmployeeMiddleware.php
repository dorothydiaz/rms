<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EssEmployeeMiddleware
{
    /**
     * Handle an incoming request.
     * Enforces that authenticated user has an active employee record and scopes queries.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $employee = $user->employee;

        if (!$employee) {
            abort(403, 'Your user account is not linked to an employee profile. Please contact HR administration.');
        }

        // Attach employee to request attributes for fast downstream access
        $request->attributes->set('current_employee', $employee);

        // Share current employee globally with all views rendered within this request
        view()->share('currentEmployee', $employee);

        return $next($request);
    }
}
