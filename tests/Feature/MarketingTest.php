<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\CustomerAircraft;
use App\Models\CustomerContact;
use App\Models\CustomerInteractionNote;
use App\Models\CustomerMarketingProfile;
use App\Models\GeneralTask;
use App\Models\Main;
use App\Models\MarketingCompanyType;
use App\Models\MarketingSegment;
use App\Models\MarketingWoFile;
use App\Models\MarketingWoEstimateNotification;
use App\Models\ProjectSetting;
use App\Models\Task;
use App\Models\User;
use App\Models\UserFeatureAccess;
use App\Mail\MarketingWoEstimateDateMail;
use App\Mail\MarketingWoFileMail;
use App\Services\SalesReportQuantumInvoiceProvider;
use App\Notifications\NewMessageNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;
use Tests\BuildsDomainData;
use Tests\TestCase;

class MarketingTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_marketing_access_is_limited_to_assigned_users(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manager = $this->createUserWithRole('Manager');
        $technician = $this->createUserWithRole('Technician');

        $this->grantMarketingAccess($admin);
        $this->grantMarketingAccess($manager);

        $this->actingAs($admin)
            ->get(route('marketing.index'))
            ->assertOk();

        $this->actingAs($manager)
            ->getJson(route('marketing.customers.index'))
            ->assertOk();

        $this->actingAs($technician)
            ->get(route('marketing.index'))
            ->assertForbidden();

        $this->actingAs($technician)
            ->getJson(route('marketing.customers.index'))
            ->assertForbidden();
    }

    public function test_library_country_reference_can_be_managed_by_admin(): void
    {
        $admin = $this->createUserWithRole('Admin');

        $this->actingAs($admin)
            ->post(route('library.countries.store'), [
                'name' => 'Testland',
                'alpha2' => 'XZ',
                'sort_order' => 900,
                'active' => '1',
            ])
            ->assertRedirect(route('library.countries.index'));

        $country = Country::query()->where('alpha2', 'XZ')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('library.countries.index', ['q' => 'Testland']))
            ->assertOk()
            ->assertSee('Testland');

        $this->actingAs($admin)
            ->put(route('library.countries.update', $country), [
                'name' => 'Testland Updated',
                'alpha2' => 'XZ',
                'sort_order' => 901,
            ])
            ->assertRedirect(route('library.countries.index'));

        $this->assertDatabaseHas('countries', [
            'id' => $country->id,
            'name' => 'Testland Updated',
            'active' => false,
        ]);

        $this->actingAs($admin)
            ->delete(route('library.countries.destroy', $country->fresh()))
            ->assertRedirect(route('library.countries.index'));

        $this->assertDatabaseMissing('countries', [
            'id' => $country->id,
        ]);
    }

    public function test_library_country_reference_requires_country_access(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $countryUser = $this->createUserWithRole('Technician', ['name' => 'Country Library User']);
        $this->grantFeatureAccess($countryUser, 'library.countries');

        $this->actingAs($technician)
            ->get(route('library.countries.index'))
            ->assertForbidden();

        $this->actingAs($countryUser)
            ->get(route('library.countries.index'))
            ->assertOk()
            ->assertSee('Library')
            ->assertSee('Countries')
            ->assertDontSee('/library/units', false)
            ->assertDontSee('/library/type-of-business', false);
    }

    public function test_library_type_of_business_reference_can_be_managed_by_admin(): void
    {
        $admin = $this->createUserWithRole('Admin');

        $this->actingAs($admin)
            ->post(route('library.type-of-business.store'), [
                'name' => 'Test Business Type',
                'sort_order' => 902,
            ])
            ->assertRedirect(route('library.type-of-business.index'));

        $companyType = MarketingCompanyType::query()
            ->where('name', 'Test Business Type')
            ->firstOrFail();

        $this->actingAs($admin)
            ->get(route('library.type-of-business.index', ['q' => 'Test Business Type']))
            ->assertOk()
            ->assertSee('Test Business Type');

        $this->actingAs($admin)
            ->put(route('library.type-of-business.update', $companyType), [
                'name' => 'Test Business Type Updated',
                'sort_order' => 903,
            ])
            ->assertRedirect(route('library.type-of-business.index'));

        $this->assertDatabaseHas('marketing_company_types', [
            'id' => $companyType->id,
            'name' => 'Test Business Type Updated',
            'sort_order' => 903,
        ]);

        $customer = $this->createCustomer(['name' => 'Business Type Lock Customer']);
        CustomerMarketingProfile::query()->create([
            'customer_id' => $customer->id,
            'lifecycle_status' => CustomerMarketingProfile::STATUS_EXISTING,
            'company_type_id' => $companyType->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('library.type-of-business.destroy', $companyType->fresh()))
            ->assertRedirect(route('library.type-of-business.index'));

        $this->assertDatabaseHas('marketing_company_types', [
            'id' => $companyType->id,
        ]);

        CustomerMarketingProfile::query()
            ->where('customer_id', $customer->id)
            ->delete();

        $this->actingAs($admin)
            ->delete(route('library.type-of-business.destroy', $companyType->fresh()))
            ->assertRedirect(route('library.type-of-business.index'));

        $this->assertDatabaseMissing('marketing_company_types', [
            'id' => $companyType->id,
        ]);
    }

    public function test_library_type_of_business_reference_requires_business_type_access(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $businessTypeUser = $this->createUserWithRole('Technician', ['name' => 'Business Type Library User']);
        $this->grantFeatureAccess($businessTypeUser, 'library.type_of_business');

        $this->actingAs($technician)
            ->get(route('library.type-of-business.index'))
            ->assertForbidden();

        $this->actingAs($businessTypeUser)
            ->get(route('library.type-of-business.index'))
            ->assertOk()
            ->assertSee('Library')
            ->assertSee('Type of Business')
            ->assertDontSee('/library/units', false)
            ->assertDontSee('/library/countries', false);
    }

    public function test_marketing_customer_profile_can_be_updated_and_listed(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->grantMarketingAccess($admin);

        $customer = $this->createCustomer(['name' => 'SkyService']);
        $companyType = MarketingCompanyType::query()->firstOrCreate(['name' => 'MRO'], ['sort_order' => 10]);
        $segment = MarketingSegment::query()->firstOrCreate(['name' => 'Regional'], ['sort_order' => 10]);
        $canada = Country::query()->firstOrCreate(['alpha2' => 'CA'], ['name' => 'Canada', 'sort_order' => 10, 'active' => true]);
        $unitedStates = Country::query()->firstOrCreate(['alpha2' => 'US'], ['name' => 'United States', 'sort_order' => 20, 'active' => true]);
        $plane = $this->createPlane(['type' => 'CL601']);

        $this->actingAs($admin)
            ->get(route('marketing.index'))
            ->assertOk()
            ->assertSee('data-marketing-page', false)
            ->assertSee('id="marketingShell"', false)
            ->assertSee('id="marketingSplitter"', false)
            ->assertSee('placeholder="Company, contact, country, city, A/C"', false)
            ->assertSee('placeholder="$0.00"', false)
            ->assertSee('.marketing-page input::placeholder', false)
            ->assertSee('.select2-selection__placeholder', false)
            ->assertSee('is-marketing-empty-date', false)
            ->assertSee('syncWorkorderSalesDatePlaceholderState', false)
            ->assertSee('id="marketingCountry"', false)
            ->assertSee('id="detailCountryId"', false)
            ->assertSee('id="detailCity"', false)
            ->assertSee('id="detailStateProvince"', false)
            ->assertSee('id="detailPostCode"', false)
            ->assertSee('id="detailStreetAddress"', false)
            ->assertSee('id="detailCompanyNotes"', false)
            ->assertSee('id="marketingAddressCategories"', false)
            ->assertDontSee('marketing-city-select', false)
            ->assertSee('marketing-city-input', false)
            ->assertSee('list="detailCityOptions"', false)
            ->assertSee('id="createCityOptions"', false)
            ->assertSee('placeholder="Type city"', false)
            ->assertSee('initMarketingCityInputs', false)
            ->assertSee('renderCitySuggestions', false)
            ->assertSee('data-address-category', false)
            ->assertSee('Company (Name)')
            ->assertSee('Post Code')
            ->assertSee('Street Address')
            ->assertSee('Company Notes')
            ->assertSee('class="table table-sm table-hover align-middle mb-0 dir-table dir-table--ellipsis marketing-table"', false)
            ->assertSee('id="marketingWorkordersScroll"', false)
            ->assertSee('id="marketingWorkordersSearch"', false)
            ->assertSee('data-workorder-filter="number"', false)
            ->assertSee('data-workorder-filter="status"', false)
            ->assertSee('data-workorder-filter="estimate_date" placeholder="Date" maxlength="11" data-project-date', false)
            ->assertSee('data-workorder-filter="approval_date" placeholder="Date" maxlength="11" data-project-date', false)
            ->assertSee('data-workorder-filter="invoice_date" placeholder="Date" maxlength="11" data-project-date', false)
            ->assertSee('data-workorder-filter="ship_date" placeholder="Date" maxlength="11" data-project-date', false)
            ->assertDontSee('data-workorder-filter="files"', false)
            ->assertSee('currentWorkorderFilters', false)
            ->assertSee('reloadWorkordersForFilterChange', false)
            ->assertSee('role="tablist"', false)
            ->assertSee('aria-controls="marketingPaneWorkorders"', false)
            ->assertSee('aria-controls="marketingPaneSalesReport"', false)
            ->assertSee("const allowedTabs = ['overview', 'contacts', 'notes', 'workorders', 'sales_report'];", false)
            ->assertSee('salesReport: ', false)
            ->assertSee('aircraftSalesReport: ', false)
            ->assertSee('id="marketingSalesReportRows"', false)
            ->assertSee('id="marketingSalesReportCompany"', false)
            ->assertSee('id="marketingSalesReportWarning" class="marketing-sales-report-warning"', false)
            ->assertSee('id="marketingSalesReportModeCustomer"', false)
            ->assertSee('id="marketingSalesReportModeAircraft"', false)
            ->assertSee('id="marketingSalesReportAircraft"', false)
            ->assertSee('data-sales-report-customer-col', false)
            ->assertSee('class="marketing-sales-report-date"', false)
            ->assertDontSee('<th>Company Name</th>', false)
            ->assertDontSee('id="marketingSalesReportWarning" class="alert alert-warning', false)
            ->assertSee('is-company-only', false)
            ->assertSee('.marketing-detail-tabs::after', false)
            ->assertSee('height: 1px;', false)
            ->assertSee('border: 2px solid transparent;', false)
            ->assertSee('border-bottom: 0;', false)
            ->assertSee('class="table table-sm table-hover align-middle mb-0 dir-table dir-table--ellipsis marketing-workorders-table"', false)
            ->assertSee('id="marketingMediaModal"', false)
            ->assertSee('id="marketingFileUploadForm"', false)
            ->assertSee('Manager Files', false)
            ->assertSee('Production Files', false)
            ->assertSee('Send email notification', false)
            ->assertSee('js-marketing-files', false)
            ->assertDontSee('js-marketing-media', false)
            ->assertSee('id="marketingProfileForm" data-no-spinner', false)
            ->assertSee('name="estimate_date" class="form-control form-control-sm" type="text" maxlength="11" placeholder=".... /.... /......" data-project-date', false)
            ->assertSee('name="sales_invoice_date" class="form-control form-control-sm" type="text" maxlength="11" placeholder=".... /.... /......" data-project-date', false)
            ->assertSee('name="shipping_shipment_at" class="form-control form-control-sm" type="text" maxlength="11" placeholder=".... /.... /......" data-project-date', false)
            ->assertSee("workorderSalesForm.querySelectorAll('input[data-project-date]')", false)
            ->assertSee("input.addEventListener('change', debouncedWorkorderFilterReload);", false)
            ->assertDontSee("workorderSalesForm.querySelectorAll('input[type=\"date\"]')", false)
            ->assertDontSee('id="marketingContactForm"', false)
            ->assertSee('id="marketingNoteForm" class="marketing-section" data-no-spinner', false)
            ->assertSee('Subject Line', false)
            ->assertSee('name="subject"', false)
            ->assertSee('id="marketingCreateForm" data-no-spinner', false)
            ->assertSee('class="table table-sm table-hover align-middle mb-0 dir-table dir-table--ellipsis marketing-contacts-table"', false)
            ->assertSee('Email 2', false)
            ->assertSee('Office #', false)
            ->assertSee('Cell #', false)
            ->assertSee('Type of Contact', false)
            ->assertSee('data-contact-sort="type"', false)
            ->assertSee("const contactTypeOptions = ['WO Estimates', 'WO Estimates/ Invoices', 'Invoices', 'Other'];", false)
            ->assertSee('contactTypeRank(a.contact_type)', false)
            ->assertDontSee('Number(Boolean(b.is_primary)) - Number(Boolean(a.is_primary))', false)
            ->assertSee('data-contact-id="${contact.id}"', false)
            ->assertSee('data-contact-new="1"', false)
            ->assertSee('data-contact-new-toggle', false)
            ->assertSee('data-contact-copy="all"', false)
            ->assertSee('data-contact-copy="emails"', false)
            ->assertSee('data-contact-copy="phones"', false)
            ->assertSee('setContactFormEditing', false)
            ->assertSee('copyMarketingContacts', false)
            ->assertSee('setNewContactFormVisible', false)
            ->assertSee('marketing-contact-edit', false)
            ->assertSee('readonly>', false)
            ->assertSee("const filtersKey = 'filters';", false)
            ->assertSee("const selectedCustomerKey = 'selected_customer_id';", false)
            ->assertSee('restoreSelectedCustomerId', false)
            ->assertSee('saveSelectedCustomer(data.customer.id)', false)
            ->assertSee('select2:select.marketingFilters', false)
            ->assertSee('marketing-filter-clear', false)
            ->assertSee('initFilterClearButtons', false)
            ->assertSee('initMarketingAircraftSelects', false)
            ->assertSee('html[data-bs-theme="dark"] .marketing-toolbar .marketing-filter .select2-container--default .select2-selection--single', false)
            ->assertSee('--marketing-control-sm-height: 31px;', false)
            ->assertSee('.marketing-field .form-control-sm', false)
            ->assertSee('color: var(--bs-warning);', false)
            ->assertSee('detailMeta.hidden = true;', false)
            ->assertSee('marketing-workorder-complete', false)
            ->assertSee("String(wo.status || '').trim().toLowerCase() === 'complete'", false)
            ->assertSee('marketing-files-cell-button js-marketing-files', false)
            ->assertSee('Files are attached to a specific WO; select a company with workorders to use Manager Files.', false)
            ->assertSee('marketing-workorders-empty-content', false)
            ->assertDontSee('size="8"', false);

        $update = $this->actingAs($admin)->patchJson(route('marketing.customers.profile.update', $customer), [
            'name' => 'SkyService Ltd',
            'lifecycle_status' => CustomerMarketingProfile::STATUS_EXISTING,
            'country_id' => $canada->id,
            'city' => 'Toronto',
            'state_province' => 'Ontario',
            'post_code' => 'M5V 1A1',
            'street_address' => '123 Airport Road',
            'company_notes' => 'Send quotes to purchasing before calling.',
            'address_categories' => [
                [
                    'key' => 'logistics',
                    'country_id' => $canada->id,
                    'city' => 'Toronto',
                    'state_province' => 'Ontario',
                    'post_code' => 'M5V 1A1',
                    'street_address' => '123 Airport Road',
                ],
                [
                    'key' => 'shipping',
                    'country_id' => $unitedStates->id,
                    'city' => 'Miami',
                    'state_province' => 'Florida',
                    'post_code' => '33101',
                    'street_address' => '400 Aviation Way',
                ],
            ],
            'company_type_id' => $companyType->id,
            'segment_id' => $segment->id,
            'terms_label' => 'NET 30',
            'aircraft_ids' => [$plane->id],
        ]);

        $update->assertOk()
            ->assertJsonPath('customer.name', 'SkyService Ltd')
            ->assertJsonPath('customer.country', 'Canada')
            ->assertJsonPath('customer.profile.country_id', $canada->id)
            ->assertJsonPath('customer.profile.city', 'Toronto')
            ->assertJsonPath('customer.profile.state_province', 'Ontario')
            ->assertJsonPath('customer.profile.post_code', 'M5V 1A1')
            ->assertJsonPath('customer.profile.street_address', '123 Airport Road')
            ->assertJsonPath('customer.profile.company_notes', 'Send quotes to purchasing before calling.')
            ->assertJsonPath('customer.profile.formatted_address', "123 Airport Road\nToronto, Ontario M5V 1A1\nCanada")
            ->assertJsonPath('customer.profile.address_categories.0.label', 'Logistics')
            ->assertJsonPath('customer.profile.address_categories.1.label', 'Shipping')
            ->assertJsonPath('customer.profile.address_categories.1.city', 'Miami')
            ->assertJsonPath('customer.profile.address_categories.1.post_code', '33101')
            ->assertJsonPath('customer.profile.address_categories.1.address', "400 Aviation Way\nMiami, Florida 33101\nUnited States")
            ->assertJsonPath('customer.profile.address_categories.4.label', 'Purchasing')
            ->assertJsonPath('customer.aircraft.0.type', 'CL601');

        $this->assertDatabaseHas('customer_marketing_profiles', [
            'customer_id' => $customer->id,
            'country_id' => $canada->id,
            'country' => 'Canada',
            'city' => 'Toronto',
            'state_province' => 'Ontario',
            'post_code' => 'M5V 1A1',
            'street_address' => '123 Airport Road',
            'company_notes' => 'Send quotes to purchasing before calling.',
            'terms_label' => 'NET 30',
        ]);
        $this->assertDatabaseHas('customer_aircraft', [
            'customer_id' => $customer->id,
            'plane_id' => $plane->id,
        ]);

        $list = $this->actingAs($admin)->getJson(route('marketing.customers.index', [
            'q' => 'SkyService',
            'country_id' => $canada->id,
            'plane_id' => $plane->id,
        ]));

        $list->assertOk()
            ->assertJsonPath('items.0.name', 'SkyService Ltd')
            ->assertJsonPath('items.0.company_type', 'MRO');

        $cities = $this->actingAs($admin)->getJson(route('marketing.cities', [
            'country_id' => $canada->id,
            'q' => 'Tor',
        ]));

        $cities->assertOk()
            ->assertJsonPath('results.0.text', 'Toronto');

        $legacyCustomer = $this->createCustomer(['name' => 'Legacy Country Customer']);
        CustomerMarketingProfile::query()->create([
            'customer_id' => $legacyCustomer->id,
            'lifecycle_status' => CustomerMarketingProfile::STATUS_EXISTING,
            'country' => 'Canada',
        ]);

        $legacyList = $this->actingAs($admin)->getJson(route('marketing.customers.index', [
            'q' => 'Legacy Country',
            'country_id' => $canada->id,
        ]));

        $legacyList->assertOk()
            ->assertJsonPath('items.0.name', 'Legacy Country Customer')
            ->assertJsonPath('items.0.country', 'Canada');

        $created = $this->actingAs($admin)->postJson(route('marketing.customers.store'), [
            'name' => 'New Prospect Co',
            'lifecycle_status' => CustomerMarketingProfile::STATUS_POTENTIAL,
            'country_id' => $unitedStates->id,
            'city' => 'Miami',
            'state_province' => 'Florida',
            'post_code' => '33101',
            'street_address' => '400 Aviation Way',
            'company_notes' => 'Prefers email follow-up.',
            'company_type_id' => $companyType->id,
            'segment_id' => $segment->id,
            'aircraft_ids' => [$plane->id],
        ]);

        $created->assertCreated()
            ->assertJsonPath('customer.name', 'New Prospect Co')
            ->assertJsonPath('customer.profile.lifecycle_status', CustomerMarketingProfile::STATUS_POTENTIAL);

        $createdCustomerId = $created->json('customer.id');

        $this->assertDatabaseHas('customers', [
            'id' => $createdCustomerId,
            'name' => 'New Prospect Co',
        ]);
        $this->assertDatabaseHas('customer_marketing_profiles', [
            'customer_id' => $createdCustomerId,
            'lifecycle_status' => CustomerMarketingProfile::STATUS_POTENTIAL,
            'country_id' => $unitedStates->id,
            'country' => 'United States',
            'city' => 'Miami',
            'state_province' => 'Florida',
            'post_code' => '33101',
            'street_address' => '400 Aviation Way',
            'company_notes' => 'Prefers email follow-up.',
        ]);
    }

    public function test_marketing_customer_workorders_endpoint_filters_columns_and_global_search(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->grantMarketingAccess($admin);

        $customer = $this->createCustomer(['name' => 'WO Filter Customer']);
        $otherCustomer = $this->createCustomer(['name' => 'Other WO Customer']);
        CustomerMarketingProfile::query()->create([
            'customer_id' => $customer->id,
            'lifecycle_status' => CustomerMarketingProfile::STATUS_EXISTING,
            'terms_label' => 'NET 45',
        ]);

        $repair = $this->createInstruction(['name' => 'Repair']);
        $overhaul = $this->createInstruction(['name' => 'Overhaul']);
        $selectedUnit = $this->createUnit([
            'part_number' => 'PN-FILTER-100',
            'name' => 'Landing Gear Assembly',
            'description' => 'Main landing gear',
        ]);
        $otherUnit = $this->createUnit([
            'part_number' => 'PN-OTHER-200',
            'name' => 'Hydraulic Pump',
            'description' => 'Pump description',
        ]);

        $matching = $this->createWorkorder([
            'customer_id' => $customer->id,
            'unit_id' => $selectedUnit->id,
            'instruction_id' => $repair->id,
            'number' => 321001,
            'customer_po' => 'RO-FILTER-900',
            'serial_number' => 'SER-FILTER-001',
            'description' => 'Fallback description',
            'open_at' => '2026-05-01 08:00:00',
            'wo_terms' => 'NET 60',
            'wo_estimate_amount' => '12345.00',
            'wo_estimate_date' => '2026-05-01',
            'approve_at' => '2026-05-10 08:00:00',
        ]);

        $waiting = $this->createWorkorder([
            'customer_id' => $customer->id,
            'unit_id' => $otherUnit->id,
            'instruction_id' => $overhaul->id,
            'number' => 321002,
            'customer_po' => 'RO-OTHER-900',
            'serial_number' => '00235',
            'description' => 'Other description',
            'open_at' => '2026-06-01 08:00:00',
            'approve_at' => null,
        ]);

        $complete = $this->createWorkorder([
            'customer_id' => $customer->id,
            'unit_id' => $otherUnit->id,
            'instruction_id' => $repair->id,
            'number' => 321003,
            'customer_po' => 'RO-COMPLETE-900',
            'serial_number' => '00197',
            'description' => 'Complete description',
            'open_at' => '2026-07-01 08:00:00',
            'approve_at' => '2026-07-02 08:00:00',
        ]);

        $completedGeneralTask = GeneralTask::query()->create([
            'name' => 'Marketing completed',
            'sort_order' => 1,
        ]);
        $completedTask = Task::query()->create([
            'name' => 'Completed',
            'general_task_id' => $completedGeneralTask->id,
        ]);
        Main::query()->create([
            'user_id' => $admin->id,
            'workorder_id' => $complete->id,
            'general_task_id' => $completedGeneralTask->id,
            'task_id' => $completedTask->id,
            'date_finish' => '2026-07-05',
            'ignore_row' => false,
        ]);

        $this->createWorkorder([
            'customer_id' => $otherCustomer->id,
            'unit_id' => $selectedUnit->id,
            'instruction_id' => $repair->id,
            'number' => 321004,
            'serial_number' => 'SER-FILTER-OTHER-CUSTOMER',
        ]);

        $this->actingAs($admin)->getJson(route('marketing.customers.workorders', [
            'customer' => $customer,
            'wo_q' => 'SER-FILTER',
        ]))
            ->assertOk()
            ->assertJsonPath('items.0.number_label', 'W321001')
            ->assertJsonCount(1, 'items');

        $this->actingAs($admin)->getJson(route('marketing.customers.workorders', [
            'customer' => $customer,
            'wo_part' => 'PN-FILTER',
        ]))
            ->assertOk()
            ->assertJsonPath('items.0.id', $matching->id)
            ->assertJsonCount(1, 'items');

        $serialResponse = $this->actingAs($admin)->getJson(route('marketing.customers.workorders', [
            'customer' => $customer,
            'wo_serial' => '001',
        ]));

        $serialResponse->assertOk()
            ->assertJsonPath('items.0.id', $complete->id)
            ->assertJsonPath('items.0.serial_number', '00197')
            ->assertJsonCount(2, 'items');
        $this->assertStringNotContainsString('00235', $serialResponse->getContent());

        $this->actingAs($admin)->getJson(route('marketing.customers.workorders', [
            'customer' => $customer,
            'wo_status' => 'complete',
        ]))
            ->assertOk()
            ->assertJsonPath('items.0.id', $complete->id)
            ->assertJsonPath('items.0.status', 'Complete')
            ->assertJsonCount(1, 'items');

        $this->actingAs($admin)->getJson(route('marketing.customers.workorders', [
            'customer' => $customer,
            'wo_terms' => 'NET 60',
        ]))
            ->assertOk()
            ->assertJsonPath('items.0.id', $matching->id)
            ->assertJsonPath('items.0.terms', 'NET 60')
            ->assertJsonCount(1, 'items');

        $this->actingAs($admin)->getJson(route('marketing.customers.workorders', [
            'customer' => $customer,
            'wo_estimate' => '12345',
        ]))
            ->assertOk()
            ->assertJsonPath('items.0.id', $matching->id)
            ->assertJsonPath('items.0.estimate_amount.value', '12345.00')
            ->assertJsonPath('items.0.estimate_amount.display', '$12,345')
            ->assertJsonCount(1, 'items');

        $this->actingAs($admin)->getJson(route('marketing.customers.workorders', [
            'customer' => $customer,
            'wo_estimate_date' => '01/May/2026',
        ]))
            ->assertOk()
            ->assertJsonPath('items.0.id', $matching->id)
            ->assertJsonCount(1, 'items');

        $this->actingAs($admin)->getJson(route('marketing.customers.workorders', [
            'customer' => $customer,
            'wo_status' => 'waiting',
        ]))
            ->assertOk()
            ->assertJsonPath('items.0.id', $waiting->id)
            ->assertJsonPath('items.0.status', 'Waiting Approval')
            ->assertJsonCount(1, 'items');

    }

    public function test_marketing_workorder_sales_fields_can_be_updated(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->grantMarketingAccess($admin);

        $customer = $this->createCustomer(['name' => 'Sales Fields Customer']);
        $workorder = $this->createWorkorder([
            'customer_id' => $customer->id,
            'number' => 107777,
        ]);

        $response = $this->actingAs($admin)->patchJson(route('marketing.workorders.sales-fields.update', $workorder), [
            'wo_terms' => 'NET 30',
            'wo_estimate_amount' => '$12,345',
            'sales_invoice_amount' => '$15,453',
            'sales_invoice_date' => '29/jun/2026',
            'shipping_shipment_at' => '01/jul/2026',
            'shipping_awb_no' => '1565df16565',
            'estimate_date' => '30/jun/2026',
        ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('workorder.id', $workorder->id)
            ->assertJsonPath('workorder.terms', 'NET 30')
            ->assertJsonPath('workorder.estimate_amount.value', '12345.00')
            ->assertJsonPath('workorder.estimate_amount.display', '$12,345')
            ->assertJsonPath('workorder.estimate_date.iso', '2026-06-30')
            ->assertJsonPath('workorder.estimate_date.display', '30/Jun/2026')
            ->assertJsonPath('workorder.sales_invoice_amount.value', '15453.00')
            ->assertJsonPath('workorder.sales_invoice_amount.display', '$15,453')
            ->assertJsonPath('workorder.sales_invoice_date.iso', '2026-06-29')
            ->assertJsonPath('workorder.sales_invoice_date.display', '29/Jun/2026')
            ->assertJsonPath('workorder.shipping_shipment_at.iso', '2026-07-01')
            ->assertJsonPath('workorder.shipping_shipment_at.display', '01/Jul/2026')
            ->assertJsonPath('workorder.shipping_awb_no', '1565df16565');

        $this->assertDatabaseHas('workorders', [
            'id' => $workorder->id,
            'wo_terms' => 'NET 30',
            'wo_estimate_amount' => '12345.00',
            'wo_estimate_date' => '2026-06-30',
            'sales_invoice_amount' => '15453.00',
            'sales_invoice_date' => '2026-06-29',
            'shipping_shipment_at' => '2026-07-01',
            'shipping_awb_no' => '1565df16565',
        ]);
    }

    public function test_marketing_managers_can_share_private_workorder_files_and_mark_them_read(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-14 10:30:00'));
        Notification::fake();
        File::cleanDirectory(base_path('codex-test-runtime/disks/private'));

        try {
            $uploader = $this->createUserWithRole('Admin');
            $recipient = $this->createUserWithRole('Manager');
            $this->grantMarketingAccess($uploader);
            $this->grantMarketingAccess($recipient);

            $customer = $this->createCustomer(['name' => 'Manager File Customer']);
            $workorder = $this->createWorkorder([
                'customer_id' => $customer->id,
                'number' => 107781,
            ]);

            $upload = $this->actingAs($uploader)->post(route('marketing.workorders.files.store', $workorder), [
                'files' => [$this->makeUploadedFile('customer-approval.pdf', '%PDF-1.4 approval', 'application/pdf')],
                'category' => 'customer_approval',
                'display_name' => 'Signed Customer Approval.pdf',
                'comment' => 'Approved estimate received.',
                'recipient_ids' => [$recipient->id],
                'send_email' => '0',
            ], ['Accept' => 'application/json']);

            $upload->assertCreated()
                ->assertJsonPath('manager_files.0.display_name', 'Signed Customer Approval.pdf')
                ->assertJsonPath('manager_files.0.category_label', 'Customer Approval')
                ->assertJsonPath('manager_files.0.uploaded_at', '14/Jul/2026 10:30')
                ->assertJsonPath('manager_files.0.notification_label', 'In-app only')
                ->assertJsonPath('summary.manager_count', 1);

            $marketingFile = MarketingWoFile::query()->firstOrFail();
            $this->assertSame('private', $marketingFile->media->disk);
            $this->assertDatabaseHas('marketing_wo_file_recipients', [
                'marketing_wo_file_id' => $marketingFile->id,
                'user_id' => $recipient->id,
                'email_requested' => false,
            ]);

            Notification::assertSentTo($recipient, NewMessageNotification::class, function (NewMessageNotification $notification) use ($workorder): bool {
                return $notification->event === 'uploaded'
                    && $notification->type === 'marketing_file'
                    && str_contains($notification->text, 'W' . $workorder->number);
            });

            $this->actingAs($recipient)->getJson(route('marketing.customers.workorders', $customer))
                ->assertOk()
                ->assertJsonPath('items.0.marketing_file_count', 1)
                ->assertJsonPath('items.0.marketing_unread_file_count', 1);

            $this->actingAs($recipient)->getJson(route('marketing.workorders.files.index', $workorder))
                ->assertOk()
                ->assertJsonPath('manager_files.0.id', $marketingFile->id)
                ->assertJsonPath('summary.unread_count', 0);

            $this->assertDatabaseHas('marketing_wo_file_reads', [
                'marketing_wo_file_id' => $marketingFile->id,
                'user_id' => $recipient->id,
                'read_at' => '2026-07-14 10:30:00',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_marketing_workorder_file_email_is_optional_and_sent_by_scheduler_command(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-14 10:40:00'));
        Notification::fake();
        Mail::fake();
        File::cleanDirectory(base_path('codex-test-runtime/disks/private'));

        try {
            $uploader = $this->createUserWithRole('Admin');
            $recipient = $this->createUserWithRole('Manager');
            $this->grantMarketingAccess($uploader);
            $this->grantMarketingAccess($recipient);

            $customer = $this->createCustomer(['name' => 'Manager File Email Customer']);
            $workorder = $this->createWorkorder([
                'customer_id' => $customer->id,
                'number' => 107782,
            ]);

            $this->actingAs($uploader)->post(route('marketing.workorders.files.store', $workorder), [
                'files' => [$this->makeUploadedFile('estimate.pdf', '%PDF-1.4 estimate', 'application/pdf')],
                'category' => 'estimate',
                'comment' => 'Updated commercial estimate.',
                'recipient_ids' => [$recipient->id],
                'send_email' => '1',
            ], ['Accept' => 'application/json'])->assertCreated();

            $marketingFile = MarketingWoFile::query()->firstOrFail();
            $outbox = $marketingFile->recipients()->firstOrFail();
            $this->assertTrue($outbox->email_requested);
            $this->assertNull($outbox->email_sent_at);

            $this->artisan('marketing:send-wo-file-emails')->assertExitCode(0);

            Mail::assertSent(MarketingWoFileMail::class, function (MarketingWoFileMail $mail) use ($marketingFile, $recipient): bool {
                return $mail->marketingFile->is($marketingFile) && $mail->hasTo($recipient->email);
            });
            $this->assertNotNull($outbox->fresh()->email_sent_at);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_marketing_estimate_date_update_queues_email_when_date_appears(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-30 08:15:00'));

        try {
            $admin = $this->createUserWithRole('Admin');
            $this->grantMarketingAccess($admin);

            ProjectSetting::setMarketingWoEstimateEmailSettings(['sales@example.test'], 3);

            $customer = $this->createCustomer(['name' => 'Estimate Alert Customer']);
            $workorder = $this->createWorkorder([
                'customer_id' => $customer->id,
                'number' => 107778,
                'open_at' => '2026-06-15',
                'wo_estimate_date' => null,
            ]);

            $response = $this->actingAs($admin)->patchJson(route('marketing.workorders.sales-fields.update', $workorder), [
                'estimate_date' => '02/jul/2026',
            ]);

            $response->assertOk()
                ->assertJsonPath('workorder.estimate_date.iso', '2026-07-02')
                ->assertJsonPath('workorder.estimate_date.display', '02/Jul/2026');

            $this->assertDatabaseHas('marketing_wo_estimate_notifications', [
                'workorder_id' => $workorder->id,
                'customer_id' => $customer->id,
                'estimate_date' => '2026-07-02',
                'due_at' => '2026-07-05 00:00:00',
                'sent_at' => null,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_marketing_wo_estimate_date_email_command_sends_due_mail(): void
    {
        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-06-30 07:20:00'));

        try {
            ProjectSetting::setMarketingWoEstimateEmailSettings([
                'sales@example.test',
                'manager@example.test',
            ], 0);

            $customer = $this->createCustomer(['name' => 'Email Alert Customer']);
            $workorder = $this->createWorkorder([
                'customer_id' => $customer->id,
                'number' => 107779,
                'open_at' => '2026-06-30',
                'wo_terms' => 'NET 15',
                'wo_estimate_amount' => '4200.00',
                'wo_estimate_date' => '2026-06-30',
                'customer_po' => 'RO-107779',
            ]);

            $notification = MarketingWoEstimateNotification::query()->create([
                'workorder_id' => $workorder->id,
                'customer_id' => $customer->id,
                'estimate_date' => '2026-06-30',
                'triggered_at' => now()->subHour(),
                'due_at' => now()->subMinute(),
            ]);

            $this->artisan('marketing:send-wo-estimate-date-emails')->assertExitCode(0);

            Mail::assertSent(MarketingWoEstimateDateMail::class, function (MarketingWoEstimateDateMail $mail) use ($notification) {
                return $mail->notification->is($notification) && $mail->hasTo('sales@example.test');
            });
            Mail::assertSent(MarketingWoEstimateDateMail::class, function (MarketingWoEstimateDateMail $mail) use ($notification) {
                return $mail->notification->is($notification) && $mail->hasTo('manager@example.test');
            });

            $notification->refresh();
            $this->assertNotNull($notification->sent_at);
            $this->assertSame(['sales@example.test', 'manager@example.test'], $notification->recipients);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_marketing_wo_estimate_date_email_skips_workorder_that_is_no_longer_waiting_approval(): void
    {
        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-07-05 07:20:00'));

        try {
            ProjectSetting::setMarketingWoEstimateEmailSettings(['sales@example.test'], 3);

            $customer = $this->createCustomer(['name' => 'Approved Estimate Customer']);
            $workorder = $this->createWorkorder([
                'customer_id' => $customer->id,
                'number' => 107780,
                'open_at' => '2026-06-30',
                'wo_estimate_date' => '2026-07-02',
                'approve_at' => '2026-07-04 12:00:00',
            ]);

            $notification = MarketingWoEstimateNotification::query()->create([
                'workorder_id' => $workorder->id,
                'customer_id' => $customer->id,
                'estimate_date' => '2026-07-02',
                'triggered_at' => now()->subDays(3),
                'due_at' => now()->subMinute(),
            ]);

            $this->artisan('marketing:send-wo-estimate-date-emails')->assertExitCode(0);

            Mail::assertNothingSent();
            $this->assertDatabaseMissing('marketing_wo_estimate_notifications', [
                'id' => $notification->id,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_marketing_customer_sales_report_endpoint_returns_selected_customer_workorders(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->grantMarketingAccess($admin);

        $customer = $this->createCustomer(['name' => 'Sales Report Customer']);
        $otherCustomer = $this->createCustomer(['name' => 'Other Sales Customer']);
        $plane = $this->createPlane(['type' => 'E170']);
        $manual = $this->createManual(['planes_id' => $plane->id]);
        $unit = $this->createUnit([
            'manual_id' => $manual->id,
            'part_number' => '2309-2200-153',
            'description' => 'MLG Shock Strut',
        ]);

        $this->createWorkorder([
            'customer_id' => $customer->id,
            'unit_id' => $unit->id,
            'number' => 107548,
            'serial_number' => 'DL5263',
            'description' => 'MLG Shock Strut',
            'open_at' => '2026-05-01 08:00:00',
        ]);

        $this->createWorkorder([
            'customer_id' => $otherCustomer->id,
            'unit_id' => $unit->id,
            'number' => 107549,
            'serial_number' => 'DL5264',
            'description' => 'Other Customer Unit',
            'open_at' => '2026-05-01 08:00:00',
        ]);

        $this->mock(SalesReportQuantumInvoiceProvider::class, function ($mock): void {
            $mock->shouldReceive('fetch')->once()->andReturn([
                'available' => false,
                'warning' => 'Quantum unavailable in test.',
                'items' => [],
            ]);
        });

        $response = $this->actingAs($admin)->getJson(route('marketing.customers.sales-report', [
            'customer' => $customer,
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
        ]));

        $response->assertOk()
            ->assertJsonPath('report_type', 'customer')
            ->assertJsonPath('rows.0.company', 'Sales Report Customer')
            ->assertJsonPath('rows.0.aircraft_type', 'E170')
            ->assertJsonPath('rows.0.wo_number', 'W107548')
            ->assertJsonPath('rows.0.part_number', '2309-2200-153')
            ->assertJsonPath('rows.0.serial_number', 'DL5263')
            ->assertJsonPath('rows.0.description', 'MLG Shock Strut');

        $this->assertStringNotContainsString('W107549', $response->getContent());
    }

    public function test_marketing_aircraft_sales_report_endpoint_returns_selected_aircraft_workorders(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->grantMarketingAccess($admin);

        $selectedPlane = $this->createPlane(['type' => 'E170']);
        $otherPlane = $this->createPlane(['type' => 'CRJ900']);
        $selectedManual = $this->createManual(['planes_id' => $selectedPlane->id]);
        $otherManual = $this->createManual(['planes_id' => $otherPlane->id]);
        $selectedUnit = $this->createUnit([
            'manual_id' => $selectedManual->id,
            'part_number' => '2309-2200-154',
            'description' => 'MLG Shock Strut',
        ]);
        $otherUnit = $this->createUnit([
            'manual_id' => $otherManual->id,
            'part_number' => '999-OTHER',
            'description' => 'Other Plane Unit',
        ]);

        $customer = $this->createCustomer(['name' => 'Jazz Aviation']);
        $secondCustomer = $this->createCustomer(['name' => 'Regional One']);

        $this->createWorkorder([
            'customer_id' => $customer->id,
            'unit_id' => $selectedUnit->id,
            'number' => 107560,
            'serial_number' => 'DL5264',
            'description' => 'MLG Shock Strut',
            'open_at' => '2026-03-01 08:00:00',
        ]);

        $this->createWorkorder([
            'customer_id' => $secondCustomer->id,
            'unit_id' => $selectedUnit->id,
            'number' => 107561,
            'serial_number' => 'DL5265',
            'description' => 'NLG Shock Strut',
            'open_at' => '2026-03-02 08:00:00',
        ]);

        $this->createWorkorder([
            'customer_id' => $customer->id,
            'unit_id' => $otherUnit->id,
            'number' => 107562,
            'serial_number' => 'OTHER-SN',
            'description' => 'Should Not Show',
            'open_at' => '2026-03-03 08:00:00',
        ]);

        $this->mock(SalesReportQuantumInvoiceProvider::class, function ($mock): void {
            $mock->shouldReceive('fetch')->once()->andReturn([
                'available' => false,
                'warning' => 'Quantum unavailable in test.',
                'items' => [],
            ]);
        });

        $response = $this->actingAs($admin)->getJson(route('marketing.sales-report.aircraft', [
            'plane_id' => $selectedPlane->id,
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
        ]));

        $response->assertOk()
            ->assertJsonPath('report_type', 'component')
            ->assertJsonPath('rows.0.aircraft_type', 'E170')
            ->assertJsonPath('rows.0.company', 'Jazz Aviation')
            ->assertJsonPath('rows.0.wo_number', 'W107560')
            ->assertJsonPath('rows.1.company', 'Regional One')
            ->assertJsonPath('rows.1.wo_number', 'W107561');

        $this->assertStringNotContainsString('W107562', $response->getContent());
        $this->assertStringNotContainsString('Should Not Show', $response->getContent());
    }

    public function test_marketing_changes_are_logged_with_human_readable_values(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->grantMarketingAccess($admin);

        $customer = $this->createCustomer(['name' => 'Readable Logs Inc']);
        $mro = MarketingCompanyType::query()->firstOrCreate(['name' => 'MRO'], ['sort_order' => 10]);
        $oem = MarketingCompanyType::query()->firstOrCreate(['name' => 'OEM'], ['sort_order' => 20]);
        $regional = MarketingSegment::query()->firstOrCreate(['name' => 'Regional'], ['sort_order' => 10]);
        $business = MarketingSegment::query()->firstOrCreate(['name' => 'Business'], ['sort_order' => 20]);
        $canada = Country::query()->firstOrCreate(['alpha2' => 'CA'], ['name' => 'Canada', 'sort_order' => 10, 'active' => true]);
        $atr = $this->createPlane(['type' => 'ATR-42']);
        $cl = $this->createPlane(['type' => 'CL601']);

        $this->actingAs($admin)->patchJson(route('marketing.customers.profile.update', $customer), [
            'name' => 'Readable Logs Canada',
            'lifecycle_status' => CustomerMarketingProfile::STATUS_EXISTING,
            'country_id' => $canada->id,
            'city' => 'Toronto',
            'state_province' => 'Ontario',
            'street_address' => '100 King Street',
            'company_notes' => 'Initial instruction set.',
            'company_type_id' => $mro->id,
            'segment_id' => $regional->id,
            'terms_label' => 'NET 30',
            'aircraft_ids' => [$atr->id],
        ])->assertOk();

        $this->actingAs($admin)->patchJson(route('marketing.customers.profile.update', $customer->fresh()), [
            'name' => 'Readable Logs Canada',
            'lifecycle_status' => CustomerMarketingProfile::STATUS_POTENTIAL,
            'country_id' => $canada->id,
            'city' => 'Montreal',
            'state_province' => 'Quebec',
            'street_address' => '200 Rue Saint-Jacques',
            'company_notes' => 'Route invoices to accounting.',
            'company_type_id' => $oem->id,
            'segment_id' => $business->id,
            'terms_label' => 'Pre-Payment',
            'aircraft_ids' => [$atr->id, $cl->id],
        ])->assertOk();

        $activity = Activity::query()
            ->where('log_name', 'marketing')
            ->where('description', 'Marketing company updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $props = $activity->properties->toArray();

        $this->assertSame('Readable Logs Canada', $props['customer']);
        $this->assertSame('MRO', $props['old']['type']);
        $this->assertSame('OEM', $props['new']['type']);
        $this->assertSame('Regional', $props['old']['segment']);
        $this->assertSame('Business', $props['new']['segment']);
        $this->assertSame('Montreal', $props['new']['city']);
        $this->assertSame('Quebec', $props['new']['state/province']);
        $this->assertSame('200 Rue Saint-Jacques', $props['new']['street address']);
        $this->assertSame('Route invoices to accounting.', $props['new']['company notes']);
        $this->assertSame('ATR-42', $props['old']['aircraft']);
        $this->assertSame('ATR-42, CL601', $props['new']['aircraft']);
        $this->assertSame('Pre-Payment', $props['new']['terms']);
    }

    public function test_marketing_contact_and_note_create_delete_actions_are_logged(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->grantMarketingAccess($admin);

        $customer = $this->createCustomer(['name' => 'Audit Trail Customer']);

        $contactResponse = $this->actingAs($admin)->postJson(route('marketing.contacts.store', $customer), [
            'first_name' => 'Jane',
            'last_name' => 'Buyer',
            'position' => 'Purchasing',
            'email' => 'jane.buyer@example.test',
            'phone' => '555-0100',
            'is_primary' => true,
        ]);

        $contactResponse->assertCreated()
            ->assertJsonPath('contact.full_name', 'Jane Buyer');
        $contactId = (int) $contactResponse->json('contact.id');

        $contactCreated = Activity::query()
            ->where('log_name', 'marketing')
            ->where('description', 'Marketing contact created')
            ->latest('id')
            ->first();

        $this->assertNotNull($contactCreated);
        $this->assertSame($admin->id, $contactCreated->causer_id);
        $this->assertSame('Audit Trail Customer', $contactCreated->properties['customer']);
        $this->assertSame('Jane Buyer <jane.buyer@example.test>', $contactCreated->properties['new']['contact']);
        $this->assertSame('jane.buyer@example.test', $contactCreated->properties['new']['email']);

        $noteResponse = $this->actingAs($admin)->postJson(route('marketing.notes.store', $customer), [
            'contact_id' => $contactId,
            'subject' => 'Overhaul forecast',
            'note' => 'Discussed overhaul forecast and pricing.',
            'interaction_at' => '12/may/2026',
            'follow_up_at' => '15/may/2026',
            'follow_up_status' => CustomerInteractionNote::STATUS_OPEN,
        ]);

        $noteResponse->assertCreated()
            ->assertJsonPath('note.subject', 'Overhaul forecast')
            ->assertJsonPath('note.note', 'Discussed overhaul forecast and pricing.');
        $noteId = (int) $noteResponse->json('note.id');

        $this->assertDatabaseHas('customer_interaction_notes', [
            'id' => $noteId,
            'subject' => 'Overhaul forecast',
        ]);

        $noteCreated = Activity::query()
            ->where('log_name', 'marketing')
            ->where('description', 'Marketing note created')
            ->latest('id')
            ->first();

        $this->assertNotNull($noteCreated);
        $this->assertSame($admin->id, $noteCreated->causer_id);
        $this->assertSame('Audit Trail Customer', $noteCreated->properties['customer']);
        $this->assertSame('Overhaul forecast', $noteCreated->properties['new']['subject line']);
        $this->assertSame('Discussed overhaul forecast and pricing.', $noteCreated->properties['new']['note']);
        $this->assertSame('Jane Buyer <jane.buyer@example.test>', $noteCreated->properties['new']['contact']);

        $this->actingAs($admin)
            ->deleteJson(route('marketing.notes.destroy', $noteId))
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseMissing('customer_interaction_notes', ['id' => $noteId]);

        $noteDeleted = Activity::query()
            ->where('log_name', 'marketing')
            ->where('description', 'Marketing note deleted')
            ->latest('id')
            ->first();

        $this->assertNotNull($noteDeleted);
        $this->assertSame($admin->id, $noteDeleted->causer_id);
        $this->assertSame('Audit Trail Customer', $noteDeleted->properties['customer']);
        $this->assertSame('Overhaul forecast', $noteDeleted->properties['old']['subject line']);
        $this->assertSame('Discussed overhaul forecast and pricing.', $noteDeleted->properties['old']['note']);

        $this->actingAs($admin)
            ->deleteJson(route('marketing.contacts.destroy', $contactId))
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseMissing('customer_contacts', ['id' => $contactId]);

        $contactDeleted = Activity::query()
            ->where('log_name', 'marketing')
            ->where('description', 'Marketing contact deleted')
            ->latest('id')
            ->first();

        $this->assertNotNull($contactDeleted);
        $this->assertSame($admin->id, $contactDeleted->causer_id);
        $this->assertSame('Audit Trail Customer', $contactDeleted->properties['customer']);
        $this->assertSame('Jane Buyer <jane.buyer@example.test>', $contactDeleted->properties['old']['contact']);
        $this->assertSame('jane.buyer@example.test', $contactDeleted->properties['old']['email']);
    }

    public function test_marketing_contacts_store_update_and_sort_by_contact_type(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->grantMarketingAccess($admin);

        $customer = $this->createCustomer(['name' => 'Contact Type Customer']);
        $other = CustomerContact::query()->create([
            'customer_id' => $customer->id,
            'first_name' => 'Other',
            'contact_type' => 'Other',
        ]);
        CustomerContact::query()->create([
            'customer_id' => $customer->id,
            'first_name' => 'Invoice',
            'contact_type' => 'Invoices',
        ]);

        $storeResponse = $this->actingAs($admin)->postJson(route('marketing.contacts.store', $customer), [
            'first_name' => 'Estimate',
            'last_name' => 'Lead',
            'position' => 'Purchasing',
            'email' => 'estimate@example.test',
            'email_2' => 'estimate.alt@example.test',
            'phone' => '416 555 1000',
            'cell_phone' => '647 555 1000',
            'contact_type' => 'WO Estimates',
        ]);

        $storeResponse->assertCreated()
            ->assertJsonPath('contact.email_2', 'estimate.alt@example.test')
            ->assertJsonPath('contact.cell_phone', '647 555 1000')
            ->assertJsonPath('contact.contact_type', 'WO Estimates');

        $this->assertSame(
            ['WO Estimates', 'Invoices', 'Other'],
            array_column($storeResponse->json('customer.contacts'), 'contact_type')
        );

        $updateResponse = $this->actingAs($admin)->patchJson(route('marketing.contacts.update', $other), [
            'first_name' => 'Estimate Invoice',
            'contact_type' => 'WO Estimates/ Invoices',
        ]);

        $updateResponse->assertOk()
            ->assertJsonPath('contact.first_name', 'Estimate Invoice')
            ->assertJsonPath('contact.contact_type', 'WO Estimates/ Invoices');

        $this->assertDatabaseHas('customer_contacts', [
            'id' => $other->id,
            'first_name' => 'Estimate Invoice',
            'contact_type' => 'WO Estimates/ Invoices',
        ]);

        $this->assertSame(
            ['WO Estimates', 'WO Estimates/ Invoices', 'Invoices'],
            array_column($updateResponse->json('customer.contacts'), 'contact_type')
        );
    }

    public function test_marketing_contact_primary_flag_can_be_moved_to_another_contact(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->grantMarketingAccess($admin);

        $customer = $this->createCustomer(['name' => 'Primary Contact Customer']);
        $currentPrimary = CustomerContact::query()->create([
            'customer_id' => $customer->id,
            'first_name' => 'Current',
            'is_primary' => true,
        ]);
        $nextPrimary = CustomerContact::query()->create([
            'customer_id' => $customer->id,
            'first_name' => 'Next',
            'is_primary' => false,
        ]);

        $response = $this->actingAs($admin)->patchJson(route('marketing.contacts.update', $nextPrimary), [
            'is_primary' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('contact.id', $nextPrimary->id)
            ->assertJsonPath('contact.is_primary', true);

        $this->assertDatabaseHas('customer_contacts', [
            'id' => $nextPrimary->id,
            'is_primary' => true,
        ]);
        $this->assertDatabaseHas('customer_contacts', [
            'id' => $currentPrimary->id,
            'is_primary' => false,
        ]);
        $this->assertSame(
            $nextPrimary->id,
            $response->json('customer.primary_contact.id')
        );
        $this->assertSame(
            [$currentPrimary->id, $nextPrimary->id],
            array_column($response->json('customer.contacts'), 'id')
        );
    }

    public function test_marketing_note_follow_up_command_sends_due_notifications(): void
    {
        Notification::fake();

        $admin = $this->createUserWithRole('Admin');
        $author = $this->createUserWithRole('Manager', ['name' => 'Note Author']);
        $otherManager = $this->createUserWithRole('Manager', ['name' => 'Other Manager']);
        $sales = $this->createUserWithRole('Sales');
        $customer = $this->createCustomer(['name' => 'Jazz Aviation LP']);

        $this->grantMarketingAccess($admin);
        $this->grantMarketingAccess($author);

        $note = CustomerInteractionNote::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $author->id,
            'note' => 'Call about ERJ units.',
            'interaction_at' => now()->subDay()->toDateString(),
            'follow_up_at' => now()->toDateString(),
            'follow_up_status' => CustomerInteractionNote::STATUS_OPEN,
        ]);

        $this->artisan('marketing:send-follow-ups')->assertExitCode(0);

        Notification::assertSentTo($admin, NewMessageNotification::class);
        Notification::assertSentTo($author, NewMessageNotification::class);
        Notification::assertNotSentTo($otherManager, NewMessageNotification::class);
        Notification::assertNotSentTo($sales, NewMessageNotification::class);

        $this->assertNotNull($note->fresh()->reminder_sent_at);
    }

    private function grantMarketingAccess(User $user): void
    {
        UserFeatureAccess::query()->firstOrCreate([
            'feature_key' => 'marketing',
            'user_id' => $user->id,
        ]);
    }
}
