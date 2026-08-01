<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'username', 'email', 'google_id', 'password', 'location', 'bio', 'avatar_url', 'badge'])]
#[Hidden(['password', 'remember_token', 'google_id'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Builds the public @handle from a display name.
     *
     * The name is lower-cased and everything that is not a letter or a digit is
     * dropped, so "Андрій Мороз" becomes "андріймороз" — one word, as the UI
     * showed before the handle became a real stored column. Cyrillic is kept
     * rather than transliterated so the handle still reads as the person's name.
     *
     * On a collision 1–3 random digits are appended, the width growing with each
     * failed attempt so a popular name cannot spin here forever.
     */
    public static function generateUsername(string $name): string
    {
        $base = preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower($name, 'UTF-8'));
        $base = mb_substr($base, 0, 30);

        if ($base === '') {
            $base = 'fisher';
        }

        if (! static::where('username', $base)->exists()) {
            return $base;
        }

        for ($attempt = 0; $attempt < 30; $attempt++) {
            $digits = intdiv($attempt, 10) + 1; // 1 → 2 → 3
            $suffix = str_pad((string) random_int(0, 10 ** $digits - 1), $digits, '0', STR_PAD_LEFT);

            if (! static::where('username', $base.$suffix)->exists()) {
                return $base.$suffix;
            }
        }

        // 30 collisions in a row means the digit pool is exhausted for this name
        return $base.'-'.uniqid();
    }

    public function catchRecords(): HasMany
    {
        return $this->hasMany(CatchRecord::class);
    }

    public function permits(): HasMany
    {
        return $this->hasMany(Permit::class);
    }

    public function followers(): HasMany
    {
        return $this->hasMany(Follow::class, 'following_id');
    }

    public function following(): HasMany
    {
        return $this->hasMany(Follow::class, 'follower_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(function ($value) {
            if (! $value || str_starts_with($value, 'http')) {
                return $value;
            }

            return asset('storage/'.$value);
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_blocked' => 'boolean',
        ];
    }
}
