<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ETag + short Cache-Control for anonymous GET JSON (products/categories list).
 * Helps caches and conditional requests; pair with CACHE_STORE=redis in production.
 */
class AddWeakEtagPublicApi
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200) {
            return $response;
        }

        $content = $response->getContent();
        if ($content === false || $content === '') {
            return $response;
        }

        $etag = 'W/"'.md5($content).'"';
        if ($request->headers->get('If-None-Match') === $etag) {
            return response('', 304, [
                'ETag' => $etag,
                'Cache-Control' => 'public, max-age=60',
            ]);
        }

        $response->headers->set('ETag', $etag);
        $response->headers->set('Cache-Control', 'public, max-age=60');

        return $response;
    }
}
