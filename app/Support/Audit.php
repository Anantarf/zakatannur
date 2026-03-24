<?php

namespace App\Support;

final class Audit
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param array<string,mixed> $metadata
     */
    public static function log($request, string $action, ?object $subject = null, array $metadata = []): void
    {
        $data = [
            'actor_user_id' => $request->user()?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        \App\Jobs\LogAuditRequest::dispatch($data)->afterResponse();
    }
}
