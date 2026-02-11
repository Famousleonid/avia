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
        // примеры: /admin/teams, /admin/roles, /admin/vendors
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
        // Приводим к единому формату:
        // 'name' => ['label'=>'Name','rules'=>[...] ]
        // 'code' => 'Code'  -> станет ['label'=>'Code','rules'=>['nullable']]
        $out = [];

        foreach (($dir['fields'] ?? []) as $field => $meta) {
            if (is_array($meta)) {
                $out[$field] = [
                    'label' => $meta['label'] ?? ucfirst($field),
                    'rules' => $meta['rules'] ?? ['nullable'],
                ];
            } else {
                $out[$field] = [
                    'label' => (string)$meta,
                    'rules' => ['nullable'],
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
     * Дополнительно поддержим удобный хак:
     * если в rules есть строка 'unique', то на update автоматически игнорим текущий id
     * (работает только для валидатора Laravel unique:table,column).
     */
    private function rulesFor(Request $request, array $dir, array $normalizedFields, ?int $ignoreId = null): array
    {
        $rules = [];

        foreach ($normalizedFields as $field => $meta) {
            $fieldRules = $meta['rules'] ?? ['nullable'];

            // если rule указан как строка 'unique:table,column' — на update добавим ignore($id)
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
            // Пропускаем готовые Rule::unique(...)
            if ($r instanceof \Illuminate\Validation\Rules\Unique) {
                $out[] = $r->ignore($ignoreId);
                continue;
            }

            // Строковый unique:table,column — превратим в Rule::unique()->ignore()
            if (is_string($r) && str_starts_with($r, 'unique:')) {
                // unique:table,column
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
        // твой blade ожидает fields как map: field => label
        $fields = [];
        foreach ($normalizedFields as $field => $meta) {
            $fields[$field] = $meta['label'] ?? ucfirst($field);
        }

        return [
            'title'      => $dir['title'] ?? ucfirst($slug),
            'baseUrl'    => url("/admin/{$slug}"),
            'firstField' => array_key_first($fields) ?: 'name',
            'fields'     => $fields,
        ];
    }

    private function applySearch(Request $request, $query, array $dir, array $fieldKeys): array
    {
        $search = trim((string)$request->get('q', ''));
        if ($search === '') return [$query, ''];

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

    public function index(Request $request)
    {
        $slug = $this->slug($request);
        $dir  = $this->dir($slug);

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
        $dir  = $this->dir($slug);

        $normalizedFields = $this->normalizedFields($dir);
        $fieldKeys = $this->fieldKeys($normalizedFields);

        $rules = $this->rulesFor($request, $dir, $normalizedFields);

        $validated = $request->validate($rules);
        $data = Arr::only($validated, $fieldKeys);

        $modelClass = $dir['model'];
        $item = $modelClass::create($data);

        return redirect()->route("$slug.index")->with('success', 'Created');
    }

    public function update(Request $request, $id)
    {
        $slug = $this->slug($request);
        $dir  = $this->dir($slug);

        $normalizedFields = $this->normalizedFields($dir);
        $fieldKeys = $this->fieldKeys($normalizedFields);

        $modelClass = $dir['model'];
        $item = $modelClass::findOrFail($id);

        $rules = $this->rulesFor($request, $dir, $normalizedFields, (int)$item->id);

        $validated = $request->validate($rules);
        $data = Arr::only($validated, $fieldKeys);

        $old = Arr::only($item->getAttributes(), $fieldKeys);

        $item->fill($data)->save();

        return redirect()->route("$slug.index")->with('success', 'Updated');
    }

    public function destroy(Request $request, $id)
    {
        $slug = $this->slug($request);
        $dir  = $this->dir($slug);

        $modelClass = $dir['model'];
        $item = $modelClass::findOrFail($id);

        $item->delete();

        return redirect()->route("$slug.index")->with('success', 'Deleted');
    }
}
