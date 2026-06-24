<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Log;

class ZakkyAdminAiWordingService
{
    /**
     * Generate natural language wording for rule-based insight (phase 2)
     * Currently returns rule-based message unchanged
     * ponytail: implement AI wording when phase 2 starts, use LLM for natural language only
     *
     * @param array{label: string, tone: string, message: string, items: array} $insight
     * @return array{label: string, tone: string, message: string, items: array, generated: bool}
     */
    public function enhance(array $insight): array
    {
        // Phase 1: Return as-is (rule-based)
        return array_merge($insight, ['generated' => false]);

        // Phase 2: Implement with OpenAI to rephrase for natural language
        // $cacheKey = 'zakky:wording:' . md5(json_encode($insight));
        // return cache()->remember($cacheKey, 1800, function () use ($insight) {
        //     try {
        //         $response = $this->callAI($insight);
        //         return array_merge($insight, ['message' => $response, 'generated' => true]);
        //     } catch (\Exception $e) {
        //         Log::warning('Zakky AI wording failed, using rule-based', ['error' => $e->getMessage()]);
        //         return array_merge($insight, ['generated' => false]);
        //     }
        // });
    }

    // Stub for future implementation
    // private function callAI(array $insight): string { ... }
}
