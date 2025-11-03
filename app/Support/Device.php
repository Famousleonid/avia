<?php

namespace App\Support;

use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class Device
{
    public static function isMobile(Request $request): bool
    {

        $c = $request->cookie('viewport_mobile');
        if ($c === '1') return true;
        if ($c === '0') return false;

        $agent = new Agent();
        return $agent->isMobile() || $agent->isTablet();
    }
}
