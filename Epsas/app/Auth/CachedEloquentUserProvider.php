<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Support\Facades\Cache;

class CachedEloquentUserProvider extends EloquentUserProvider
{
    public function retrieveById($identifier): ?UserContract
    {
        if (empty($identifier)) {
            return null;
        }

        return Cache::remember(
            $this->cacheKey($identifier),
            now()->addMinutes((int) config('auth.user_cache_minutes', 10)),
            function () use ($identifier) {
                $model = $this->createModel();

                return $model->newQuery()
                    ->with('persona')
                    ->where($model->getAuthIdentifierName(), $identifier)
                    ->first();
            }
        );
    }

    public function retrieveByToken($identifier, $token): ?UserContract
    {
        $user = $this->retrieveById($identifier);

        if (!$user) {
            return null;
        }

        $rememberToken = $user->getRememberToken();

        return $rememberToken && hash_equals($rememberToken, $token) ? $user : null;
    }

    public function updateRememberToken(UserContract $user, $token): void
    {
        parent::updateRememberToken($user, $token);

        Cache::forget($this->cacheKey($user->getAuthIdentifier()));
    }

    private function cacheKey(mixed $identifier): string
    {
        return 'auth:user:' . str_replace('\\', '.', $this->model) . ':' . $identifier;
    }
}
