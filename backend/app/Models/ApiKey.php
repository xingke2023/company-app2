<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $fillable = ['user_id', 'name', 'key', 'last_used_at'];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generate(User $user, string $name): self
    {
        return self::create([
            'user_id' => $user->id,
            'name'    => $name,
            'key'     => 'ak_' . Str::random(48),
        ]);
    }
}
