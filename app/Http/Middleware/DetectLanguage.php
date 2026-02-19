<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class DetectLanguage
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Session::has('locale')) {

            $ip = $request->ip();

            if ($ip === '127.0.0.1') {
                $country = 'Other';
            } else {
                $response = Http::get("http://ip-api.com/json/{$ip}");
                $country = $response->json('country') ?? 'Other';
            }

            $arabicCountries = [
                'United Arab Emirates',
                'Egypt',
                'Bahrain',
                'Saudi Arabia',
                'Kuwait',
                'Iraq',
                'Oman',
                'Lebanon',
                'Jordan',
                'Qatar'
            ];

            if ($country === 'Israel') {
                $locale = 'he';
            } elseif (in_array($country, $arabicCountries)) {
                $locale = 'ar';
            } else {
                $locale = 'en';
            }

            session([
                'locale' => $locale,
                'country' => $country
            ]);
        }

        app()->setLocale(session('locale'));

        return $next($request);
    }
}
