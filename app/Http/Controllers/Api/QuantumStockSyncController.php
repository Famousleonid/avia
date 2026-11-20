<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QuantumStock;
use Illuminate\Http\Request;

class QuantumStockSyncController extends Controller
{
    public function store(Request $request, QuantumStock $stock)
    {
        $token = (string) config('services.quantum_sync.token');
        abort_unless($token !== '' && hash_equals($token, (string) $request->bearerToken()), 401);
        $stock->import($request->all());
        return response()->json(['success' => true, 'rows' => $request->input('row_count')]);
    }
}
