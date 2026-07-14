<?php

namespace App\Services\Transactions;

final class AnnualZakatDefaults
{
    public int $fitrahCashPerJiwa;
    public int $fidyahPerHari;
    public float $fitrahBerasPerJiwa;
    public float $fidyahBerasPerHari;
    public int $nishabGoldGram;
    public int $goldPricePerGram;

    public function __construct(
        int $fitrahCashPerJiwa,
        int $fidyahPerHari,
        float $fitrahBerasPerJiwa,
        float $fidyahBerasPerHari,
        int $nishabGoldGram = 85,
        int $goldPricePerGram = 900000
    ) {
        $this->fitrahCashPerJiwa = $fitrahCashPerJiwa;
        $this->fidyahPerHari = $fidyahPerHari;
        $this->fitrahBerasPerJiwa = $fitrahBerasPerJiwa;
        $this->fidyahBerasPerHari = $fidyahBerasPerHari;
        $this->nishabGoldGram = $nishabGoldGram;
        $this->goldPricePerGram = $goldPricePerGram;
    }

    /** @return array{0:int,1:int,2:float,3:float} */
    public function toTuple(): array
    {
        return [
            $this->fitrahCashPerJiwa,
            $this->fidyahPerHari,
            $this->fitrahBerasPerJiwa,
            $this->fidyahBerasPerHari,
        ];
    }
}
