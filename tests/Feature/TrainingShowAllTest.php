<?php

namespace Tests\Feature;

use App\Models\Manual;
use App\Models\Training;
use App\Models\TrainingCategory;
use App\Models\TrainingMatrixRow;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class TrainingShowAllTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    private function createMatrixRowForManual(Manual $manual, array $rowAttributes = []): TrainingMatrixRow
    {
        $category = TrainingCategory::query()->create([
            'name' => 'QA Category ' . uniqid(),
            'sort_order' => (int) TrainingCategory::max('sort_order') + 1,
        ]);

        return TrainingMatrixRow::query()->create(array_merge([
            'training_category_id' => $category->id,
            'description' => 'QA Unit ' . uniqid(),
            'part_number' => 'QA-PN-' . uniqid(),
            'sort_order' => 1,
            'manual_id' => $manual->id,
        ], $rowAttributes));
    }

    public function test_show_all_excludes_system_admin_users_from_technician_columns(): void
    {
        $viewer = $this->createUserWithRole('Admin', [
            'name' => 'Training Viewer ' . uniqid(),
            'stamp' => 'TV',
        ]);
        $technician = $this->createUserWithRole('Technician', [
            'name' => 'Visible Technician ' . uniqid(),
            'stamp' => 'T01',
            'is_admin' => false,
        ]);
        $systemAdmin = $this->createUserWithRole('Admin', [
            'name' => 'Hidden Training Admin ' . uniqid(),
            'stamp' => 'A01',
            'is_admin' => true,
        ]);
        $manual = $this->createManual([
            'title' => 'Training Show All Manual ' . uniqid(),
            'unit_name_training' => 'TRAIN-PN-' . uniqid(),
        ]);
        $this->createMatrixRowForManual($manual);

        Training::query()->create([
            'user_id' => $technician->id,
            'manuals_id' => $manual->id,
            'date_training' => '2026-05-15',
            'form_type' => '112',
        ]);
        Training::query()->create([
            'user_id' => $systemAdmin->id,
            'manuals_id' => $manual->id,
            'date_training' => '2024-01-01',
            'form_type' => '112',
        ]);

        $response = $this->actingAs($viewer)->get(route('trainings.showAll'));

        $response->assertOk();
        $response->assertSee($technician->name);
        $response->assertSee('May-15-2026');
        $response->assertDontSee($systemAdmin->name);
        $response->assertDontSee('Jan-01-2024');
    }

    public function test_legacy_training_is_shown_as_x(): void
    {
        $viewer = $this->createUserWithRole('Admin', ['stamp' => 'TV']);
        $technician = $this->createUserWithRole('Technician', [
            'name' => 'Legacy Tech ' . uniqid(),
            'stamp' => 'LX',
            'is_admin' => false,
        ]);
        $manual = $this->createManual(['unit_name_training' => 'LEGACY-PN-' . uniqid()]);
        $this->createMatrixRowForManual($manual);

        Training::query()->create([
            'user_id' => $technician->id,
            'manuals_id' => $manual->id,
            'date_training' => null,
            'form_type' => null,
            'is_legacy' => true,
        ]);

        $response = $this->actingAs($viewer)->get(route('trainings.showAll'));

        $response->assertOk();
        $response->assertSee('Old training (no date on record)');
    }

    public function test_date_older_than_legacy_threshold_is_shown_as_x(): void
    {
        $viewer = $this->createUserWithRole('Admin', ['stamp' => 'TV']);
        $technician = $this->createUserWithRole('Technician', [
            'stamp' => 'OX',
            'is_admin' => false,
        ]);
        $manual = $this->createManual(['unit_name_training' => 'OLDX-PN-' . uniqid()]);
        $this->createMatrixRowForManual($manual);

        $oldDate = now()->subYears(4)->format('Y-m-d');
        Training::query()->create([
            'user_id' => $technician->id,
            'manuals_id' => $manual->id,
            'date_training' => $oldDate,
            'form_type' => '112',
        ]);

        $response = $this->actingAs($viewer)->get(route('trainings.showAll'));

        $response->assertOk();
        // Ячейка — «X» с датой в тултипе, а не обычная/красная дата
        $response->assertSee('refresh required (MP-20)');
        $response->assertSee('training-x">X', false);
    }

    public function test_date_older_than_red_threshold_is_marked_red(): void
    {
        $viewer = $this->createUserWithRole('Admin', ['stamp' => 'TV']);
        $technician = $this->createUserWithRole('Technician', [
            'stamp' => 'RD',
            'is_admin' => false,
        ]);
        $manual = $this->createManual(['unit_name_training' => 'RED-PN-' . uniqid()]);
        $this->createMatrixRowForManual($manual);

        $redDate = now()->subDays(400);
        Training::query()->create([
            'user_id' => $technician->id,
            'manuals_id' => $manual->id,
            'date_training' => $redDate->format('Y-m-d'),
            'form_type' => '112',
        ]);

        $response = $this->actingAs($viewer)->get(route('trainings.showAll'));

        $response->assertOk();
        $response->assertSee('training-date-old', false);
        $response->assertSee($redDate->format('M-d-Y'));
    }

    public function test_row_without_manual_is_rendered_with_no_cmm_badge(): void
    {
        $viewer = $this->createUserWithRole('Admin', ['stamp' => 'TV']);
        $partNumber = 'NOCMM-PN-' . uniqid();

        $category = TrainingCategory::query()->create([
            'name' => 'QA NoCmm Category ' . uniqid(),
            'sort_order' => (int) TrainingCategory::max('sort_order') + 1,
        ]);
        TrainingMatrixRow::query()->create([
            'training_category_id' => $category->id,
            'description' => 'Unregistered unit',
            'part_number' => $partNumber,
            'sort_order' => 1,
            'manual_id' => null,
        ]);

        $response = $this->actingAs($viewer)->get(route('trainings.showAll'));

        $response->assertOk();
        $response->assertSee($partNumber);
        $response->assertSee('no CMM');
    }

    public function test_numeric_stamps_come_before_letter_stamps(): void
    {
        $viewer = $this->createUserWithRole('Admin', ['stamp' => 'TV']);
        $letterTech = $this->createUserWithRole('Technician', [
            'name' => 'Letter Stamp Tech ' . uniqid(),
            'stamp' => 'ZZ',
            'is_admin' => false,
        ]);
        $numericTech = $this->createUserWithRole('Technician', [
            'name' => 'Numeric Stamp Tech ' . uniqid(),
            'stamp' => '97',
            'is_admin' => false,
        ]);
        $manual = $this->createManual(['unit_name_training' => 'ORDER-PN-' . uniqid()]);
        $this->createMatrixRowForManual($manual);

        $response = $this->actingAs($viewer)->get(route('trainings.showAll'));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertNotFalse(strpos($content, $numericTech->name));
        $this->assertNotFalse(strpos($content, $letterTech->name));
        $this->assertLessThan(
            strpos($content, $letterTech->name),
            strpos($content, $numericTech->name),
            'Numeric stamps must be ordered before letter stamps'
        );
    }

    public function test_admin_can_mark_old_training_without_forms(): void
    {
        $admin = $this->createUserWithRole('Admin', ['stamp' => 'AD']);
        $technician = $this->createUserWithRole('Technician', [
            'stamp' => 'LT',
            'is_admin' => false,
        ]);
        $manualA = $this->createManual(['unit_name_training' => 'LEG-A-' . uniqid()]);
        $manualB = $this->createManual(['unit_name_training' => 'LEG-B-' . uniqid()]);

        $response = $this->actingAs($admin)->post(route('trainings.store'), [
            'is_legacy' => 1,
            'user_id' => $technician->id,
            'legacy_manuals_ids' => [$manualA->id, $manualB->id],
        ]);

        $response->assertRedirect();

        foreach ([$manualA, $manualB] as $manual) {
            $this->assertDatabaseHas('trainings', [
                'user_id' => $technician->id,
                'manuals_id' => $manual->id,
                'is_legacy' => 1,
                'date_training' => null,
            ]);
        }
        $this->assertSame(0, Training::query()
            ->whereIn('manuals_id', [$manualA->id, $manualB->id])
            ->where('is_legacy', false)
            ->count());
    }

    public function test_technician_cannot_mark_old_training(): void
    {
        $technician = $this->createUserWithRole('Technician', [
            'stamp' => 'NP',
            'is_admin' => false,
        ]);
        $manual = $this->createManual(['unit_name_training' => 'DENY-PN-' . uniqid()]);

        $response = $this->actingAs($technician)->post(route('trainings.store'), [
            'is_legacy' => 1,
            'legacy_manuals_ids' => [$manual->id],
        ]);

        $response->assertForbidden();
    }

    public function test_store_no_longer_backfills_missing_yearly_trainings(): void
    {
        $admin = $this->createUserWithRole('Admin', ['stamp' => 'AD']);
        $manual = $this->createManual(['unit_name_training' => 'BACKFILL-PN-' . uniqid()]);

        $firstDate = now()->subYears(3)->startOfWeek()->addDays(4); // пятница 3 года назад

        $response = $this->actingAs($admin)->post(route('trainings.store'), [
            'manuals_id' => $manual->id,
            'date_training' => $firstDate->format('Y-m-d'),
        ]);

        $response->assertRedirect();

        // Только 132 + 112 на введённую дату; никаких догенерённых 112 за 3 пропущенных года.
        $this->assertSame(2, Training::query()->where('manuals_id', $manual->id)->count());
    }
}
