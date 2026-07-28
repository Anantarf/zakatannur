<?php

namespace Tests\Unit;

use Tests\TestCase;

class MigrationPolicyTest extends TestCase
{
    private const IRREVERSIBLE_DATA_CLEANUPS = [
        '2026_05_19_030000_collapse_suspicious_risk_level_to_warning.php',
        '2026_05_19_040000_cleanup_obsolete_transaction_risk_flags.php',
        '2026_05_19_050000_remove_infaq_outlier_risk_flags.php',
        '2026_07_17_134428_delete_stale_pre_consolidation_knowledge_base_rows.php',
    ];

    public function test_migrations_do_not_hide_empty_rollbacks(): void
    {
        $violations = [];

        foreach (glob(database_path('migrations/*.php')) as $path) {
            $contents = file_get_contents($path);
            $file = basename($path);

            if (! preg_match('/function\s+down\s*\([^)]*\)(?:\s*:\s*\w+)?\s*\{(?P<body>.*?)\n\s*\}/s', $contents, $match)) {
                $violations[] = "{$file} is missing down().";
                continue;
            }

            $body = trim(preg_replace('/\/\/.*$/m', '', $match['body']));

            if ($body !== '') {
                continue;
            }

            if (! in_array($file, self::IRREVERSIBLE_DATA_CLEANUPS, true)) {
                $violations[] = "{$file} has an empty down() without an irreversible-cleanup allowlist entry.";
                continue;
            }

            $this->assertStringContainsString('Irreversible data cleanup', $match['body'], "{$file} must explain why rollback is not safe.");
        }

        $this->assertSame([], $violations);
    }
}
