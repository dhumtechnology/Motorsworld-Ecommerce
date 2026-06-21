<?php

namespace App\Models\Auth\Concerns;

use App\Enums\Auth\TokenType;
use App\Models\Auth\Token;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

trait HasApiTokens
{
    /**
     * @return HasMany<Token, $this>
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class);
    }

    public function createToken(
        TokenType $type,
        ?DateTimeInterface $expiresAt = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): string {
        $plainTextToken = Str::random(40);

        $this->tokens()->create([
            'type' => $type,
            'token_hash' => hash('sha256', $plainTextToken),
            'expires_at' => $expiresAt ?? now()->addHour(),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        return $plainTextToken;
    }

    public function currentAccessToken(): ?Token
    {
        return $this->accessToken ?? null;
    }

    public function withAccessToken(Token $accessToken): static
    {
        $this->accessToken = $accessToken;

        return $this;
    }
}
