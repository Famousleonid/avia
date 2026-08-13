<?php

namespace Tests\Feature;

use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class UserSelectionNameTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_selection_name_displays_given_name_before_last_name(): void
    {
        $this->assertSame('Eduard Lyfar', (new User(['name' => 'Lyfar Eduard']))->selection_name);
        $this->assertSame('Mary Jane Smith', (new User(['name' => 'Smith Mary Jane']))->selection_name);
        $this->assertSame('Alexey Baydala', (new User([
            'name' => 'Alexey Baydala',
            'selection_name_order' => 'first_last',
        ]))->selection_name);
        $this->assertSame('Volker', (new User(['name' => 'Volker']))->selection_name);
    }

    public function test_review_accounts_are_excluded_from_operational_user_queries_case_insensitively(): void
    {
        config()->set('mobile_review.accounts', [
            'appreview@aviatechnik.ca' => ['workorder_numbers' => [100500]],
            'googleview@aviatechnik.ca' => ['workorder_numbers' => [100500]],
        ]);

        $regular = $this->createUserWithRole('Technician');
        $appReview = $this->createUserWithRole('Team Leader', ['email' => 'appreview@aviatechnik.ca']);
        $googleReview = $this->createUserWithRole('Team Leader', ['email' => 'googleview@avIatechnik.ca']);

        $visibleIds = User::query()
            ->withoutReviewAccounts()
            ->whereIn('id', [$regular->id, $appReview->id, $googleReview->id])
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $this->assertSame([$regular->id], $visibleIds);
    }

    public function test_message_recipient_list_uses_and_sorts_by_selection_name(): void
    {
        $admin = $this->createUserWithRole('Admin', ['name' => 'Administrator Current']);
        $recipient = $this->createUserWithRole('Technician', [
            'name' => 'Lyfar Eduard',
            'selection_name_order' => 'last_first',
        ]);
        $firstRecipient = $this->createUserWithRole('Technician', [
            'name' => 'Zulu Aaron',
            'selection_name_order' => 'last_first',
        ]);
        $lastRecipient = $this->createUserWithRole('Technician', [
            'name' => 'Alpha Zoe',
            'selection_name_order' => 'last_first',
        ]);
        $reviewRecipient = $this->createUserWithRole('Team Leader', [
            'name' => 'Hidden Review',
            'email' => 'appreview@aviatechnik.ca',
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.messages.users'))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $recipient->id,
                'name' => 'Eduard Lyfar',
            ])
            ->assertJsonMissing(['id' => $reviewRecipient->id]);

        $sortedNames = collect($response->json())
            ->whereIn('id', [$firstRecipient->id, $recipient->id, $lastRecipient->id])
            ->pluck('name')
            ->values()
            ->all();

        $this->assertSame(['Aaron Zulu', 'Eduard Lyfar', 'Zoe Alpha'], $sortedNames);
    }

    public function test_training_forms_display_given_name_before_last_name(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $trainee = $this->createUserWithRole('Technician', [
            'name' => 'Lyfar Eduard',
            'selection_name_order' => 'last_first',
        ]);
        $manual = $this->createManual();

        foreach ([112, 132] as $formType) {
            $training = Training::query()->create([
                'user_id' => $trainee->id,
                'manuals_id' => $manual->id,
                'date_training' => now()->toDateString(),
                'form_type' => (string) $formType,
            ]);

            $this->actingAs($admin)
                ->get(route("trainings.form{$formType}", $training))
                ->assertOk()
                ->assertSee('Eduard Lyfar')
                ->assertDontSee('Lyfar Eduard');
        }
    }
}
