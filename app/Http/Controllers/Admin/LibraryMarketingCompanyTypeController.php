<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingCompanyType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LibraryMarketingCompanyTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:feature.library.type_of_business');
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $companyTypes = MarketingCompanyType::query()
            ->withCount('profiles')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(100)
            ->withQueryString();

        return view('admin.marketing_company_types.index', [
            'companyTypes' => $companyTypes,
            'q' => $search,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        MarketingCompanyType::query()->create($this->validatedCompanyTypeData($request));

        return redirect()
            ->route('library.type-of-business.index', $this->indexQuery($request))
            ->with('success', 'Type of Business created successfully.');
    }

    public function update(Request $request, MarketingCompanyType $companyType): RedirectResponse
    {
        $companyType->update($this->validatedCompanyTypeData($request, $companyType));

        return redirect()
            ->route('library.type-of-business.index', $this->indexQuery($request))
            ->with('success', 'Type of Business updated successfully.');
    }

    public function destroy(Request $request, MarketingCompanyType $companyType): RedirectResponse
    {
        $profileCount = $companyType->profiles()->count();

        if ($profileCount > 0) {
            return redirect()
                ->route('library.type-of-business.index', $this->indexQuery($request))
                ->with('error', "Cannot delete type of business: {$profileCount} company profile(s) are linked to it.");
        }

        $companyType->delete();

        return redirect()
            ->route('library.type-of-business.index', $this->indexQuery($request))
            ->with('success', 'Type of Business deleted successfully.');
    }

    /**
     * @return array{name:string, sort_order:int}
     */
    private function validatedCompanyTypeData(Request $request, ?MarketingCompanyType $companyType = null): array
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('marketing_company_types', 'name')->ignore($companyType?->getKey()),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        return [
            'name' => trim((string) $data['name']),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function indexQuery(Request $request): array
    {
        return collect([
            'q' => $request->input('index_q', $request->query('q')),
        ])
            ->map(fn ($value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '')
            ->all();
    }
}
