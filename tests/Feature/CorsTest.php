<?php

use function Pest\Laravel\call;

it('allows any vercel.app origin on an API preflight request', function () {
    $origin = 'https://vue-demo-website.vercel.app';

    call('OPTIONS', '/api/about', server: [
        'HTTP_ORIGIN' => $origin,
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
    ])->assertHeader('Access-Control-Allow-Origin', $origin);
});

it('allows a custom origin listed in FRONTEND_URLS', function () {
    $origin = 'https://app.example.com';
    config()->set('cors.allowed_origins', [$origin]);

    call('OPTIONS', '/api/about', server: [
        'HTTP_ORIGIN' => $origin,
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
    ])->assertHeader('Access-Control-Allow-Origin', $origin);
});

it('does not expose CORS headers to a disallowed origin', function () {
    call('OPTIONS', '/api/about', server: [
        'HTTP_ORIGIN' => 'https://evil.example.com',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
    ])->assertHeaderMissing('Access-Control-Allow-Origin');
});
