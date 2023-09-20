<?php

namespace DanHarrin\LivewireRateLimiting;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Illuminate\Support\Facades\RateLimiter;

trait WithRateLimiting
{
    protected function clearRateLimiter($method = null)
    {
        $method ??= debug_backtrace(limit: 2)[1]['function'];

        $key = $this->getRateLimitKey($method);

        RateLimiter::clear($key);
    }

    protected function getRateLimitKey($method)
    {
        $method ??= debug_backtrace(limit: 2)[1]['function'];

        return sha1(static::class.'|'.$method.'|'.request()->ip());
    }

    protected function hitRateLimiter($method = null, $decaySeconds = 60)
    {
        $method ??= debug_backtrace(limit: 2)[1]['function'];

        $key = $this->getRateLimitKey($method);

        RateLimiter::hit($key, $decaySeconds);
    }

    protected function rateLimit($maxAttempts, $decaySeconds = 60, $method = null)
    {
        $method ??= debug_backtrace(limit: 2)[1]['function'];

        $key = $this->getRateLimitKey($method);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $component = static::class;
            $ip = request()->ip();
            $secondsUntilAvailable = RateLimiter::availableIn($key);

            throw new TooManyRequestsException($component, $method, $ip, $secondsUntilAvailable);
        }

        $this->hitRateLimiter($method, $decaySeconds);
    }
}
