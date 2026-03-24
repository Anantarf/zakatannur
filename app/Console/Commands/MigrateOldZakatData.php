<?php

namespace App\Console\Commands;

use App\Models\Muzakki;
use App\Models\User;
use App\Models\ZakatTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateOldZakatData extends Command
{
    protected $signature = 'zakat:migrate-old-db {file : The path to the old SQL dump file} {--dry-run : Only simulate the migration} {--fresh : Wipe existing Muzakki/Transactions before start}';
    protected $description = 'Migrate data from an old Zakat system SQL dump to the current system with advanced parsing';

    private $muzakkiMap = []; // old_id => new_id
    private $transactionHeaders = []; // old_id => data array
    private $userMap = []; // old_user_id => username (e.g., IRK1)
    private $petugasId;

    public function handle()
    {
        $filePath = $this->argument('file');
        $isDryRun = $this->option('dry-run');
        $isFresh = $this->option('fresh');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        if ($isFresh && !$isDryRun) {
            $this->warn("Wiping Muzakki and ZakatTransaction tables...");
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            ZakatTransaction::truncate();
            Muzakki::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $this->info("Starting migration from: {$filePath}" . ($isDryRun ? " (DRY RUN)" : ""));

        // Get a default petugas (first admin)
        $this->petugasId = User::where('role', 'admin')->first()?->id ?? User::first()?->id;

        // Pass 0: Handle Users (for IRK mapping)
        $this->info("Step 0/4: Mapping Users/Staff...");
        $this->parseInsertStatements($filePath, 'user', function ($data) {
            $oldId = $data['id'] ?? null;
            $username = $data['username'] ?? '';
            if ($oldId) {
                $this->userMap[$oldId] = $username;
            }
        });

        // Pass 1: Handle Muzakki
        $this->info("Step 1/4: Migrating Muzakkis...");
        $this->parseInsertStatements($filePath, 'muzakki', function ($data) use ($isDryRun) {
            $oldId = $data['id'] ?? null;
            $name = $data['name'] ?? 'Unknown';
            $address = $data['address'] ?? '';
            $phone = $data['phone'] ?? '';

            $muzakki = Muzakki::where('name', $name)->first();
            if (!$muzakki && !$isDryRun) {
                $muzakki = Muzakki::create([
                    'name' => $name,
                    'address' => $address,
                    'phone' => $phone,
                ]);
            }

            if ($muzakki) {
                $this->muzakkiMap[$oldId] = $muzakki->id;
            }
        });

        // Pass 2: Handle Transaction Headers
        $this->info("Step 2/4: Collecting Transaction Headers...");
        $headerCallback = function ($data) {
            $oldId = $data['id'] ?? $data['transaction_id'] ?? null;
            if ($oldId && !isset($this->transactionHeaders[$oldId])) {
                $oldUser = $data['transaction_user'] ?? '';
                $username = $this->userMap[$oldUser] ?? '';
                
                // Map IRK to shift
                $shift = ZakatTransaction::SHIFT_PAGI;
                if (strpos($username, 'IRK2') !== false) $shift = ZakatTransaction::SHIFT_SIANG;
                elseif (strpos($username, 'IRK3') !== false) $shift = ZakatTransaction::SHIFT_MALAM;

                $this->transactionHeaders[$oldId] = [
                    'date' => $data['transaction_date'] ?? now(),
                    'name' => $data['transaction_name'] ?? '',
                    'address' => $data['transaction_address'] ?? '',
                    'phone' => $data['transaction_phone'] ?? '',
                    'shift' => $shift,
                ];
            }
        };
        $this->parseInsertStatements($filePath, 'transaction', $headerCallback);
        $this->parseInsertStatements($filePath, 'transaction_temp', $headerCallback);

        // Pass 3: Handle Zakat Transactions
        $this->info("Step 3/4: Migrating Zakat Transactions...");
        $count = 0;
        $totalNominal = 0;
        $this->parseInsertStatements($filePath, 'zakat_transaction', function ($data) use ($isDryRun, &$count, &$totalNominal) {
            $oldTransId = $data['transaction_id'] ?? null;
            $header = $this->transactionHeaders[$oldTransId] ?? null;

            if (!$header) return;
            
            // Filter only 'activated' or 'edited' status for transaction data
            $status = $data['zakat_transaction_status'] ?? '';
            if ($status !== 'activated' && $status !== 'edited') return;

            $oldMuzakkiId = $data['zakat_muzakki_id'] ?? null;
            $newMuzakkiId = $this->muzakkiMap[$oldMuzakkiId] ?? null;

            $oldType = $data['zakat_type_id'] ?? '';
            $category = match ($oldType) {
                'FTR' => ZakatTransaction::CATEGORY_FITRAH,
                'MAL' => ZakatTransaction::CATEGORY_MAL,
                'FDY' => ZakatTransaction::CATEGORY_FIDYAH,
                'SDQ' => ZakatTransaction::CATEGORY_INFAK,
                default => ZakatTransaction::CATEGORY_INFAK,
            };

            $oldTransType = strtolower($data['transaction_type'] ?? '');
            $metode = str_contains($oldTransType, 'beras') ? ZakatTransaction::METHOD_BERAS : ZakatTransaction::METHOD_UANG;

            $nominalUang = (int)($data['income_value'] ?? 0);
            $jumlahBeras = (float)($data['income_goods'] ?? 0);
            $date = $header['date'];
            $year = date('Y', strtotime($date));

            if (!$isDryRun) {
                ZakatTransaction::create([
                    'no_transaksi' => $oldTransId,
                    'muzakki_id' => $newMuzakkiId,
                    'category' => $category,
                    'metode' => $metode,
                    'nominal_uang' => $nominalUang,
                    'jumlah_beras_kg' => $jumlahBeras,
                    'tahun_zakat' => $year,
                    'waktu_terima' => $date,
                    'petugas_id' => $this->petugasId,
                    'status' => ZakatTransaction::STATUS_VALID,
                    'pembayar_nama' => $header['name'],
                    'pembayar_alamat' => $header['address'],
                    'pembayar_phone' => $header['phone'],
                    'shift' => $header['shift'],
                ]);
            }
            $count++;
            if ($year == 2026) {
                $totalNominal += $nominalUang;
            }
        });

        $this->info("Migration completed! Total records: {$count}");
        $this->info("Total Nominal for 2026: " . number_format($totalNominal));
        return 0;
    }

    private function parseInsertStatements($filePath, $tableName, $callback)
    {
        $handle = fopen($filePath, "r");
        if (!$handle) return;

        $collecting = false;
        $columns = [];
        $insertIntoPattern = "/INSERT INTO `{$tableName}` \((.*?)\) VALUES/i";

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if (empty($line)) continue;

            if (preg_match($insertIntoPattern, $line, $matches)) {
                $columns = array_map(fn($c) => trim($c, ' `'), explode(',', $matches[1]));
                $collecting = true;
                $valuesPart = substr($line, strpos($line, 'VALUES') + 7);
                $this->processValues($valuesPart, $columns, $callback);
                continue;
            }

            if ($collecting) {
                $isLast = str_ends_with($line, ';');
                $this->processValues($line, $columns, $callback);
                if ($isLast) $collecting = false;
            }
        }
        fclose($handle);
    }

    private function processValues($string, $columns, $callback)
    {
        // Clean trailing comma or semicolon
        $string = rtrim(trim($string), ',;');
        if (empty($string)) return;

        // Split by "), (" but respect escapes
        // Standard MySQL dump rows are (v, v), (v, v)
        // We handle multiple rows in a single string (from single-line or multi-line inserts)
        $rows = preg_split('/\),\s*\(/', trim($string, " ()"));
        
        foreach ($rows as $row) {
            // MySQL escapes single quotes as \'
            // str_getcsv expects enclosure to be doubled for escape (e.g. '')
            $row = str_replace("\\'", "''", $row); 
            $values = str_getcsv($row, ",", "'");
            
            $data = [];
            foreach ($columns as $i => $col) {
                $val = $values[$i] ?? null;
                if ($val === 'NULL' || $val === 'null' || $val === null) $val = null;
                $data[$col] = $val;
            }
            $callback($data);
        }
    }
}
