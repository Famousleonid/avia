<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QuantumUnitHistory;
use Illuminate\Http\Request;

class QuantumUnitHistorySyncController extends Controller
{
    public function store(Request $request, QuantumUnitHistory $history)
    {
        $token = (string) config('services.quantum_sync.token');
        abort_unless($token !== '' && hash_equals($token, (string) $request->bearerToken()), 401);
        $history->import($request->all());
        return response()->json(['success' => true, 'rows' => $request->input('row_count')]);
    }
}
