<?php

namespace App\Support;

final class Tenancy
{
    public static function enabled(): bool
    {
        return (bool) config('examup.tenancy.enabled', false);
    }
}
