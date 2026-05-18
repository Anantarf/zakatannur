@props(['categories'])

@php
    if (is_string($categories)) {
        $categories = explode(',', $categories);
    }

    $categoriesArr = is_array($categories) ? array_map('trim', $categories) : [];
    $categoriesArr = array_filter($categoriesArr);

    $priority = [
        \App\Models\ZakatTransaction::CATEGORY_FITRAH => 1,
        \App\Models\ZakatTransaction::CATEGORY_FIDYAH => 2,
        \App\Models\ZakatTransaction::CATEGORY_MAL    => 3,
        \App\Models\ZakatTransaction::CATEGORY_INFAK  => 4,
    ];

    usort($categoriesArr, function($a, $b) use ($priority) {
        $pA = $priority[$a] ?? 99;
        $pB = $priority[$b] ?? 99;
        return $pA <=> $pB;
    });

    $rows = [];
    if (count($categoriesArr) > 0) {
        $hasInfaq = in_array(\App\Models\ZakatTransaction::CATEGORY_INFAK, $categoriesArr);
        
        if ($hasInfaq && count($categoriesArr) > 1) {
            $others = array_values(array_filter($categoriesArr, fn($c) => $c !== \App\Models\ZakatTransaction::CATEGORY_INFAK));
            $infaq = [\App\Models\ZakatTransaction::CATEGORY_INFAK];
            
            if (count($others) <= 2) {
                $rows[] = $others;
                $rows[] = $infaq;
            } else {
                $rows[] = array_slice($others, 0, 2);
                $rows[] = array_merge(array_slice($others, 2), $infaq);
            }
        } else {
            $rows = array_chunk($categoriesArr, 2);
        }
    }
@endphp

<div class="flex flex-col gap-1.5 items-center">
    @foreach($rows as $row)
        <div class="flex gap-1 justify-center">
            @foreach($row as $cat)
                <span class="ui-badge ui-badge-token ui-badge-token-emerald text-[9px] font-bold">
                    {{ \App\Models\ZakatTransaction::CATEGORY_LABELS[$cat] ?? strtoupper($cat) }}
                </span>
            @endforeach
        </div>
    @endforeach
</div>
