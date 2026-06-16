<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class NotJwpubEmail implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $domain = strtolower(substr(strrchr($value, '@') ?: '', 1));

        if ($domain === 'jwpub.org') {
            $fail(__('E-mail addresses from @jwpub.org are not allowed.'));
        }
    }
}
