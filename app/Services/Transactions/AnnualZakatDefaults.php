<?php

namespace App\Services\Transactions;

final class AnnualZakatDefaults
{
    public int $fitrahCashPerJiwa;
    public int $fidyahPerHari;
    public float $fitrahBerasPerJiwa;
    public float $fidyahBerasPerHari;

    public function __construct(
        int $fitrahCashPerJiwa,
        int $fidyahPerHari,
        float $fitrahBerasPerJiwa,
        float $fidyahBerasPerHari
    ) {
        $this->fitrahCashPerJiwa = $fitrahCashPerJiwa;
        $this->fidyahPerHari = $fidyahPerHari;
        $this->fitrahBerasPerJiwa = $fitrahBerasPerJiwa;
        $this->fidyahBerasPerHari = $fidyahBerasPerHari;
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
