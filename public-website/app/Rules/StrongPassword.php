<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Min. 12 Zeichen, Buchstaben + Zahl + Sonderzeichen (docs/turnier-plan.md Abschnitt 4). */
class StrongPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || strlen($value) < 12) {
            $fail('Das Passwort muss mindestens 12 Zeichen lang sein.');
            return;
        }

        if (!preg_match('/[A-Za-z]/', $value) || !preg_match('/[0-9]/', $value) || !preg_match('/[^A-Za-z0-9]/', $value)) {
            $fail('Das Passwort muss Buchstaben, Zahlen und Sonderzeichen enthalten.');
        }
    }
}
