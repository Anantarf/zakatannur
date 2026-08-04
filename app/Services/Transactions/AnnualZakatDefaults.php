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
    public ?int $nishabAnnualRupiah;

    public function __construct(
        int $fitrahCashPerJiwa,
        int $fidyahPerHari,
        float $fitrahBerasPerJiwa,
        float $fidyahBerasPerHari,
        int $nishabGoldGram = 85,
        int $goldPricePerGram = 1078609,
        ?int $nishabAnnualRupiah = null
    ) {
        $this->fitrahCashPerJiwa = $fitrahCashPerJiwa;
        $this->fidyahPerHari = $fidyahPerHari;
        $this->fitrahBerasPerJiwa = $fitrahBerasPerJiwa;
        $this->fidyahBerasPerHari = $fidyahBerasPerHari;
        $this->nishabGoldGram = $nishabGoldGram;
        $this->goldPricePerGram = $goldPricePerGram;
        $this->nishabAnnualRupiah = $nishabAnnualRupiah;
    }

    /** Nisab tahunan aktual dipakai untuk kalkulasi zakat mal - pakai override rupiah langsung (mis. SK BAZNAS) bila diisi, jika tidak jatuh ke gram emas x harga emas. */
    public function nishabAnnual(): int
    {
        return $this->nishabAnnualRupiah ?? ($this->nishabGoldGram * $this->goldPricePerGram);
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
