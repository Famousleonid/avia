<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class DirectoryController extends Controller
{
    // ---------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------

    private function slug(Request $request): string
    {
        // /admin/{resource}
        return (string)$request->segment(2);
    }

    private function dir(string $slug): array
    {
        $dir = config("directories.$slug");
        abort_if(!$dir, 404, "Directory [$slug] not configured");

        $model = $dir['model'] ?? null;
        abort_if(!$model || !class_exists($model), 500, "Model not found for [$slug]");

        $fields = $dir['fields'] ?? null;
        abort_if(!is_array($fields) || empty($fields), 500, "Fields not configured for [$slug]");

        return $dir;
    }

    private function normalizedFields(array $dir): array
    {
        $out = [];

        foreach (($dir['fields'] ?? []) as $field => $meta) {
            if (is_array($meta)) {
                $out[$field] = [
                    'label'          => $meta['label'] ?? ucfirst($field),
                    'rules'          => $meta['rules'] ?? ['nullable'],
                    'type'           => $meta['type'] ?? 'text',
                    'options'        => $meta['options'] ?? [],
                    'options_source' => $meta['options_source'] ?? null,
                    'placeholder'    => $meta['placeholder'] ?? null,
                ];
            } else {
                $out[$field] = [
                    'label'          => (string)$meta,
                    'rules'          => ['nullable'],
                    'type'           => 'text',
                    'options'        => [],
                    'options_source' => null,
                    'placeholder'    => null,
                ];
            }
        }

        return $out;
    }

    private function fieldKeys(array $normalizedFields): array
    {
        return array_keys($normalizedFields);
    }

    /**
     * Строим правила из конфига.
     */
    private function rulesFor(Request $request, array $dir, array $normalizedFields, ?int $ignoreId = null): array
    {
        $rules = [];

        foreach ($normalizedFields as $field => $meta) {
            $fieldRules = $meta['rules'] ?? ['nullable'];

            if ($ignoreId) {
                $fieldRules = $this->injectUniqueIgnoreIfNeeded($fieldRules, $dir, $field, $ignoreId);
            }

            $rules[$field] = $fieldRules;
        }

        return $rules;
    }

    private function injectUniqueIgnoreIfNeeded(array $fieldRules, array $dir, string $field, int $ignoreId): array
    {
        $out = [];

        foreach ($fieldRules as $r) {
            if ($r instanceof \Illuminate\Validation\Rules\Unique) {
                $out[] = $r->ignore($ignoreId);
                continue;
            }

            if (is_string($r) && str_starts_with($r, 'unique:')) {
                $parts = explode(':', $r, 2);
                $params = $parts[1] ?? '';
                [$table, $column] = array_pad(explode(',', $params, 2), 2, null);

                $table = $table ?: null;
                $column = $column ?: $field;

                if ($table) {
                    $out[] = Rule::unique($table, $column)->ignore($ignoreId);
                    continue;
                }
            }

            $out[] = $r;
        }

        return $out;
    }

    private function cfgForBlade(string $slug, array $dir, array $normalizedFields): array
    {
        $fields = [];
        $fieldsMeta = [];

        foreach ($normalizedFields as $field => $meta) {
            $fields[$field] = $meta['label'] ?? ucfirst($field);

            $prepared = $meta;

            if (($meta['type'] ?? 'text') === 'select') {
                $prepared['options'] = $this->resolveFieldOptions($meta);
            }

            $fieldsMeta[$field] = $prepared;
        }

        return [
            'key'        => $slug,
            'title'      => $dir['title'] ?? ucfirst($slug),
            'baseUrl'    => url("/admin/{$slug}"),
            'toggleUrl' => url("/admin/{$slug}/toggle"),
            'firstField' => array_key_first($fields) ?: 'name',
            'fields'     => $fields,
            'fieldsMeta' => $fieldsMeta,
        ];
    }

    private function applySearch(Request $request, $query, array $dir, array $fieldKeys): array
    {
        $search = trim((string)$request->get('q', ''));

        if ($search === '') {
            return [$query, ''];
        }

        $cols = $dir['search'] ?? $fieldKeys;

        $query->where(function ($q) use ($cols, $search) {
            foreach ((array)$cols as $col) {
                $q->orWhere($col, 'like', "%{$search}%");
            }
        });

        return [$query, $search];
    }

    private function applyOrder($query, array $dir)
    {
        $order = $dir['order'] ?? ['id' => 'desc'];

        foreach ((array)$order as $col => $direction) {
            $query->orderBy($col, $direction);
        }

        return $query;
    }

    /**
     * Нормализуем boolean / checkbox поля перед create / update.
     */
    private function normalizeDataForSave(Request $request, array $normalizedFields, array $data): array
    {
        foreach ($normalizedFields as $field => $meta) {
            $type = $meta['type'] ?? 'text';

            if (in_array($type, ['boolean', 'checkbox'], true)) {
                $data[$field] = $request->boolean($field);
            }
        }

        return $data;
    }

    public function index(Request $request)
    {
        $slug = $this->slug($request);
        $dir = $this->dir($slug);

        $normalizedFields = $this->normalizedFields($dir);
        $fieldKeys = $this->fieldKeys($normalizedFields);

        $modelClass = $dir['model'];
        $query = $modelClass::query();

        [$query, $search] = $this->applySearch($request, $query, $dir, $fieldKeys);
        $query = $this->applyOrder($query, $dir);

        $items = $query->paginate(50)->withQueryString();

        $cfg = $this->cfgForBlade($slug, $dir, $normalizedFields);

        return view('admin.directories.index', [
            'slug'  => $slug,
            'cfg'   => $cfg,
            'items' => $items,
            'q'     => $search,
        ]);
    }

    public function store(Request $request)
    {
        $slug = $this->slug($request);
        $dir = $this->dir($slug);

        $normalizedFields = $this->normalizedFields($dir);
        $fieldKeys = $this->fieldKeys($normalizedFields);

        $rules = $this->rulesFor($request, $dir, $normalizedFields);

        $validated = $request->validate($rules);
        $data = Arr::only($validated, $fieldKeys);

        // ВОТ ЗДЕСЬ: до create()
        $data = $this->normalizeDataForSave($request, $normalizedFields, $data);

        $modelClass = $dir['model'];
        $item = $modelClass::create($data);

        if ($request->expectsJson()) {
            return response()->json(
                ['id' => $item->id] + $item->only($fieldKeys)
            );
        }

        return redirect()->route("$slug.index")->with('success', 'Created');
    }

    public function update(Request $request, $id)
    {
        $slug = $this->slug($request);
        $dir = $this->dir($slug);

        $normalizedFields = $this->normalizedFields($dir);
        $fieldKeys = $this->fieldKeys($normalizedFields);

        $modelClass = $dir['model'];
        $item = $modelClass::findOrFail($id);

        $rules = $this->rulesFor($request, $dir, $normalizedFields, (int)$item->id);

        $validated = $request->validate($rules);
        $data = Arr::only($validated, $fieldKeys);
        $data = $this->normalizeDataForSave($request, $normalizedFields, $data);

        $item->fill($data)->save();

        return redirect()->route("$slug.index")->with('success', 'Updated');
    }

    public function destroy(Request $request, $id)
    {
        $slug = $this->slug($request);
        $dir = $this->dir($slug);

        $modelClass = $dir['model'];
        $item = $modelClass::findOrFail($id);

        $item->delete();

        return redirect()->route("$slug.index")->with('success', 'Deleted');
    }

    /**
     * Получить options для одного select-поля.
     */
    protected function resolveFieldOptions(array $meta): array
    {
        if (($meta['type'] ?? null) !== 'select') {
            return [];
        }

        $source = $meta['options_source'] ?? null;

        return match ($source) {
            'users' => \App\Models\User::query()
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray(),

            'teams' => \App\Models\Team::query()
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray(),

            'roles' => \App\Models\Role::query()
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray(),

            default => is_array($meta['options'] ?? null)
                ? $meta['options']
                : [],
        };
    }

    public function toggle(string $directory, int $id, string $field)
    {
        $dir = $this->dir($directory);
        $normalizedFields = $this->normalizedFields($dir);

        $meta = $normalizedFields[$field] ?? null;
        abort_unless($meta, 404);

        $type = $meta['type'] ?? 'text';
        abort_unless(in_array($type, ['boolean', 'checkbox'], true), 422);

        $modelClass = $dir['model'];
        $item = $modelClass::findOrFail($id);

        $item->{$field} = !$item->{$field};
        $item->save();

        return response()->json([
            'ok'      => true,
            'id'      => $item->id,
            'field'   => $field,
            'value'   => (bool)$item->{$field},
            'display' => $item->{$field} ? 'Yes' : 'No',
        ]);
    }
}
