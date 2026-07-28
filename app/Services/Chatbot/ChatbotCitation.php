<?php

namespace App\Services\Chatbot;

// Replaces the raw ['id' => ..., 'label' => ...] arrays that used to get built ad-hoc at every
// citation call site. That's exactly how Bab 10.17's bug happened: KnowledgeBase::toKnowledgeArray()
// exposes 'source_label', but citations were expected to carry 'label' - a typo-shaped mismatch
// silent to PHP, only caught by manually reading the JSON response. A constructor with named,
// typed properties makes that class of mismatch a compile-time/IDE-time error instead.
final class ChatbotCitation
{
    public ?string $id;
    public string $label;

    public function __construct(
        ?string $id,
        string $label
    ) {
        $this->id = $id;
        $this->label = $label;
    }

    // Builds a citation from a KnowledgeBase::toKnowledgeArray()-shaped entry - the one place
    // 'source_label' (not 'label') is the correct field to read from.
    public static function fromKnowledgeArray(array $entry, string $fallbackLabel = 'Panduan Zakat Masjid An-Nur'): self
    {
        return new self($entry['id'] ?? null, $entry['source_label'] ?? $fallbackLabel);
    }

    public function toArray(): array
    {
        return ['id' => $this->id, 'label' => $this->label];
    }
}
