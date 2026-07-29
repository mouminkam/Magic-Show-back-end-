<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLanguage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $raw = $request->header('Accept-Language', 'en');
        // Extract primary language from BCP 47 format (e.g. "ar-SA,ar;q=0.9,en;q=0.8" -> "ar")
        $language = 'en';
        if ($raw && preg_match('/^([a-z]{2})/', strtolower(trim(explode(',', $raw)[0] ?? '')), $m)) {
            $language = $m[1];
        }
        if (!in_array($language, ['ar', 'en'])) {
            $language = 'en';
        }
        App::setLocale($language);
        
        return $next($request);
    }
}
