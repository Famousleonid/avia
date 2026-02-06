<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class DirectoryController extends Controller
{
    private array $map = [
        'builders' => [
            'title' => 'Builder',
            'model' => \App\Models\Builder::class,
            'fields' => ['name' => 'Name'],
        ],
        'codes' => [
            'title' => 'Codes',
            'model' => \App\Models\Code::class,
            'fields' => ['name' => 'Name', 'code' => 'Code'],
        ],
        'instructions' => [
            'title' => 'Instruction',
            'model' => \App\Models\Instruction::class,
            'fields' => ['name' => 'Name'],
        ],
        'necessaries' => [
            'title' => 'Necessaries',
            'model' => \App\Models\Necessary::class,
            'fields' => ['name' => 'Name'],
        ],
        'planes' => [
            'title' => 'Planes',
            'model' => \App\Models\Plane::class,
            'fields' => ['type' => 'Type'],
        ],
        'process_names' => [
            'title' => 'Process names',
            'model' => \App\Models\ProcessName::class,
            'fields' => ['name' => 'Name'],
        ],
        'roles' => [
            'title' => 'Roles',
            'model' => \App\Models\Role::class, // Spatie
            'fields' => ['name' => 'Name'],
        ],
        'scopes' => [
            'title' => 'Scopes',
            'model' => \App\Models\Scope::class,
            'fields' => ['scope' => 'Scope'],
        ],
        'teams' => [
            'title' => 'Teams',
            'model' => \App\Models\Team::class,
            'fields' => ['name' => 'Name'],
        ],
        'vendors' => [
            'title' => 'Vendors',
            'model' => \App\Models\Vendor::class,
            'fields' => ['name' => 'Name'],
        ],
    ];

    private function cfg(Request $request): array
    {
        $key = $request->route()->defaults['dict'] ?? null;
        $cfg = config("directories.$key");

        abort_unless($cfg, 404);

        return [
            'key'        => $key,
            'title'      => $cfg['title'],
            'model'      => $cfg['model'],
            'fields'     => $cfg['fields'],
            'baseUrl'    => url()->current(),
            'firstField' => array_key_first($cfg['fields']),
        ];
    }

    public function index(Request $request)
    {
        $cfg = $this->cfg($request);
        $Model = $cfg['model'];

        return view('admin.directory.index', [
            'cfg'   => $cfg,
            'items' => $Model::query()
                ->orderBy($cfg['firstField'])
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $cfg = $this->cfg($request);
        $Model = $cfg['model'];

        $rules = $this->rules($cfg);
        $data = $request->validate($rules);

        $Model::create($data);

        return back()->with('success', $cfg['title'].' created');
    }

    public function update(Request $request, $id)
    {
        $cfg = $this->cfg($request);
        $Model = $cfg['model'];

        $item = $Model::findOrFail($id);

        $rules = $this->rules($cfg, $id);
        $data = $request->validate($rules);

        $item->update($data);

        return back()->with('success', $cfg['title'].' updated');
    }

    public function destroy(Request $request, $id)
    {
        $cfg = $this->cfg($request);
        $Model = $cfg['model'];

        $Model::findOrFail($id)->delete();

        return back()->with('success', $cfg['title'].' deleted');
    }

    private function rules(array $cfg, ?int $ignoreId = null): array
    {
        $Model = $cfg['model'];
        $table = (new $Model)->getTable();

        $rules = [];
        foreach ($cfg['fields'] as $field => $label) {
            $r = ['required', 'string', 'max:255'];

            // unique для каждого поля
            $unique = Rule::unique($table, $field);
            if ($ignoreId) $unique = $unique->ignore($ignoreId);

            $r[] = $unique;

            $rules[$field] = $r;
        }

        return $rules;
    }
}
