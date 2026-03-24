@props(['categories'])

@php
    // Handle both string and array inputs
    if (is_string($categories)) {
        $categories = explode(',', $categories);
    }
    
    // Ensure it's an array and clean it up
    $categoriesArr = is_array($categories) ? array_map('trim', $categories) : [];
    $categoriesArr = array_filter($categoriesArr);

    // Define priority (smaller number = higher priority/top left)
    $priority = [
        \App\Models\ZakatTransaction::CATEGORY_FITRAH => 1,
        \App\Models\ZakatTransaction::CATEGORY_FIDYAH => 2,
        \App\Models\ZakatTransaction::CATEGORY_MAL    => 3,
        \App\Models\ZakatTransaction::CATEGORY_INFAK  => 4,
    ];

    // Sort categories based on priority
    usort($categoriesArr, function($a, $b) use ($priority) {
        $pA = $priority[$a] ?? 99;
        $pB = $priority[$b] ?? 99;
        return $pA <=> $pB;
    });

    $rows = [];
    if (count($categoriesArr) > 0) {
        // Special logic for Infaq (should be at the bottom if others exist)
        $hasInfaq = in_array(\App\Models\ZakatTransaction::CATEGORY_INFAK, $categoriesArr);
        
        if ($hasInfaq && count($categoriesArr) > 1) {
            // Group non-infaq categories
            $others = array_values(array_filter($categoriesArr, fn($c) => $c !== \App\Models\ZakatTransaction::CATEGORY_INFAK));
            $infaq = [\App\Models\ZakatTransaction::CATEGORY_INFAK];
            
            if (count($others) <= 2) {
                // e.g. [Fitrah, Mal] then [Infaq]
                $rows[] = $others;
                $rows[] = $infaq;
            } else {
                // e.g. [Fitrah, Fidyah, Mal] + [Infaq]
                // 1st row: Fitrah, Fidyah
                // 2nd row: Mal, Infaq
                $rows[] = array_slice($others, 0, 2);
                $rows[] = array_merge(array_slice($others, 2), $infaq);
            }
        } else {
            // Standard "2 per row" layout if no Infaq or only Infaq
            $rows = array_chunk($categoriesArr, 2);
        }
    }
@endphp

<div class="flex flex-col gap-1.5 items-center">
    @foreach($rows as $row)
        <div class="flex gap-1 justify-center">
            @foreach($row as $cat)
                <span class="inline-flex items-center justify-center rounded px-2 py-0.5 text-[9px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-100 whitespace-nowrap leading-tight text-center">
                    {{ \App\Models\ZakatTransaction::CATEGORY_LABELS[$cat] ?? strtoupper($cat) }}
                </span>
            @endforeach
        </div>
    @endforeach
</div>
