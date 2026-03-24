<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Muzakki extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'muzakki';

    protected $fillable = [
        'name',
        'address',
        'phone',
    ];

    /**
     * Get the number of days left before the Muzakki is permanently deleted.
     */
    public function getDaysLeftAttribute(): ?int
    {
        if (!$this->deleted_at) {
            return null;
        }

        $purgeDays = 30;
        $deletedAt = $this->deleted_at->timezone('Asia/Jakarta');
        return max(0, $purgeDays - (int) $deletedAt->startOfDay()->diffInDays(now('Asia/Jakarta')->startOfDay()));
    }

    /**
     * Get the formatted deletion date.
     */
    public function getDeletedAtFormattedAttribute(): string
    {
        if (!$this->deleted_at) {
            return '-';
        }

        return $this->deleted_at->timezone('Asia/Jakarta')->format('d/m/Y H:i');
    }

    /**
     * Scope a query to search by name, address, or phone.
     */
    public function scopeSearch($query, ?string $q)
    {
        return $query->when($q, function ($query, $search) {
            $like = '%' . str_replace('%', '\\%', $search) . '%';
            return $query->where(function ($sub) use ($like) {
                $sub->where('name', 'like', $like)
                    ->orWhere('address', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            });
        });
    }

    public static function firstOrCreateNormalized(array $data): self
    {
        $name = trim(preg_replace('/\s+/', ' ', (string) $data['muzakki_name']));
        $phone = preg_replace('/[^0-9]/', '', (string) ($data['muzakki_phone'] ?? ''));
        $address = trim((string) ($data['muzakki_address'] ?? ''));

        $criteria = ($phone !== '') 
            ? ['name' => $name, 'phone' => $phone] 
            : ['name' => $name, 'address' => $address];

        $muzakki = self::withTrashed()->updateOrCreate($criteria, [
            'address' => $address,
            'phone'   => $phone
        ]);
        
        if ($muzakki->trashed()) {
            $muzakki->restore();
        }

        return $muzakki;
    }

    /**
     * Get the transactions associated with the Muzakki.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(ZakatTransaction::class, 'muzakki_id');
    }
}
