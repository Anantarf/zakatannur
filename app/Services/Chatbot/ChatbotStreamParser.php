<?php

namespace App\Services\Chatbot;

// Consumes raw provider stream chunks, swallows [SUGGEST:]/[HITUNG:] sentinels (surfacing
// [HITUNG:]'s computed result live, keeping [SUGGEST:] hidden), and yields only complete
// sentences so a guardrail violation is caught at a sentence boundary instead of after an
// arbitrary run of raw chunks. One instance per stream call - it holds per-call state.
class ChatbotStreamParser
{
    private string $fullReply = '';
    private bool $guardrailTripped = false;

    public function __construct(
        private ChatbotSentinelParser $sentinelParser,
        private ChatbotGuardrailVerifier $guardrailVerifier
    ) {
    }

    public function fullReply(): string
    {
        return $this->fullReply;
    }

    public function guardrailTripped(): bool
    {
        return $this->guardrailTripped;
    }

    public function parse(iterable $stream): \Generator
    {
        $buffer = '';
        $sentenceBuffer = '';
        $isSwallowing = false;
        $swallowingType = null;

        foreach ($stream as $chunk) {
            $this->fullReply .= $chunk;
            $buffer .= $chunk;

            while (strlen($buffer) > 0) {
                if ($isSwallowing) {
                    $pos = strpos($buffer, ']');
                    if ($pos !== false) {
                        $isSwallowing = false;
                        $sentinel = substr($buffer, 0, $pos + 1);
                        $buffer = substr($buffer, $pos + 1);

                        // [HITUNG:...] must surface its computed result live; [SUGGEST:...] stays hidden.
                        if ($swallowingType === 'hitung') {
                            $computed = trim($this->sentinelParser->parseAndCalculateSentinel($sentinel));
                            if ($computed !== '') {
                                $sentenceBuffer .= $computed;
                            }
                            // Replace it in fullReply too, so finalizeAiReply's later pass finds
                            // no sentinel left and doesn't redo the same DB lookup + calculation.
                            $this->fullReply = str_replace($sentinel, $computed, $this->fullReply);
                        }
                        $swallowingType = null;
                    } else {
                        break; // Wait for ]
                    }
                } else {
                    $pos = strpos($buffer, '[');
                    if ($pos !== false) {
                        $yieldStr = substr($buffer, 0, $pos);
                        if ($yieldStr !== '') {
                            $sentenceBuffer .= $yieldStr;
                            $buffer = substr($buffer, $pos);
                        }

                        $prefix9 = substr($buffer, 0, 9);
                        $prefix8 = substr($buffer, 0, 8);

                        if (strlen($buffer) < 9) {
                            if (!str_starts_with("[SUGGEST:", strtoupper($prefix9)) && !str_starts_with("[HITUNG:", strtoupper($prefix8))) {
                                $sentenceBuffer .= '[';
                                $buffer = substr($buffer, 1);
                            } else {
                                break; // Wait for more chars
                            }
                        } else {
                            if (strtoupper($prefix9) === '[SUGGEST:') {
                                $isSwallowing = true;
                                $swallowingType = 'suggest';
                            } elseif (strtoupper($prefix8) === '[HITUNG:') {
                                $isSwallowing = true;
                                $swallowingType = 'hitung';
                            } else {
                                $sentenceBuffer .= '[';
                                $buffer = substr($buffer, 1);
                            }
                        }
                    } else {
                        $sentenceBuffer .= $buffer;
                        $buffer = '';
                    }
                }
            }

            foreach ($this->extractCompleteSentences($sentenceBuffer) as $sentence) {
                if ($this->guardrailVerifier->verify($this->fullReply) !== null) {
                    $this->guardrailTripped = true;
                    break;
                }
                yield $sentence;
            }

            if ($this->guardrailTripped) {
                break;
            }
        }

        if (!$this->guardrailTripped) {
            if ($buffer !== '' && !$isSwallowing) {
                $sentenceBuffer .= $buffer;
            }
            if ($sentenceBuffer !== '' && $this->guardrailVerifier->verify($this->fullReply) === null) {
                yield $sentenceBuffer;
            }
        }
    }

    private function extractCompleteSentences(string &$sentenceBuffer): array
    {
        $sentences = [];

        while (true) {
            if (preg_match('/^.*?[.!?\n]/s', $sentenceBuffer, $matches)) {
                $sentences[] = $matches[0];
                $sentenceBuffer = substr($sentenceBuffer, strlen($matches[0]));
                continue;
            }

            if (strlen($sentenceBuffer) > 200) {
                $sentences[] = $sentenceBuffer;
                $sentenceBuffer = '';
            }

            break;
        }

        return $sentences;
    }
}
