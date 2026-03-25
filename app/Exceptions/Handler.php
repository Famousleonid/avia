<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{

    protected $dontReport = [
        //
    ];


    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];


    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        // MySQL 1265: Data truncated — часто при вводе не-целого номера WO в INTEGER колонку workorders.number
        if ($e instanceof QueryException) {
            $msg = $e->getMessage();
            $code = (int) ($e->errorInfo[1] ?? 0);
            if (
                $code === 1265
                && str_contains($msg, 'workorders')
                && str_contains($msg, 'number')
            ) {
                $friendly = __('The workorder number must be a whole number (digits only, no dashes or letters).');
                if ($request->expectsJson()) {
                    return response()->json(['message' => $friendly], 422);
                }

                return redirect()->back()->withInput()->with('error', $friendly);
            }
        }

        // 403 из policy/gate
        if ($e instanceof AuthorizationException) {
            // Если запрос ожидает JSON (AJAX, fetch, axios, DataTables и т.п.)
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You do not have permission to perform this action.',
                ], 403);
            }

            // Обычный HTML → твоя страница errors/403.blade.php
            return response()->view('errors.403-popup', [], 403);
        }

        return parent::render($request, $e);
    }

}
