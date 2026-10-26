<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One AI request made through the gateway, with its token counts and cost.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $token_id
 * @property string $kind
 * @property string|null $turn_id
 * @property string|null $model
 * @property string|null $response_id
 * @property int $input_tokens
 * @property int $output_tokens
 * @property int $searches
 * @property float $voice_minutes
 * @property float $cost_usd
 */
final class UsageEvent extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    /** @return BelongsTo<User, covariant $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return ['voice_minutes' => 'float', 'cost_usd' => 'float', 'created_at' => 'datetime'];
    }
}
