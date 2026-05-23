<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserUiSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserUiSettingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scope' => ['required', 'string', 'max:120'],
        ]);

        $settings = UserUiSetting::query()
            ->where('user_id', $request->user()->id)
            ->where('scope', $data['scope'])
            ->get()
            ->mapWithKeys(fn (UserUiSetting $setting): array => [
                $setting->key => $setting->value,
            ]);

        return response()->json([
            'settings' => $settings,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scope' => ['required', 'string', 'max:120'],
            'key' => ['required', 'string', 'max:120'],
            'value' => ['present'],
        ]);

        if ($data['value'] === null) {
            UserUiSetting::query()
                ->where('user_id', $request->user()->id)
                ->where('scope', $data['scope'])
                ->where('key', $data['key'])
                ->delete();

            return response()->json([
                'ok' => true,
                'setting' => [
                    'scope' => $data['scope'],
                    'key' => $data['key'],
                    'value' => null,
                ],
            ]);
        }

        $setting = UserUiSetting::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'scope' => $data['scope'],
                'key' => $data['key'],
            ],
            [
                'value' => $data['value'],
            ]
        );

        return response()->json([
            'ok' => true,
            'setting' => [
                'scope' => $setting->scope,
                'key' => $setting->key,
                'value' => $setting->value,
            ],
        ]);
    }
}
