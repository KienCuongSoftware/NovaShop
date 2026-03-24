<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectLocalhostToLoopbackIp
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getHost() === 'localhost') {
            $uri = $request->getRequestUri();
            $port = $request->getPort();
            $portPart = ($port && $port !== 80 && $port !== 443) ? ':'.$port : '';
            $scheme = $request->getScheme();

            return redirect()->to($scheme.'://127.0.0.1'.$portPart.$uri, 301);
        }

        return $next($request);
    }
}
