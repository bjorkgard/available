<?php

use App\Rules\NotJwpubEmail;
use Illuminate\Support\Facades\Validator;

it('rejects emails ending with @jwpub.org', function (string $email) {
    $validator = Validator::make(
        ['email' => $email],
        ['email' => ['required', 'email', new NotJwpubEmail]],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('email'))->toBeTrue();
})->with([
    'lowercase' => 'someone@jwpub.org',
    'uppercase' => 'SOMEONE@JWPUB.ORG',
    'mixed case' => 'User@JwPub.Org',
]);

it('allows emails not from jwpub.org', function (string $email) {
    $validator = Validator::make(
        ['email' => $email],
        ['email' => ['required', 'email', new NotJwpubEmail]],
    );

    expect($validator->fails())->toBeFalse();
})->with([
    'gmail' => 'user@gmail.com',
    'custom domain' => 'elder@congregation.se',
    'similar domain' => 'user@notjwpub.org',
    'subdomain' => 'user@sub.jwpub.org',
]);
