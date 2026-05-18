<?php

namespace App\Services\Transactions;

final class TransactionHistoryFilters
{
    public string $q;
    public ?int $year;
    public ?string $category;
    public ?string $metode;
    public ?string $status;
    public ?int $petugasId;
    public ?string $riskLevel;
    public ?string $reviewStatus;
    public int $activeYear;

    public function __construct(
        string $q,
        ?int $year,
        ?string $category,
        ?string $metode,
        ?string $status,
        ?int $petugasId,
        ?string $riskLevel,
        ?string $reviewStatus,
        int $activeYear
    ) {
        $this->q = $q;
        $this->year = $year;
        $this->category = $category;
        $this->metode = $metode;
        $this->status = $status;
        $this->petugasId = $petugasId;
        $this->riskLevel = $riskLevel;
        $this->reviewStatus = $reviewStatus;
        $this->activeYear = $activeYear;
    }

    /**
     * @param array{q:string,year:int|null,category:?string,metode:?string,status:?string,petugasId:?int,riskLevel:?string,reviewStatus:?string,activeYear:int} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['q'],
            $data['year'],
            $data['category'],
            $data['metode'],
            $data['status'],
            $data['petugasId'],
            $data['riskLevel'],
            $data['reviewStatus'],
            $data['activeYear']
        );
    }

    /**
     * @return array{q:string,year:int|null,category:?string,metode:?string,status:?string,petugasId:?int,riskLevel:?string,reviewStatus:?string,activeYear:int}
     */
    public function toArray(): array
    {
        return [
            'q' => $this->q,
            'year' => $this->year,
            'category' => $this->category,
            'metode' => $this->metode,
            'status' => $this->status,
            'petugasId' => $this->petugasId,
            'riskLevel' => $this->riskLevel,
            'reviewStatus' => $this->reviewStatus,
            'activeYear' => $this->activeYear,
        ];
    }
}
