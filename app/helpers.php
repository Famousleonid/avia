<?php

use Illuminate\Support\Facades\Auth;

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return Auth::check() && Auth::user()->is_admin;
    }
}
