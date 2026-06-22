<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MarketingController extends Controller
{
    public function index(): View
    {
        return view('admin.marketing.index', [
            'companyTypes' => MarketingCompanyType::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'segments' => MarketingSegment::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'planes' => Plane::query()->orderBy('type')->get(['id', 'type']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'lifecycleOptions' => $this->lifecycleOptions(),
        ]);
    }

    public function customers(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'company_type_id' => ['nullable', 'integer', 'exists:marketing_company_types,id'],
            'segment_id' => ['nullable', 'integer', 'exists:marketing_segments,id'],
            'plane_id' => ['nullable', 'integer', 'exists:planes,id'],
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
                            ->orWhere('terms_label', 'like', $like);
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

        if (!empty($filters['country'])) {
            $country = '%' . $this->escapeLike(trim((string) $filters['country'])) . '%';
            $query->whereHas('marketingProfile', fn ($profile) => $profile->where('country', 'like', $country));
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
        ]);

        $perPage = (int) ($data['per_page'] ?? 20);

        $workorders = Workorder::query()
            ->where('customer_id', $customer->id)
            ->with(['unit.manual.plane', 'instruction', 'main.task', 'media'])
            ->orderByDesc('open_at')
            ->orderByDesc('number')
            ->paginate($perPage);

        $profile = $this->ensureProfile($customer);

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

    private function validateProfilePayload(Request $request, bool $creating): array
    {
        $rules = [
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:250'],
            'lifecycle_status' => ['nullable', Rule::in(array_keys($this->lifecycleOptions()))],
            'country' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:2000'],
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

        $profile->fill([
            'lifecycle_status' => $data['lifecycle_status'] ?? $profile->lifecycle_status ?? CustomerMarketingProfile::STATUS_EXISTING,
            'country' => $data['country'] ?? $profile->country,
            'address' => $data['address'] ?? $profile->address,
            'company_type_id' => array_key_exists('company_type_id', $data) ? $data['company_type_id'] : $profile->company_type_id,
            'segment_id' => array_key_exists('segment_id', $data) ? $data['segment_id'] : $profile->segment_id,
            'terms_label' => $data['terms_label'] ?? $profile->terms_label,
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
            'country' => (string) ($profile?->country ?? ''),
            'address' => (string) ($profile?->address ?? ''),
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
        return [
            'lifecycle_status' => $profile?->lifecycle_status ?? CustomerMarketingProfile::STATUS_EXISTING,
            'country' => (string) ($profile?->country ?? ''),
            'address' => (string) ($profile?->address ?? ''),
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
            'terms' => (string) ($profile->terms_label ?? ''),
            'status' => $status,
            'estimate_amount' => null,
            'estimate_date' => $this->datePayload($workorder->open_at),
            'approval_date' => $this->datePayload($workorder->approve_at),
            'pdf_count' => $pdfCount,
            'image_count' => $imageCount,
            'urls' => [
                'open' => route('mains.show', $workorder->id),
                'photos' => route('workorders.photos', $workorder->id),
                'pdfs' => route('workorders.pdfs', $workorder->id),
            ],
        ];
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
            'marketingAircraft.plane',
        ]);

        $profile = $customer->marketingProfile;

        return [
            'company' => (string) $customer->name,
            'status' => $this->lifecycleOptions()[$profile?->lifecycle_status ?? CustomerMarketingProfile::STATUS_EXISTING] ?? (string) ($profile?->lifecycle_status ?? ''),
            'country' => (string) ($profile?->country ?? ''),
            'address' => (string) ($profile?->address ?? ''),
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
