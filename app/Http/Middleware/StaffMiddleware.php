<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || (! ($user->is_staff ?? false) && ! ($user->is_admin ?? false))) {
            abort(403, 'Bạn không có quyền truy cập khu vực nhân viên.');
        }

        return $next($request);
    }
}
