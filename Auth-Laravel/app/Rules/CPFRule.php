<?php

namespace App\Rules;

use Closure;
use App\Traits\IsCPFValid;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Validation\ValidationRule;

class CPFRule implements ValidationRule
{
    use IsCPFValid;


    public function __construct() {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        $validator = Validator::make([$attribute => $value], [
            $attribute => 'string|digits:11',
        ]);

        // Verificacao basica de formato
        if ($validator->fails()) {
            $fail($validator->errors()->first());

            return;
        }

        // Valida o calculo do dígito verificador
        if (env('APP_ENV') == 'production' && !$this->isCPFValid($value)) {
            $fail('O CPF informado não é valido');

            return;
        }
    }
}
