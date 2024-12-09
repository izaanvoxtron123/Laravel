<?php

namespace App\Http\Middleware;

use Closure;
use GuzzleHttp\Client;

class VerifyRecaptcha
{
    public function handle($request, Closure $next)
    {
        return $next($request);
        $recaptchaToken = $request->input('g-recaptcha-response');
        $secretKey = config('services.recaptcha.secret');

        if ($recaptchaToken) {
            $client = new Client();
            $response = $client->post('https://www.google.com/recaptcha/api/siteverify', [
                'form_params' => [
                    'secret' => $secretKey,
                    'response' => $recaptchaToken,
                    'remoteip' => $request->ip()
                ]
            ]);

            $body = json_decode((string)$response->getBody(), true);

            if ($body['success'] && $body['score'] >= 0.5) {
                return $next($request);
            }
        }

        return redirect()->back()->withErrors(['captcha_error' => 'reCAPTCHA verification failed']);
    }
}
