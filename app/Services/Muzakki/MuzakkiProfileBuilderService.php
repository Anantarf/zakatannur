<?php

namespace App\Services\Muzakki;

use App\Models\Muzakki;

class MuzakkiProfileBuilderService
{
    /**
     * Resolves and intelligently preserves Muzakki identity data without silent data loss.
     */
    public function resolveProfile(string $name, string $phone, string $address): Muzakki
    {
        $normalizedName = $this->normalizeString($name);
        $normalizedPhone = preg_replace('/[^0-9]/', '', $phone);
        $normalizedAddress = $this->normalizeString($address);

        $muzakki = null;

        // Try to match by phone if it exists
        if ($normalizedPhone !== '') {
            $muzakki = Muzakki::withTrashed()
                ->where('phone', $normalizedPhone)
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($normalizedName)])
                ->first();
        }

        // If not matched by phone, try to match by name and exact address
        if (!$muzakki) {
            $muzakki = Muzakki::withTrashed()
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($normalizedName)])
                ->whereRaw('LOWER(TRIM(address)) = ?', [strtolower($normalizedAddress)])
                ->first();
        }

        if (!$muzakki) {
            // Create new if not found
            return Muzakki::create([
                'name' => $normalizedName,
                'phone' => $normalizedPhone,
                'address' => trim($address),
            ]);
        }

        // Restore if trashed
        if ($muzakki->trashed()) {
            $muzakki->restore();
        }

        $updates = [];

        // Only fill the phone when the existing profile has none and the form provided one
        if ($normalizedPhone !== '' && empty($muzakki->phone)) {
            $updates['phone'] = $normalizedPhone;
        }

        // Honor the operator's input: if the form provides a non-empty address,
        // keep it in sync with the profile. This avoids silent data loss when
        // staff re-enters the same transaction (e.g. after restoring a muzakki).
        if (trim($address) !== '') {
            $updates['address'] = trim($address);
        }

        if (!empty($updates)) {
            $muzakki->update($updates);
        }

        return $muzakki;
    }

    private function normalizeString(string $input): string
    {
        return trim(preg_replace('/\s+/', ' ', $input));
    }
}