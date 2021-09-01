<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OauthController extends Controller
{
    public function redirect(Request $request)
    {
        $request->session()->put('state', $state = Str::random(40));

        $query = http_build_query([
            'client_id' => config('services.api_free.client_id'),
            'redirect_uri' => route('callback'),
            'response_type' => 'code',
            'scope' => 'create-post read-post update-post delete-post',
            'state' => $state,
        ]);

        return redirect(config('services.api_free.url').'/oauth/authorize?'.$query);
    }

    public function callback(Request $request)
    {
        $state = $request->session()->pull('state');

        throw_unless(
            strlen($state) > 0 && $state === $request->state,
            InvalidArgumentException::class
        );

        $response = Http::withOptions([
            'verify' => false
        ])->asForm()->post(config('services.api_free.url').'/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.api_free.client_id'),
            'client_secret' => config('services.api_free.client_secret'),
            'redirect_uri' => route('callback'),
            'code' => $request->code,
        ]);

        return $response->json();
    }
}
