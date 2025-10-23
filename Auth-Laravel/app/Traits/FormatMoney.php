<?php


namespace App\Traits;

trait FormatMoney
{
    public function formatMoney(float $input): string
    {
        return number_format($input, 2, ',', '.');
    }
}
