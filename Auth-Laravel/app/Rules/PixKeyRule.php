<?php

namespace App\Rules;

use App\Traits\IsCPFValid;
use Illuminate\Contracts\Validation\Rule;

class PixKeyRule implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    use IsCPFValid;

    public function passes($attribute, $value)
    {
        return $this->isCPFValid($value) ||
            $this->isCnpj($value) ||
            $this->isEmail($value) ||
            $this->isPhoneNumber($value) ||
            $this->isUuid($value);
    }

    public function message(): string
    {
        return 'Chave pix inválida.';
    }

    /**
     * Validate CNPJ.
     *
     * @param  string  $value
     * @return bool
     */
    private function isCnpj($value)
    {
        $value = preg_replace('/[^0-9]/', '', $value);

        if (strlen($value) != 14 || preg_match('/(\d)\1{13}/', $value)) {
            return false;
        }

        for ($t = 12; $t < 14; $t++) {
            for ($d = 0, $c = 0, $p = ($t - 7); $c < $t; $c++) {
                $d += (int) $value[$c] * $p;
                $p = ($p == 2) ? 9 : --$p;
            }
            $d = ((10 * $d) % 11) % 10;
            if ($value[$c] != $d) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate Email.
     *
     * @param  string  $value
     * @return bool
     */
    private function isEmail($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate Phone Number.
     *
     * @param  string  $value
     * @return bool
     */
    private function isPhoneNumber($value): int|bool
    {
        return preg_match("/^\+[1-9][0-9]\d{1,14}$/", $value);
    }

    /**
     * Validate UUID.
     *
     * @param  string  $value
     * @return bool
     */
    private function isUuid($value): int|bool
    {
        return preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $value);
    }
}
