<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;

class Emails implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $emails = explode(',', $value);
        $rules = [
            'email' => 'required|email',
        ];
        foreach ($emails as $email) {
            $data = [
                'email' => $email
            ];
            $validator = Validator::make($data, $rules);
            if ($validator->fails()) {
                $fail('The value must be a valid array of email address.');
            }
        }
    }
}
