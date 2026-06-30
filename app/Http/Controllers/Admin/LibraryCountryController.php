<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\CustomerMarketingProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LibraryCountryController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            abort_unless(auth()->check() && auth()->user()->roleIs('Admin'), 403);

            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $countries = Country::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('alpha2', 'like', '%' . $search . '%');
                });
            })
            ->withCount('marketingProfiles')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(100)
            ->withQueryString();

        return view('admin.countries.index', [
            'countries' => $countries,
            'q' => $search,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Country::query()->create($this->validatedCountryData($request));

        return redirect()
            ->route('library.countries.index', $this->indexQuery($request))
            ->with('success', 'Country created successfully.');
    }

    public function update(Request $request, Country $country): RedirectResponse
    {
        $country->update($this->validatedCountryData($request, $country));

        return redirect()
            ->route('library.countries.index', $this->indexQuery($request))
            ->with('success', 'Country updated successfully.');
    }

    public function destroy(Request $request, Country $country): RedirectResponse
    {
        $profileCount = CustomerMarketingProfile::query()
            ->where('country_id', $country->id)
            ->count();

        if ($profileCount > 0) {
            return redirect()
                ->route('library.countries.index', $this->indexQuery($request))
                ->with('error', "Cannot delete country: {$profileCount} company profile(s) are linked to it.");
        }

        $country->delete();

        return redirect()
            ->route('library.countries.index', $this->indexQuery($request))
            ->with('success', 'Country deleted successfully.');
    }

    private function validatedCountryData(Request $request, ?Country $country = null): array
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('countries', 'name')->ignore($country?->getKey()),
            ],
            'alpha2' => [
                'required',
                'string',
                'size:2',
                Rule::unique('countries', 'alpha2')->ignore($country?->getKey()),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'active' => ['nullable', 'boolean'],
        ]);

        return [
            'name' => trim((string) $data['name']),
            'alpha2' => strtoupper(trim((string) $data['alpha2'])),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'active' => $request->boolean('active'),
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
