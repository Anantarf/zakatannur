<?php

namespace App\Services\Chatbot\Knowledge;

class KnowledgeRetriever
{
    public function search(string $message, int $limit = 3): array
    {
        $message = $this->normalize($message);
        $entries = config('zakky_knowledge', []);
        $ranked = [];

        foreach ($entries as $entry) {
            $score = $this->score($message, $entry);
            if ($score <= 0) {
                continue;
            }

            $entry['_score'] = $score;
            $ranked[] = $entry;
        }

        usort($ranked, fn ($a, $b) => $b['_score'] <=> $a['_score']);

        return array_slice($ranked, 0, $limit);
    }

    public function best(string $message, int $threshold = 3): ?array
    {
        $results = $this->search($message, 1);
        $top = $results[0] ?? null;

        if (!$top || (int) ($top['_score'] ?? 0) < $threshold) {
            return null;
        }

        return $top;
    }

    private function score(string $message, array $entry): int
    {
        $score = 0;

        foreach (($entry['keywords'] ?? []) as $keyword) {
            $keyword = $this->normalize($keyword);
            if ($keyword !== '' && str_contains($message, $keyword)) {
                $score += str_contains($keyword, ' ') ? 5 : 3;
            }
        }

        foreach (explode(' ', $this->normalize((string) ($entry['title'] ?? ''))) as $token) {
            if (mb_strlen($token) >= 4 && str_contains($message, $token)) {
                $score += 1;
            }
        }

        return $score;
    }

    private function normalize(string $value): string
    {
        $value = preg_replace('/[^\pL\pN\s]/u', ' ', mb_strtolower($value)) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }
}
