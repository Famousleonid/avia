<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerAircraft;
use App\Models\CustomerContact;
use App\Models\CustomerInteractionNote;
use App\Models\CustomerMarketingProfile;
use App\Models\MarketingCompanyType;
use App\Models\MarketingSegment;
use App\Models\Plane;
use App\Models\User;
use App\Models\Workorder;
use App\Services\MarketingWoEstimateNotificationService;
use App\Services\SalesReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MarketingController extends Controller
{
    private const ADDRESS_CATEGORY_LABELS = [
        'logistics' => 'Logistics',
        'shipping' => 'Shipping',
        'marketing' => 'Marketing',
        'accounting' => 'Accounting',
        'purchasing' => 'Purchasing',
    ];

    public function index(): View
    {
        return view('admin.marketing.index', [
            'companyTypes' => MarketingCompanyType::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'segments' => MarketingSegment::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'countries' => Country::query()->where('active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'alpha2', 'name']),
            'planes' => Plane::query()->orderBy('type')->get(['id', 'type']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'lifecycleOptions' => $this->lifecycleOptions(),
            'addressCategoryLabels' => $this->addressCategoryLabels(),
        ]);
    }

    public function customers(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'company_type_id' => ['nullable', 'integer', 'exists:marketing_company_types,id'],
            'segment_id' => ['nullable', 'integer', 'exists:marketing_segments,id'],
            'plane_id' => ['nullable', 'integer', 'exists:planes,id'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'country' => ['nullable', 'string', 'max:120'],
            'lifecycle_status' => ['nullable', Rule::in(array_keys($this->lifecycleOptions()))],
            'follow_up' => ['nullable', Rule::in(['due', 'upcoming', 'none'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:75'],
        ]);

        $q = trim((string) ($filters['q'] ?? ''));
        $perPage = (int) ($filters['per_page'] ?? 40);
        $today = now()->toDateString();

        $query = Customer::query()
            ->with([
                'marketingProfile.companyType',
                'marketingProfile.segment',
                'marketingProfile.countryRef',
                'marketingAircraft.plane',
                'marketingContacts',
            ])
            ->withCount(['workorders', 'marketingContacts', 'marketingNotes'])
            ->orderBy('name')
            ->orderBy('id');

        if ($q !== '') {
            $like = '%' . $this->escapeLike($q) . '%';

            $query->where(function ($inner) use ($like) {
                $inner->where('name', 'like', $like)
                    ->orWhereHas('marketingProfile', function ($profile) use ($like) {
                        $profile->where('country', 'like', $like)
                            ->orWhere('address', 'like', $like)
                            ->orWhere('street_address', 'like', $like)
                            ->orWhere('city', 'like', $like)
                            ->orWhere('state_province', 'like', $like)
                            ->orWhere('post_code', 'like', $like)
                            ->orWhere('company_notes', 'like', $like)
                            ->orWhereRaw('CAST(address_categories AS CHAR) LIKE ?', [$like])
                            ->orWhere('terms_label', 'like', $like);
                    })
                    ->orWhereHas('marketingProfile.countryRef', function ($country) use ($like) {
                        $country->where('name', 'like', $like)
                            ->orWhere('alpha2', 'like', $like);
                    })
                    ->orWhereHas('marketingContacts', function ($contact) use ($like) {
                        $contact->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('position', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('phone', 'like', $like);
                    })
                    ->orWhereHas('marketingAircraft.plane', function ($plane) use ($like) {
                        $plane->where('type', 'like', $like);
                    });
            });
        }

        if (!empty($filters['company_type_id'])) {
            $query->whereHas('marketingProfile', fn ($profile) => $profile->where('company_type_id', (int) $filters['company_type_id']));
        }

        if (!empty($filters['segment_id'])) {
            $query->whereHas('marketingProfile', fn ($profile) => $profile->where('segment_id', (int) $filters['segment_id']));
        }

        if (!empty($filters['plane_id'])) {
            $query->whereHas('marketingAircraft', fn ($aircraft) => $aircraft->where('plane_id', (int) $filters['plane_id']));
        }

        if (!empty($filters['country_id'])) {
            $selectedCountry = Country::query()->find((int) $filters['country_id']);
            $legacyCountryNames = $selectedCountry ? $this->countryFilterAliases($selectedCountry) : [];

            $query->whereHas('marketingProfile', function ($profile) use ($filters, $legacyCountryNames): void {
                $profile->where('country_id', (int) $filters['country_id']);

                if ($legacyCountryNames !== []) {
                    $profile->orWhere(function ($legacy) use ($legacyCountryNames): void {
                        foreach ($legacyCountryNames as $legacyName) {
                            $legacy->orWhereRaw('LOWER(TRIM(country)) = ?', [$legacyName]);
                        }
                    });
                }
            });
        }

        if (!empty($filters['country'])) {
            $country = '%' . $this->escapeLike(trim((string) $filters['country'])) . '%';
            $query->whereHas('marketingProfile', function ($profile) use ($country): void {
                $profile->where('country', 'like', $country)
                    ->orWhereHas('countryRef', fn ($countryQuery) => $countryQuery->where('name', 'like', $country));
            });
        }

        if (!empty($filters['lifecycle_status'])) {
            $status = (string) $filters['lifecycle_status'];
            $query->where(function ($inner) use ($status) {
                $inner->whereHas('marketingProfile', fn ($profile) => $profile->where('lifecycle_status', $status));

                if ($status === CustomerMarketingProfile::STATUS_EXISTING) {
                    $inner->orWhereDoesntHave('marketingProfile');
                }
            });
        }

        if (!empty($filters['follow_up'])) {
            $query->whereHas('marketingProfile', function ($profile) use ($filters, $today) {
                if ($filters['follow_up'] === 'due') {
                    $profile->whereNotNull('next_follow_up_at')->whereDate('next_follow_up_at', '<=', $today);
                } elseif ($filters['follow_up'] === 'upcoming') {
                    $profile->whereNotNull('next_follow_up_at')->whereDate('next_follow_up_at', '>', $today);
                } elseif ($filters['follow_up'] === 'none') {
                    $profile->whereNull('next_follow_up_at');
                }
            });
        }

        $customers = $query->paginate($perPage);

        return response()->json([
            'items' => $customers->getCollection()->map(fn (Customer $customer) => $this->customerRow($customer))->values(),
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'next_page' => $customers->hasMorePages() ? $customers->currentPage() + 1 : null,
                'has_more' => $customers->hasMorePages(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    public function cities(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $query = CustomerMarketingProfile::query()
            ->whereNotNull('city')
            ->whereRaw("TRIM(city) <> ''");

        if (!empty($filters['country_id'])) {
            $selectedCountry = Country::query()->find((int) $filters['country_id']);
            $legacyCountryNames = $selectedCountry ? $this->countryFilterAliases($selectedCountry) : [];

            $query->where(function ($inner) use ($filters, $legacyCountryNames): void {
                $inner->where('country_id', (int) $filters['country_id']);

                if ($legacyCountryNames !== []) {
                    $inner->orWhere(function ($legacy) use ($legacyCountryNames): void {
                        foreach ($legacyCountryNames as $legacyName) {
                            $legacy->orWhereRaw('LOWER(TRIM(country)) = ?', [$legacyName]);
                        }
                    });
                }
            });
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $query->where('city', 'like', '%' . $this->escapeLike($q) . '%');
        }

        $cities = $query
            ->select('city')
            ->distinct()
            ->orderBy('city')
            ->limit(25)
            ->pluck('city')
            ->map(fn ($city): string => trim((string) $city))
            ->filter()
            ->unique()
            ->values()
            ->map(fn (string $city): array => [
                'id' => $city,
                'text' => $city,
            ]);

        return response()->json(['results' => $cities]);
    }

    public function storeCustomer(Request $request): JsonResponse
    {
        $data = $this->validateProfilePayload($request, true);

        $customer = DB::transaction(function () use ($data) {
            $customer = Customer::query()->create(['name' => $data['name']]);
            $this->saveProfile($customer, $data);
            $this->syncAircraft($customer, $data['aircraft_ids'] ?? []);

            return $customer;
        });

        $this->logMarketingActivity(
            'created',
            $customer->fresh(),
            'Marketing company created',
            [],
            $this->customerAuditSnapshot($customer->fresh())
        );

        return response()->json([
            'ok' => true,
            'customer' => $this->customerDetail($customer->fresh()),
        ], 201);
    }

    public function showCustomer(Customer $customer): JsonResponse
    {
        return response()->json([
            'customer' => $this->customerDetail($customer),
        ]);
    }

    public function updateProfile(Request $request, Customer $customer): JsonResponse
    {
        $data = $this->validateProfilePayload($request, false);
        $old = $this->customerAuditSnapshot($customer->fresh());

        DB::transaction(function () use ($customer, $data) {
            if (array_key_exists('name', $data)) {
                $customer->update(['name' => $data['name']]);
            }

            $this->saveProfile($customer, $data);

            if (array_key_exists('aircraft_ids', $data)) {
                $this->syncAircraft($customer, $data['aircraft_ids'] ?? []);
            }
        });

        $fresh = $customer->fresh();
        $new = $this->customerAuditSnapshot($fresh);
        $this->logMarketingActivity(
            'updated',
            $fresh,
            'Marketing company updated',
            $old,
            $new
        );

        return response()->json([
            'ok' => true,
            'customer' => $this->customerDetail($fresh),
        ]);
    }

    public function storeContact(Request $request, Customer $customer): JsonResponse
    {
        $data = $this->validateContactPayload($request);
        $data['customer_id'] = $customer->id;

        if (! $customer->marketingContacts()->exists()) {
            $data['is_primary'] = true;
        }

        $contact = DB::transaction(function () use ($customer, $data) {
            $contact = CustomerContact::query()->create($data);
            $this->syncPrimaryContact($customer, $contact);

            return $contact;
        });

        $this->logMarketingActivity(
            'created',
            $contact->fresh(['customer']),
            'Marketing contact created',
            [],
            $this->contactAuditSnapshot($contact->fresh(['customer']))
        );

        return response()->json([
            'ok' => true,
            'contact' => $this->contactRow($contact->fresh()),
            'customer' => $this->customerDetail($customer->fresh()),
        ], 201);
    }

    public function updateContact(Request $request, CustomerContact $contact): JsonResponse
    {
        $data = $this->validateContactPayload($request);
        $old = $this->contactAuditSnapshot($contact->fresh(['customer']));

        DB::transaction(function () use ($contact, $data) {
            $contact->update($data);
            $this->syncPrimaryContact($contact->customer, $contact);
        });

        $fresh = $contact->fresh(['customer']);
        $this->logMarketingActivity(
            'updated',
            $fresh,
            'Marketing contact updated',
            $old,
            $this->contactAuditSnapshot($fresh)
        );

        return response()->json([
            'ok' => true,
            'contact' => $this->contactRow($fresh),
            'customer' => $this->customerDetail($contact->customer->fresh()),
        ]);
    }

    public function destroyContact(CustomerContact $contact): JsonResponse
    {
        $customer = $contact->customer;
        $old = $this->contactAuditSnapshot($contact->fresh(['customer']));

        DB::transaction(function () use ($customer, $contact) {
            $wasPrimary = (bool) $contact->is_primary;
            $contact->delete();

            if ($wasPrimary) {
                $next = $customer->marketingContacts()->first();
                if ($next) {
                    $next->update(['is_primary' => true]);
                }
            }
        });

        $this->logMarketingActivity(
            'deleted',
            $customer->fresh(),
            'Marketing contact deleted',
            $old,
            []
        );

        return response()->json([
            'ok' => true,
            'customer' => $this->customerDetail($customer->fresh()),
        ]);
    }

    public function storeNote(Request $request, Customer $customer): JsonResponse
    {
        $data = $this->validateNotePayload($request, true);
        $this->assertContactBelongsToCustomer($data['contact_id'] ?? null, $customer);

        $data['customer_id'] = $customer->id;
        $data['user_id'] = $request->user()?->id;

        $note = DB::transaction(function () use ($customer, $data) {
            $note = CustomerInteractionNote::query()->create($data);
            $this->syncProfileDates($customer);

            return $note;
        });

        $this->logMarketingActivity(
            'created',
            $note->fresh(['customer', 'contact', 'user']),
            'Marketing note created',
            [],
            $this->noteAuditSnapshot($note->fresh(['customer', 'contact', 'user']))
        );

        return response()->json([
            'ok' => true,
            'note' => $this->noteRow($note->fresh(['contact', 'user'])),
            'customer' => $this->customerDetail($customer->fresh()),
        ], 201);
    }

    public function updateNote(Request $request, CustomerInteractionNote $note): JsonResponse
    {
        $data = $this->validateNotePayload($request, false);
        $customer = $note->customer;
        $this->assertContactBelongsToCustomer($data['contact_id'] ?? null, $customer);
        $old = $this->noteAuditSnapshot($note->fresh(['customer', 'contact', 'user']));

        DB::transaction(function () use ($customer, $note, $data) {
            $note->update($data);
            $this->syncProfileDates($customer);
        });

        $fresh = $note->fresh(['customer', 'contact', 'user']);
        $this->logMarketingActivity(
            'updated',
            $fresh,
            'Marketing note updated',
            $old,
            $this->noteAuditSnapshot($fresh)
        );

        return response()->json([
            'ok' => true,
            'note' => $this->noteRow($fresh),
            'customer' => $this->customerDetail($customer->fresh()),
        ]);
    }

    public function destroyNote(CustomerInteractionNote $note): JsonResponse
    {
        $customer = $note->customer;
        $old = $this->noteAuditSnapshot($note->fresh(['customer', 'contact', 'user']));

        DB::transaction(function () use ($customer, $note) {
            $note->delete();
            $this->syncProfileDates($customer);
        });

        $this->logMarketingActivity(
            'deleted',
            $customer->fresh(),
            'Marketing note deleted',
            $old,
            []
        );

        return response()->json([
            'ok' => true,
            'customer' => $this->customerDetail($customer->fresh()),
        ]);
    }

    public function customerWorkorders(Request $request, Customer $customer): JsonResponse
    {
        $data = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'wo_q' => ['nullable', 'string', 'max:120'],
            'wo_number' => ['nullable', 'string', 'max:60'],
            'wo_status' => ['nullable', 'string', 'max:60'],
            'wo_ro' => ['nullable', 'string', 'max:120'],
            'wo_part' => ['nullable', 'string', 'max:120'],
            'wo_description' => ['nullable', 'string', 'max:120'],
            'wo_serial' => ['nullable', 'string', 'max:120'],
            'wo_task' => ['nullable', 'string', 'max:120'],
            'wo_terms' => ['nullable', 'string', 'max:120'],
            'wo_estimate' => ['nullable', 'string', 'max:120'],
            'wo_estimate_date' => ['nullable', 'string', 'max:60'],
            'wo_approval_date' => ['nullable', 'string', 'max:60'],
            'wo_invoice' => ['nullable', 'string', 'max:60'],
            'wo_invoice_date' => ['nullable', 'string', 'max:60'],
            'wo_ship_date' => ['nullable', 'string', 'max:60'],
            'wo_awb' => ['nullable', 'string', 'max:120'],
        ]);

        $perPage = (int) ($data['per_page'] ?? 20);
        $profile = $this->ensureProfile($customer);

        $query = Workorder::query()
            ->where('customer_id', $customer->id)
            ->with(['unit.manual.plane', 'instruction', 'main.task', 'media'])
            ->orderByDesc('open_at')
            ->orderByDesc('number');

        $this->applyMarketingWorkorderFilters($query, $data, $profile);

        $workorders = $query->paginate($perPage);

        return response()->json([
            'items' => $workorders->getCollection()
                ->map(fn (Workorder $workorder) => $this->workorderRow($workorder, $profile))
                ->values(),
            'pagination' => [
                'current_page' => $workorders->currentPage(),
                'next_page' => $workorders->hasMorePages() ? $workorders->currentPage() + 1 : null,
                'has_more' => $workorders->hasMorePages(),
                'per_page' => $workorders->perPage(),
                'total' => $workorders->total(),
            ],
        ]);
    }

    public function updateWorkorderSalesFields(
        Request $request,
        Workorder $workorder,
        MarketingWoEstimateNotificationService $estimateNotifications
    ): JsonResponse
    {
        abort_if($workorder->is_draft, 404);

        $hasEstimateDateInput = $request->has('estimate_date');
        $oldEstimateDate = $workorder->wo_estimate_date?->toDateString();

        $data = $request->validate([
            'wo_terms' => ['nullable', 'string', 'max:120'],
            'wo_estimate_amount' => ['nullable', 'string', 'max:60'],
            'estimate_date' => ['nullable', 'string', 'max:32'],
            'sales_invoice_amount' => ['nullable', 'string', 'max:60'],
            'sales_invoice_date' => ['nullable', 'string', 'max:32'],
            'shipping_shipment_at' => ['nullable', 'string', 'max:32'],
            'shipping_awb_no' => ['nullable', 'string', 'max:255'],
        ]);

        $updates = [
            'wo_terms' => trim((string) ($data['wo_terms'] ?? '')) ?: null,
            'wo_estimate_amount' => $this->parseMoneyInput($data['wo_estimate_amount'] ?? null, 'wo_estimate_amount'),
            'sales_invoice_amount' => $this->parseMoneyInput($data['sales_invoice_amount'] ?? null, 'sales_invoice_amount'),
            'sales_invoice_date' => $this->parseDateInput($data['sales_invoice_date'] ?? null, 'sales_invoice_date'),
            'shipping_shipment_at' => $this->parseDateInput($data['shipping_shipment_at'] ?? null, 'shipping_shipment_at'),
            'shipping_awb_no' => trim((string) ($data['shipping_awb_no'] ?? '')) ?: null,
        ];

        if ($hasEstimateDateInput) {
            $updates['wo_estimate_date'] = $this->parseDateInput($data['estimate_date'] ?? null, 'estimate_date');
        }

        $workorder->forceFill($updates)->save();

        if ($hasEstimateDateInput) {
            $workorder->refresh();
            $estimateNotifications->handleEstimateDateChange(
                $workorder,
                $oldEstimateDate,
                $workorder->wo_estimate_date?->toDateString()
            );
        }

        $customer = $workorder->customer()->firstOrFail();
        $profile = $this->ensureProfile($customer);
        $workorder->load(['unit.manual.plane', 'instruction', 'main.task', 'media']);

        return response()->json([
            'ok' => true,
            'workorder' => $this->workorderRow($workorder, $profile),
        ]);
    }

    public function customerSalesReport(Request $request, Customer $customer, SalesReportService $reports): JsonResponse
    {
        $data = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $report = $reports->build([
            'report_type' => 'customer',
            'customer_id' => $customer->id,
            'date_from' => $data['date_from'] ?? now()->startOfYear()->format('Y-m-d'),
            'date_to' => $data['date_to'] ?? now()->endOfYear()->format('Y-m-d'),
        ]);

        return response()->json($report);
    }

    public function aircraftSalesReport(Request $request, SalesReportService $reports): JsonResponse
    {
        $data = $request->validate([
            'plane_id' => ['required', 'integer', 'exists:planes,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $report = $reports->build([
            'report_type' => 'component',
            'plane_id' => (int) $data['plane_id'],
            'date_from' => $data['date_from'] ?? now()->startOfYear()->format('Y-m-d'),
            'date_to' => $data['date_to'] ?? now()->endOfYear()->format('Y-m-d'),
        ]);

        return response()->json($report);
    }

    private function validateProfilePayload(Request $request, bool $creating): array
    {
        $rules = [
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:250'],
            'lifecycle_status' => ['nullable', Rule::in(array_keys($this->lifecycleOptions()))],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'country' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:2000'],
            'street_address' => ['nullable', 'string', 'max:2000'],
            'city' => ['nullable', 'string', 'max:120'],
            'state_province' => ['nullable', 'string', 'max:120'],
            'post_code' => ['nullable', 'string', 'max:40'],
            'company_notes' => ['nullable', 'string', 'max:8000'],
            'address_categories' => ['nullable', 'array'],
            'address_categories.*.key' => ['required_with:address_categories', Rule::in(array_keys(self::ADDRESS_CATEGORY_LABELS))],
            'address_categories.*.country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'address_categories.*.city' => ['nullable', 'string', 'max:120'],
            'address_categories.*.state_province' => ['nullable', 'string', 'max:120'],
            'address_categories.*.post_code' => ['nullable', 'string', 'max:40'],
            'address_categories.*.street_address' => ['nullable', 'string', 'max:2000'],
            'company_type_id' => ['nullable', 'integer', 'exists:marketing_company_types,id'],
            'segment_id' => ['nullable', 'integer', 'exists:marketing_segments,id'],
            'terms_label' => ['nullable', 'string', 'max:120'],
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'aircraft_ids' => ['nullable', 'array'],
            'aircraft_ids.*' => ['integer', 'exists:planes,id'],
        ];

        return $request->validate($rules);
    }

    private function validateContactPayload(Request $request): array
    {
        return $request->validate([
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'position' => ['nullable', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:80'],
            'is_primary' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);
    }

    private function validateNotePayload(Request $request, bool $creating): array
    {
        $data = $request->validate([
            'contact_id' => ['nullable', 'integer', 'exists:customer_contacts,id'],
            'note' => [$creating ? 'required' : 'sometimes', 'string', 'max:8000'],
            'interaction_at' => ['nullable', 'string', 'max:40'],
            'follow_up_at' => ['nullable', 'string', 'max:40'],
            'follow_up_status' => ['nullable', Rule::in([
                CustomerInteractionNote::STATUS_OPEN,
                CustomerInteractionNote::STATUS_DONE,
                CustomerInteractionNote::STATUS_CANCELLED,
            ])],
        ]);

        if (! array_key_exists('interaction_at', $data) || blank($data['interaction_at'])) {
            if ($creating) {
                $data['interaction_at'] = now()->toDateString();
            } else {
                unset($data['interaction_at']);
            }
        } else {
            $data['interaction_at'] = $this->parseDateInput($data['interaction_at'], 'interaction_at');
        }

        if (array_key_exists('follow_up_at', $data)) {
            $data['follow_up_at'] = $this->parseDateInput($data['follow_up_at'], 'follow_up_at');
        }

        if (! array_key_exists('follow_up_status', $data) && $creating) {
            $data['follow_up_status'] = CustomerInteractionNote::STATUS_OPEN;
        }

        return $data;
    }

    private function saveProfile(Customer $customer, array $data): CustomerMarketingProfile
    {
        $profile = $this->ensureProfile($customer);
        $countryId = $profile->country_id;
        $countryName = $profile->country;

        if (array_key_exists('country_id', $data)) {
            $countryId = $data['country_id'] ? (int) $data['country_id'] : null;
            $countryName = $countryId
                ? Country::query()->whereKey($countryId)->value('name')
                : null;
        } elseif (array_key_exists('country', $data)) {
            $countryName = $this->nullableString($data['country'] ?? null);
        }

        $streetAddress = $profile->street_address ?? $profile->address;
        if (array_key_exists('street_address', $data)) {
            $streetAddress = $this->nullableString($data['street_address']);
        } elseif (array_key_exists('address', $data)) {
            $streetAddress = $this->nullableString($data['address']);
        }

        $city = array_key_exists('city', $data) ? $this->nullableString($data['city']) : $profile->city;
        $stateProvince = array_key_exists('state_province', $data) ? $this->nullableString($data['state_province']) : $profile->state_province;
        $postCode = array_key_exists('post_code', $data) ? $this->nullableString($data['post_code']) : $profile->post_code;
        $addressCategories = array_key_exists('address_categories', $data)
            ? $this->normalizeAddressCategoriesForStorage($data['address_categories'], $profile, [
                'country_id' => $countryId,
                'city' => $city,
                'state_province' => $stateProvince,
                'post_code' => $postCode,
                'street_address' => $streetAddress,
            ])
            : $profile->address_categories;

        $profile->fill([
            'lifecycle_status' => array_key_exists('lifecycle_status', $data) ? ($data['lifecycle_status'] ?? CustomerMarketingProfile::STATUS_EXISTING) : ($profile->lifecycle_status ?? CustomerMarketingProfile::STATUS_EXISTING),
            'country_id' => $countryId,
            'country' => $countryName,
            'address' => $streetAddress,
            'street_address' => $streetAddress,
            'city' => $city,
            'state_province' => $stateProvince,
            'post_code' => $postCode,
            'company_notes' => array_key_exists('company_notes', $data) ? $this->nullableString($data['company_notes']) : $profile->company_notes,
            'address_categories' => $addressCategories,
            'company_type_id' => array_key_exists('company_type_id', $data) ? $data['company_type_id'] : $profile->company_type_id,
            'segment_id' => array_key_exists('segment_id', $data) ? $data['segment_id'] : $profile->segment_id,
            'terms_label' => array_key_exists('terms_label', $data) ? $this->nullableString($data['terms_label']) : $profile->terms_label,
            'owner_user_id' => array_key_exists('owner_user_id', $data) ? $data['owner_user_id'] : $profile->owner_user_id,
        ]);

        $profile->save();

        return $profile;
    }

    private function syncAircraft(Customer $customer, array $planeIds): void
    {
        $ids = collect($planeIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        CustomerAircraft::query()
            ->where('customer_id', $customer->id)
            ->when($ids->isNotEmpty(), fn ($query) => $query->whereNotIn('plane_id', $ids->all()))
            ->delete();

        if ($ids->isEmpty()) {
            CustomerAircraft::query()->where('customer_id', $customer->id)->delete();
            return;
        }

        foreach ($ids as $planeId) {
            CustomerAircraft::query()->firstOrCreate([
                'customer_id' => $customer->id,
                'plane_id' => $planeId,
            ]);
        }
    }

    private function syncPrimaryContact(Customer $customer, CustomerContact $contact): void
    {
        if (! $contact->is_primary) {
            return;
        }

        CustomerContact::query()
            ->where('customer_id', $customer->id)
            ->where('id', '!=', $contact->id)
            ->update(['is_primary' => false]);
    }

    private function assertContactBelongsToCustomer(mixed $contactId, Customer $customer): void
    {
        if (! $contactId) {
            return;
        }

        $belongs = CustomerContact::query()
            ->whereKey((int) $contactId)
            ->where('customer_id', $customer->id)
            ->exists();

        if (! $belongs) {
            throw ValidationException::withMessages(['contact_id' => 'Contact does not belong to this customer.']);
        }
    }

    private function syncProfileDates(Customer $customer): void
    {
        $profile = $this->ensureProfile($customer);

        $profile->last_contact_at = CustomerInteractionNote::query()
            ->where('customer_id', $customer->id)
            ->max('interaction_at');

        $profile->next_follow_up_at = CustomerInteractionNote::query()
            ->where('customer_id', $customer->id)
            ->where('follow_up_status', CustomerInteractionNote::STATUS_OPEN)
            ->whereNotNull('follow_up_at')
            ->min('follow_up_at');

        $profile->save();
    }

    private function ensureProfile(Customer $customer): CustomerMarketingProfile
    {
        return CustomerMarketingProfile::query()->firstOrCreate(
            ['customer_id' => $customer->id],
            ['lifecycle_status' => CustomerMarketingProfile::STATUS_EXISTING]
        );
    }

    private function customerDetail(Customer $customer): array
    {
        $this->ensureProfile($customer);

        $customer->load([
            'marketingProfile.companyType',
            'marketingProfile.segment',
            'marketingProfile.owner',
            'marketingProfile.countryRef',
            'marketingAircraft.plane',
            'marketingContacts',
            'marketingNotes.contact',
            'marketingNotes.user',
        ]);
        $customer->loadCount(['workorders', 'marketingContacts', 'marketingNotes']);

        return array_merge($this->customerRow($customer), [
            'profile' => $this->profileRow($customer->marketingProfile),
            'contacts' => $customer->marketingContacts->map(fn (CustomerContact $contact) => $this->contactRow($contact))->values(),
            'notes' => $customer->marketingNotes->take(30)->map(fn (CustomerInteractionNote $note) => $this->noteRow($note))->values(),
        ]);
    }

    private function customerRow(Customer $customer): array
    {
        $profile = $customer->marketingProfile;
        $country = $this->countryName($profile);
        $streetAddress = (string) ($profile?->street_address ?? $profile?->address ?? '');
        $formattedAddress = $this->formattedCompanyAddress($profile);
        $contacts = $customer->marketingContacts ?? collect();
        $primaryContact = $contacts->firstWhere('is_primary', true) ?: $contacts->first();
        $aircraft = ($customer->marketingAircraft ?? collect())
            ->map(fn (CustomerAircraft $row) => [
                'id' => $row->plane_id,
                'type' => $row->plane?->type ?? '',
            ])
            ->filter(fn (array $row) => $row['type'] !== '')
            ->values();

        return [
            'id' => $customer->id,
            'name' => (string) $customer->name,
            'country_id' => $profile?->country_id,
            'country' => $country,
            'address' => $streetAddress,
            'street_address' => $streetAddress,
            'city' => (string) ($profile?->city ?? ''),
            'state_province' => (string) ($profile?->state_province ?? ''),
            'post_code' => (string) ($profile?->post_code ?? ''),
            'company_notes' => (string) ($profile?->company_notes ?? ''),
            'formatted_address' => $formattedAddress,
            'address_categories' => $this->addressCategories($profile),
            'company_type' => $profile?->companyType?->name,
            'company_type_id' => $profile?->company_type_id,
            'segment' => $profile?->segment?->name,
            'segment_id' => $profile?->segment_id,
            'terms_label' => (string) ($profile?->terms_label ?? ''),
            'lifecycle_status' => (string) ($profile?->lifecycle_status ?? CustomerMarketingProfile::STATUS_EXISTING),
            'lifecycle_label' => $this->lifecycleOptions()[$profile?->lifecycle_status ?? CustomerMarketingProfile::STATUS_EXISTING] ?? 'Existing',
            'aircraft' => $aircraft,
            'aircraft_text' => $aircraft->pluck('type')->implode(', '),
            'primary_contact' => $primaryContact ? $this->contactRow($primaryContact) : null,
            'contacts_count' => (int) ($customer->marketing_contacts_count ?? $contacts->count()),
            'notes_count' => (int) ($customer->marketing_notes_count ?? 0),
            'workorders_count' => (int) ($customer->workorders_count ?? 0),
            'last_contact_at' => $this->datePayload($profile?->last_contact_at),
            'next_follow_up_at' => $this->datePayload($profile?->next_follow_up_at),
            'follow_up_state' => $this->followUpState($profile?->next_follow_up_at),
        ];
    }

    private function profileRow(?CustomerMarketingProfile $profile): array
    {
        $streetAddress = (string) ($profile?->street_address ?? $profile?->address ?? '');
        $formattedAddress = $this->formattedCompanyAddress($profile);

        return [
            'lifecycle_status' => $profile?->lifecycle_status ?? CustomerMarketingProfile::STATUS_EXISTING,
            'country_id' => $profile?->country_id,
            'country' => $this->countryName($profile),
            'address' => $streetAddress,
            'street_address' => $streetAddress,
            'city' => (string) ($profile?->city ?? ''),
            'state_province' => (string) ($profile?->state_province ?? ''),
            'post_code' => (string) ($profile?->post_code ?? ''),
            'company_notes' => (string) ($profile?->company_notes ?? ''),
            'formatted_address' => $formattedAddress,
            'address_categories' => $this->addressCategories($profile),
            'company_type_id' => $profile?->company_type_id,
            'company_type' => $profile?->companyType?->name,
            'segment_id' => $profile?->segment_id,
            'segment' => $profile?->segment?->name,
            'terms_label' => (string) ($profile?->terms_label ?? ''),
            'owner_user_id' => $profile?->owner_user_id,
            'owner_name' => $profile?->owner?->name,
            'last_contact_at' => $this->datePayload($profile?->last_contact_at),
            'next_follow_up_at' => $this->datePayload($profile?->next_follow_up_at),
        ];
    }

    private function contactRow(CustomerContact $contact): array
    {
        return [
            'id' => $contact->id,
            'first_name' => (string) ($contact->first_name ?? ''),
            'last_name' => (string) ($contact->last_name ?? ''),
            'full_name' => $contact->full_name,
            'position' => (string) ($contact->position ?? ''),
            'email' => (string) ($contact->email ?? ''),
            'phone' => (string) ($contact->phone ?? ''),
            'is_primary' => (bool) $contact->is_primary,
            'is_active' => (bool) $contact->is_active,
            'sort_order' => (int) $contact->sort_order,
        ];
    }

    private function noteRow(CustomerInteractionNote $note): array
    {
        return [
            'id' => $note->id,
            'contact_id' => $note->contact_id,
            'contact_name' => $note->contact?->full_name ?? '',
            'user_name' => $note->user?->name ?? 'System',
            'note' => (string) $note->note,
            'interaction_at' => $this->datePayload($note->interaction_at),
            'follow_up_at' => $this->datePayload($note->follow_up_at),
            'follow_up_status' => (string) $note->follow_up_status,
            'reminder_sent_at' => $note->reminder_sent_at?->toIso8601String(),
            'created_at' => $note->created_at?->toIso8601String(),
        ];
    }

    private function workorderRow(Workorder $workorder, CustomerMarketingProfile $profile): array
    {
        $isDone = $workorder->isDone();
        $status = $isDone ? 'Complete' : ($workorder->approve_at ? 'In Process' : 'Waiting Approval');
        $media = $workorder->media ?? collect();
        $pdfCount = $media->filter(fn ($item) => str_contains((string) $item->mime_type, 'pdf'))->count();
        $imageCount = $media->filter(fn ($item) => str_starts_with((string) $item->mime_type, 'image/'))->count();

        return [
            'id' => $workorder->id,
            'number' => $workorder->number,
            'number_label' => 'W' . $workorder->number,
            'ro_number' => (string) ($workorder->customer_po ?? ''),
            'part_number' => (string) ($workorder->unit?->part_number ?? ''),
            'description' => (string) ($workorder->displayDescription() ?? $workorder->description ?? ''),
            'serial_number' => (string) ($workorder->serial_number ?? ''),
            'aircraft_type' => (string) ($workorder->unit?->manual?->plane?->type ?? ''),
            'task' => (string) ($workorder->instruction?->name ?? ''),
            'terms' => (string) ($workorder->wo_terms ?? ''),
            'status' => $status,
            'estimate_amount' => $this->moneyPayload($workorder->wo_estimate_amount),
            'estimate_date' => $this->datePayload($workorder->wo_estimate_date),
            'approval_date' => $this->datePayload($workorder->approve_at),
            'sales_invoice_amount' => $this->moneyPayload($workorder->sales_invoice_amount),
            'sales_invoice_date' => $this->datePayload($workorder->sales_invoice_date),
            'shipping_shipment_at' => $this->datePayload($workorder->shipping_shipment_at),
            'shipping_awb_no' => (string) ($workorder->shipping_awb_no ?? ''),
            'pdf_count' => $pdfCount,
            'image_count' => $imageCount,
            'urls' => [
                'open' => route('mains.show', $workorder->id),
                'photos' => route('workorders.photos', $workorder->id),
                'pdfs' => route('workorders.pdfs', $workorder->id),
            ],
        ];
    }

    private function applyMarketingWorkorderFilters($query, array $filters, CustomerMarketingProfile $profile): void
    {
        $global = trim((string) ($filters['wo_q'] ?? ''));
        if ($global !== '') {
            $this->applyMarketingWorkorderGlobalSearch($query, $global, $profile);
        }

        $this->applyMarketingWorkorderNumberFilter($query, (string) ($filters['wo_number'] ?? ''));
        $this->applyMarketingWorkorderStatusTextFilter($query, (string) ($filters['wo_status'] ?? ''));
        $this->applyMarketingWorkorderLikeFilter($query, 'customer_po', (string) ($filters['wo_ro'] ?? ''));
        $this->applyMarketingWorkorderUnitFilter($query, 'part_number', (string) ($filters['wo_part'] ?? ''));
        $this->applyMarketingWorkorderDescriptionFilter($query, (string) ($filters['wo_description'] ?? ''));
        $this->applyMarketingWorkorderLikeFilter($query, 'serial_number', (string) ($filters['wo_serial'] ?? ''));
        $this->applyMarketingWorkorderRelationFilter($query, 'instruction', 'name', (string) ($filters['wo_task'] ?? ''));
        $this->applyMarketingWorkorderLikeFilter($query, 'wo_terms', (string) ($filters['wo_terms'] ?? ''));
        $this->applyMarketingWorkorderMoneyFilter($query, 'wo_estimate_amount', (string) ($filters['wo_estimate'] ?? ''));
        $this->applyMarketingWorkorderDateFilter($query, 'wo_estimate_date', (string) ($filters['wo_estimate_date'] ?? ''));
        $this->applyMarketingWorkorderDateFilter($query, 'approve_at', (string) ($filters['wo_approval_date'] ?? ''));
        $this->applyMarketingWorkorderMoneyFilter($query, 'sales_invoice_amount', (string) ($filters['wo_invoice'] ?? ''));
        $this->applyMarketingWorkorderDateFilter($query, 'sales_invoice_date', (string) ($filters['wo_invoice_date'] ?? ''));
        $this->applyMarketingWorkorderDateFilter($query, 'shipping_shipment_at', (string) ($filters['wo_ship_date'] ?? ''));
        $this->applyMarketingWorkorderLikeFilter($query, 'shipping_awb_no', (string) ($filters['wo_awb'] ?? ''));
    }

    private function applyMarketingWorkorderGlobalSearch($query, string $value, CustomerMarketingProfile $profile): void
    {
        $like = '%' . $this->escapeLike($value) . '%';
        $number = $this->workorderNumberSearchValue($value);
        $date = $this->parseSearchDate($value);
        $money = $this->normalizeMoneySearchValue($value);

        $query->where(function ($inner) use ($like, $number, $date, $money, $value): void {
            $inner->where('customer_po', 'like', $like)
                ->orWhere('wo_terms', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhere('serial_number', 'like', $like)
                ->orWhere('wo_estimate_date', 'like', $like)
                ->orWhere('approve_at', 'like', $like)
                ->orWhere('sales_invoice_date', 'like', $like)
                ->orWhere('shipping_shipment_at', 'like', $like)
                ->orWhere('shipping_awb_no', 'like', $like)
                ->orWhereHas('unit', function ($unit) use ($like): void {
                    $unit->where('part_number', 'like', $like)
                        ->orWhere('name', 'like', $like)
                        ->orWhere('description', 'like', $like);
                })
                ->orWhereHas('instruction', fn ($instruction) => $instruction->where('name', 'like', $like));

            if ($number !== '') {
                $inner->orWhere('number', 'like', '%' . $this->escapeLike($number) . '%');
            }

            if ($date !== null) {
                $inner->orWhereDate('wo_estimate_date', $date)
                    ->orWhereDate('approve_at', $date)
                    ->orWhereDate('sales_invoice_date', $date)
                    ->orWhereDate('shipping_shipment_at', $date);
            }

            if ($money !== '') {
                $moneyLike = '%' . $this->escapeLike($money) . '%';
                $inner->orWhere('wo_estimate_amount', 'like', $moneyLike)
                    ->orWhere('sales_invoice_amount', 'like', $moneyLike);
            }

            $this->orWhereMarketingWorkorderStatusTextMatches($inner, $value);
        });
    }

    private function applyMarketingWorkorderNumberFilter($query, string $value): void
    {
        $number = $this->workorderNumberSearchValue($value);
        if ($number === '') {
            return;
        }

        $query->where('number', 'like', '%' . $this->escapeLike($number) . '%');
    }

    private function applyMarketingWorkorderLikeFilter($query, string $column, string $value): void
    {
        $value = trim($value);
        if ($value === '') {
            return;
        }

        $query->where($column, 'like', '%' . $this->escapeLike($value) . '%');
    }

    private function applyMarketingWorkorderUnitFilter($query, string $column, string $value): void
    {
        $value = trim($value);
        if ($value === '') {
            return;
        }

        $like = '%' . $this->escapeLike($value) . '%';
        $query->whereHas('unit', fn ($unit) => $unit->where($column, 'like', $like));
    }

    private function applyMarketingWorkorderRelationFilter($query, string $relation, string $column, string $value): void
    {
        $value = trim($value);
        if ($value === '') {
            return;
        }

        $like = '%' . $this->escapeLike($value) . '%';
        $query->whereHas($relation, fn ($related) => $related->where($column, 'like', $like));
    }

    private function applyMarketingWorkorderDescriptionFilter($query, string $value): void
    {
        $value = trim($value);
        if ($value === '') {
            return;
        }

        $like = '%' . $this->escapeLike($value) . '%';
        $query->where(function ($inner) use ($like): void {
            $inner->where('description', 'like', $like)
                ->orWhereHas('unit', function ($unit) use ($like): void {
                    $unit->where('name', 'like', $like)
                        ->orWhere('description', 'like', $like);
                });
        });
    }

    private function applyMarketingWorkorderMoneyFilter($query, string $column, string $value): void
    {
        $value = $this->normalizeMoneySearchValue($value);
        if ($value === '') {
            return;
        }

        $query->where($column, 'like', '%' . $this->escapeLike($value) . '%');
    }

    private function applyMarketingWorkorderDateFilter($query, string $column, string $value): void
    {
        $value = trim($value);
        if ($value === '') {
            return;
        }

        $date = $this->parseSearchDate($value);
        if ($date !== null) {
            $query->whereDate($column, $date);
            return;
        }

        $query->where($column, 'like', '%' . $this->escapeLike($value) . '%');
    }

    private function applyMarketingWorkorderStatusTextFilter($query, string $value): void
    {
        $value = trim($value);
        if ($value === '') {
            return;
        }

        $statuses = $this->matchingMarketingWorkorderStatuses($value);
        if ($statuses === []) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->where(function ($inner) use ($statuses): void {
            foreach ($statuses as $status) {
                $inner->orWhere(fn ($statusQuery) => $this->applyMarketingWorkorderStatusCondition($statusQuery, $status));
            }
        });
    }

    private function orWhereMarketingWorkorderStatusTextMatches($query, string $value): void
    {
        foreach ($this->matchingMarketingWorkorderStatuses($value) as $status) {
            $query->orWhere(fn ($statusQuery) => $this->applyMarketingWorkorderStatusCondition($statusQuery, $status));
        }
    }

    private function applyMarketingWorkorderStatusCondition($query, string $status): void
    {
        if ($status === 'complete') {
            $query->whereHas('main', fn ($main) => $this->applyCompletedMainConstraint($main));
            return;
        }

        if ($status === 'in_process') {
            $query->whereNotNull('approve_at')
                ->whereDoesntHave('main', fn ($main) => $this->applyCompletedMainConstraint($main));
            return;
        }

        if ($status === 'waiting_approval') {
            $query->whereNull('approve_at')
                ->whereDoesntHave('main', fn ($main) => $this->applyCompletedMainConstraint($main));
        }
    }

    private function applyCompletedMainConstraint($query): void
    {
        $query->where(function ($inner): void {
            $inner->where('ignore_row', false)
                ->orWhereNull('ignore_row');
        })
            ->whereNotNull('date_finish')
            ->whereHas('task', fn ($task) => $task->whereIn('name', ['Completed', 'Complete']));
    }

    private function matchingMarketingWorkorderStatuses(string $value): array
    {
        $needle = strtolower(trim($value));
        if ($needle === '') {
            return [];
        }

        return collect([
            'complete' => 'complete',
            'in_process' => 'in process',
            'waiting_approval' => 'waiting approval',
        ])
            ->filter(fn (string $label): bool => str_contains($label, $needle))
            ->keys()
            ->values()
            ->all();
    }

    private function workorderNumberSearchValue(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private function parseSearchDate(string $value): ?string
    {
        try {
            return parse_project_date($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function containsSearchText(mixed $haystack, string $needle): bool
    {
        return str_contains(
            strtolower((string) $haystack),
            strtolower(trim($needle))
        );
    }

    private function countryName(?CustomerMarketingProfile $profile): string
    {
        return (string) ($profile?->countryRef?->name ?? $profile?->country ?? '');
    }

    private function formattedCompanyAddress(?CustomerMarketingProfile $profile): string
    {
        if (! $profile) {
            return '';
        }

        $streetAddress = $this->nullableString($profile->street_address ?? $profile->address);
        $cityRegion = collect([
            $this->nullableString($profile->city),
            $this->nullableString($profile->state_province),
        ])->filter()->implode(', ');
        $cityLine = collect([
            $cityRegion !== '' ? $cityRegion : null,
            $this->nullableString($profile->post_code),
        ])->filter()->implode(' ');

        return collect([
            $streetAddress,
            $cityLine !== '' ? $cityLine : null,
            $this->countryName($profile) ?: null,
        ])->filter()->implode("\n");
    }

    private function addressCategories(?CustomerMarketingProfile $profile): array
    {
        $stored = is_array($profile?->address_categories) ? $profile->address_categories : [];
        $base = $this->baseAddressPayload($profile);
        $countryNamesById = collect($stored)
            ->filter(fn ($item): bool => is_array($item) && !empty($item['country_id']))
            ->map(fn (array $item): int => (int) $item['country_id'])
            ->push($base['country_id'] ? (int) $base['country_id'] : null)
            ->filter()
            ->unique()
            ->values()
            ->pipe(fn ($ids): array => $ids->isEmpty()
                ? []
                : Country::query()->whereIn('id', $ids->all())->pluck('name', 'id')->all());

        return collect(self::ADDRESS_CATEGORY_LABELS)
            ->map(function (string $label, string $key) use ($stored, $base, $countryNamesById): array {
                $item = $this->storedAddressCategoryByKey($stored, $key) ?? [];
                $parts = [
                    'country_id' => array_key_exists('country_id', $item) ? $item['country_id'] : $base['country_id'],
                    'country' => array_key_exists('country', $item) ? $item['country'] : $base['country'],
                    'city' => array_key_exists('city', $item) ? $item['city'] : $base['city'],
                    'state_province' => array_key_exists('state_province', $item) ? $item['state_province'] : $base['state_province'],
                    'post_code' => array_key_exists('post_code', $item) ? $item['post_code'] : $base['post_code'],
                    'street_address' => array_key_exists('street_address', $item) ? $item['street_address'] : $base['street_address'],
                ];

                return [
                    'key' => $key,
                    'label' => $label,
                    'country_id' => $parts['country_id'] ? (int) $parts['country_id'] : null,
                    'country' => $this->countryNameFromAddressParts($parts, $countryNamesById),
                    'city' => (string) ($parts['city'] ?? ''),
                    'state_province' => (string) ($parts['state_province'] ?? ''),
                    'post_code' => (string) ($parts['post_code'] ?? ''),
                    'street_address' => (string) ($parts['street_address'] ?? ''),
                    'address' => $this->formattedAddressFromParts($parts, $countryNamesById),
                ];
            })
            ->values()
            ->all();
    }

    private function normalizeAddressCategoriesForStorage(mixed $items, ?CustomerMarketingProfile $profile, array $baseOverrides = []): array
    {
        $base = array_merge($this->baseAddressPayload($profile), $baseOverrides);
        $byKey = [];

        foreach ((array) $items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $key = (string) ($item['key'] ?? '');
            if (! array_key_exists($key, self::ADDRESS_CATEGORY_LABELS)) {
                continue;
            }

            $byKey[$key] = $item;
        }

        return collect(self::ADDRESS_CATEGORY_LABELS)
            ->map(function (string $label, string $key) use ($byKey, $base): array {
                $item = $byKey[$key] ?? [];

                return [
                    'key' => $key,
                    'label' => $label,
                    'country_id' => array_key_exists('country_id', $item)
                        ? ($item['country_id'] ? (int) $item['country_id'] : null)
                        : ($base['country_id'] ? (int) $base['country_id'] : null),
                    'city' => array_key_exists('city', $item) ? $this->nullableString($item['city']) : $this->nullableString($base['city'] ?? null),
                    'state_province' => array_key_exists('state_province', $item) ? $this->nullableString($item['state_province']) : $this->nullableString($base['state_province'] ?? null),
                    'post_code' => array_key_exists('post_code', $item) ? $this->nullableString($item['post_code']) : $this->nullableString($base['post_code'] ?? null),
                    'street_address' => array_key_exists('street_address', $item) ? $this->nullableString($item['street_address']) : $this->nullableString($base['street_address'] ?? null),
                ];
            })
            ->values()
            ->all();
    }

    private function baseAddressPayload(?CustomerMarketingProfile $profile): array
    {
        return [
            'country_id' => $profile?->country_id,
            'country' => $this->countryName($profile),
            'city' => $profile?->city,
            'state_province' => $profile?->state_province,
            'post_code' => $profile?->post_code,
            'street_address' => $profile?->street_address ?? $profile?->address,
        ];
    }

    private function storedAddressCategoryByKey(array $stored, string $key): ?array
    {
        foreach ($stored as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (($item['key'] ?? null) === $key) {
                return $item;
            }
        }

        return null;
    }

    private function formattedAddressFromParts(array $parts, array $countryNamesById = []): string
    {
        $cityRegion = collect([
            $this->nullableString($parts['city'] ?? null),
            $this->nullableString($parts['state_province'] ?? null),
        ])->filter()->implode(', ');

        $cityLine = collect([
            $cityRegion !== '' ? $cityRegion : null,
            $this->nullableString($parts['post_code'] ?? null),
        ])->filter()->implode(' ');

        return collect([
            $this->nullableString($parts['street_address'] ?? null),
            $cityLine !== '' ? $cityLine : null,
            $this->countryNameFromAddressParts($parts, $countryNamesById) ?: null,
        ])->filter()->implode("\n");
    }

    private function countryNameFromAddressParts(array $parts, array $countryNamesById = []): string
    {
        if (!empty($parts['country_id'])) {
            $countryId = (int) $parts['country_id'];

            return (string) ($countryNamesById[$countryId] ?? Country::query()->whereKey($countryId)->value('name') ?? '');
        }

        return (string) ($parts['country'] ?? '');
    }

    private function addressCategoryLabels(): array
    {
        return self::ADDRESS_CATEGORY_LABELS;
    }

    private function countryFilterAliases(Country $country): array
    {
        $aliases = [
            $country->name,
            $country->alpha2,
        ];

        if ($country->alpha2 === 'US') {
            $aliases = array_merge($aliases, ['USA', 'U.S.A.', 'United States of America']);
        }

        if ($country->alpha2 === 'GB') {
            $aliases = array_merge($aliases, ['UK', 'U.K.', 'Great Britain']);
        }

        return collect($aliases)
            ->map(fn ($value): string => strtolower(trim((string) $value)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function datePayload(mixed $date): array
    {
        if (! $date) {
            return ['iso' => null, 'display' => ''];
        }

        try {
            $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
        } catch (\Throwable) {
            return ['iso' => null, 'display' => (string) $date];
        }

        return [
            'iso' => $carbon->toDateString(),
            'display' => format_project_date($carbon) ?? $carbon->toDateString(),
        ];
    }

    private function moneyPayload(mixed $value): array
    {
        if ($value === null || $value === '') {
            return ['value' => null, 'display' => ''];
        }

        $amount = (float) $value;
        $formattedValue = number_format($amount, 2, '.', '');
        $decimals = abs($amount - round($amount)) < 0.005 ? 0 : 2;

        return [
            'value' => $formattedValue,
            'display' => '$' . number_format($amount, $decimals, '.', ','),
        ];
    }

    private function followUpState(mixed $date): string
    {
        if (! $date) {
            return 'none';
        }

        try {
            return Carbon::parse($date)->lte(now()->startOfDay()) ? 'due' : 'upcoming';
        } catch (\Throwable) {
            return 'none';
        }
    }

    private function parseDateInput(?string $value, string $field): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        $parsed = parse_project_date($value);

        if (! $parsed) {
            throw ValidationException::withMessages([$field => 'Invalid date.']);
        }

        return $parsed;
    }

    private function parseMoneyInput(mixed $value, string $field): ?string
    {
        $normalized = $this->normalizeMoneySearchValue((string) $value);

        if ($normalized === '') {
            return null;
        }

        if (! preg_match('/^-?\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw ValidationException::withMessages([$field => 'Invalid amount.']);
        }

        return number_format((float) $normalized, 2, '.', '');
    }

    private function normalizeMoneySearchValue(string $value): string
    {
        return trim(str_replace(['$', ',', ' '], '', $value));
    }

    private function lifecycleOptions(): array
    {
        return [
            CustomerMarketingProfile::STATUS_EXISTING => 'Existing',
            CustomerMarketingProfile::STATUS_POTENTIAL => 'Potential',
            CustomerMarketingProfile::STATUS_INACTIVE => 'Inactive',
        ];
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }

    private function logMarketingActivity(string $event, object $subject, string $description, array $old, array $new): void
    {
        if ($event === 'updated' && $old === $new) {
            return;
        }

        $customer = $subject instanceof Customer
            ? $subject
            : (method_exists($subject, 'customer') ? $subject->customer : null);

        activity('marketing')
            ->causedBy(auth()->user())
            ->performedOn($subject)
            ->event($event)
            ->withProperties([
                'customer' => $customer instanceof Customer ? (string) $customer->name : '',
                'customer_id' => $customer instanceof Customer ? (int) $customer->id : null,
                'old' => $old,
                'new' => $new,
            ])
            ->log($description);
    }

    private function customerAuditSnapshot(Customer $customer): array
    {
        $customer->loadMissing([
            'marketingProfile.companyType',
            'marketingProfile.segment',
            'marketingProfile.owner',
            'marketingProfile.countryRef',
            'marketingAircraft.plane',
        ]);

        $profile = $customer->marketingProfile;

        return [
            'company' => (string) $customer->name,
            'status' => $this->lifecycleOptions()[$profile?->lifecycle_status ?? CustomerMarketingProfile::STATUS_EXISTING] ?? (string) ($profile?->lifecycle_status ?? ''),
            'country' => $this->countryName($profile),
            'street address' => (string) ($profile?->street_address ?? $profile?->address ?? ''),
            'city' => (string) ($profile?->city ?? ''),
            'state/province' => (string) ($profile?->state_province ?? ''),
            'post code' => (string) ($profile?->post_code ?? ''),
            'company notes' => (string) ($profile?->company_notes ?? ''),
            'type' => (string) ($profile?->companyType?->name ?? ''),
            'segment' => (string) ($profile?->segment?->name ?? ''),
            'terms' => (string) ($profile?->terms_label ?? ''),
            'owner' => (string) ($profile?->owner?->name ?? ''),
            'aircraft' => $this->aircraftAuditLabel($customer),
            'last contact' => $this->dateAuditLabel($profile?->last_contact_at),
            'next follow-up' => $this->dateAuditLabel($profile?->next_follow_up_at),
        ];
    }

    private function contactAuditSnapshot(CustomerContact $contact): array
    {
        $contact->loadMissing('customer');

        return [
            'company' => (string) ($contact->customer?->name ?? ''),
            'contact' => $this->contactAuditLabel($contact),
            'first name' => (string) ($contact->first_name ?? ''),
            'last name' => (string) ($contact->last_name ?? ''),
            'position' => (string) ($contact->position ?? ''),
            'email' => (string) ($contact->email ?? ''),
            'phone' => (string) ($contact->phone ?? ''),
            'primary' => $contact->is_primary ? 'yes' : 'no',
            'active' => $contact->is_active ? 'yes' : 'no',
        ];
    }

    private function noteAuditSnapshot(CustomerInteractionNote $note): array
    {
        $note->loadMissing(['customer', 'contact', 'user']);

        return [
            'company' => (string) ($note->customer?->name ?? ''),
            'contact' => $note->contact ? $this->contactAuditLabel($note->contact) : '',
            'author' => (string) ($note->user?->name ?? 'System'),
            'note' => (string) $note->note,
            'interaction date' => $this->dateAuditLabel($note->interaction_at),
            'follow-up date' => $this->dateAuditLabel($note->follow_up_at),
            'follow-up status' => (string) $note->follow_up_status,
        ];
    }

    private function aircraftAuditLabel(Customer $customer): string
    {
        $customer->loadMissing('marketingAircraft.plane');

        return $customer->marketingAircraft
            ->map(fn (CustomerAircraft $row) => (string) ($row->plane?->type ?? ''))
            ->filter()
            ->sort()
            ->values()
            ->implode(', ');
    }

    private function contactAuditLabel(CustomerContact $contact): string
    {
        $name = trim($contact->full_name);
        $email = trim((string) $contact->email);

        if ($name !== '' && $email !== '') {
            return "{$name} <{$email}>";
        }

        return $name !== '' ? $name : $email;
    }

    private function dateAuditLabel(mixed $date): string
    {
        $payload = $this->datePayload($date);

        return (string) ($payload['display'] ?? '');
    }
}
