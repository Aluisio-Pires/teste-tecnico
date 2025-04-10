<?php

namespace App\Traits;

trait HasJWT
{
    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array<mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
