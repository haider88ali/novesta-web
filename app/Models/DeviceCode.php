<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A pending desktop-app login (device authorization flow).
 *
 * @property int $id
 * @property string $device_code_hash
 * @property string $user_code
 * @property string $device_name
 * @property string $platform
 * @property int|null $user_id
 * @property \Carbon\CarbonImmutable|null $approved_at
 * @property \Carbon\CarbonImmutable|null $consumed_at
 * @property \Carbon\CarbonImmutable $expires_at
 */
final class DeviceCode extends Model
{
    protected $guarded = ['id'];

    /** @return BelongsTo<User, covariant $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    protected function casts(): array
    {
        return ['approved_at' => 'datetime', 'consumed_at' => 'datetime', 'expires_at' => 'datetime'];
    }
}
