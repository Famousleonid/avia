<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\QuantumStock;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class StockController extends Controller
{
    public function index(Request $request, QuantumStock $stock)
    {
        $filters = $request->validate(['q'=>'nullable|string|max:255','nonzero'=>'sometimes|boolean','pn' => 'nullable|string|max:255', 'description' => 'nullable|string|max:255', 'page' => 'nullable|integer|min:1']);
        $pn = trim($filters['pn'] ?? ''); $description = trim($filters['description'] ?? '');
        $query=trim($filters['q']??($pn?:$description)); $nonzero=$request->boolean('nonzero');
        $snapshot = $stock->search('', '', $query, $nonzero);
        $page = (int) ($filters['page'] ?? 1);
        $rows = new LengthAwarePaginator(array_slice($snapshot['rows'], ($page - 1) * 100, 100), count($snapshot['rows']), 100, $page,
            ['path' => $request->url(), 'query' => $request->query()]);
        if ($request->expectsJson()) return response()->json([
            'html'=>view('admin.stock.rows',compact('rows','snapshot'))->render(),
            'total'=>$snapshot['row_count'],'filtered'=>$rows->total(),
            'next'=>$rows->hasMorePages()?$page+1:null,'snapshot'=>$snapshot['exported_at'],
            'updated'=>$snapshot['exported_at'] ? 'Updated '.\Carbon\CarbonImmutable::parse($snapshot['exported_at'])->timezone('America/Toronto')->format('d/M/Y H:i').' (Toronto)' : 'No Quantum snapshot received',
        ])->header('Cache-Control','no-store');
        return view('admin.stock.index', compact('rows', 'snapshot', 'query', 'nonzero'));
    }
}
