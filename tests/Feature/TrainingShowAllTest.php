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

    protected function setUp(): void
    {
        parent::setUp();
        // EnsureAuthSessionVersion разлогинивает запросы без версии в сессии;
        // runningUnitTests-страховка нестабильна на первом буте процесса.
        $this->withSession([\App\Http\Middleware\EnsureAuthSessionVersion::SESSION_KEY => 1]);
    }

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
            'show_in_training_matrix' => true,
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

        // Смотрим глазами техника: у него нет модалки Personnel,
        // где имена всех со stamp встречаются по определению.
        $response = $this->actingAs($technician)->get(route('trainings.showAll'));

        $response->assertOk();
        $response->assertSee($technician->name);
        $response->assertSee('15/May/2026');
        $response->assertDontSee($systemAdmin->name);
        $response->assertDontSee('01/Jan/2024');
    }

    public function test_legacy_training_is_shown_as_x(): void
    {
        $viewer = $this->createUserWithRole('Admin', ['stamp' => 'TV']);
        $technician = $this->createUserWithRole('Technician', [
            'name' => 'Legacy Tech ' . uniqid(),
            'stamp' => 'LX',
            'is_admin' => false,
            'show_in_training_matrix' => true,
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
            'show_in_training_matrix' => true,
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
            'show_in_training_matrix' => true,
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
        $response->assertSee($redDate->format('d/M/Y'));
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
            'show_in_training_matrix' => true,
        ]);
        $numericTech = $this->createUserWithRole('Technician', [
            'name' => 'Numeric Stamp Tech ' . uniqid(),
            'stamp' => '97',
            'is_admin' => false,
            'show_in_training_matrix' => true,
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
            'show_in_training_matrix' => true,
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
            'show_in_training_matrix' => true,
        ]);
        $manual = $this->createManual(['unit_name_training' => 'DENY-PN-' . uniqid()]);

        $response = $this->actingAs($technician)->post(route('trainings.store'), [
            'is_legacy' => 1,
            'legacy_manuals_ids' => [$manual->id],
        ]);

        $response->assertForbidden();
    }

    public function test_create_training_for_legacy_pair_adds_refresh_112_without_132(): void
    {
        $admin = $this->createUserWithRole('Admin', ['stamp' => 'AD']);
        $technician = $this->createUserWithRole('Technician', [
            'stamp' => 'RF',
            'is_admin' => false,
            'show_in_training_matrix' => true,
        ]);
        $manual = $this->createManual(['unit_name_training' => 'REFRESH-PN-' . uniqid()]);

        Training::query()->create([
            'user_id' => $technician->id,
            'manuals_id' => $manual->id,
            'date_training' => null,
            'form_type' => null,
            'is_legacy' => true,
        ]);

        $response = $this->actingAs($admin)->postJson(route('trainings.createTraining'), [
            'manuals_id' => [$manual->id],
            'date_training' => [now()->format('Y-m-d')],
            'form_type' => ['112'],
            'user_id' => $technician->id,
        ]);

        $response->assertOk();

        $records = Training::query()
            ->where('user_id', $technician->id)
            ->where('manuals_id', $manual->id)
            ->get();
        // legacy + новая 112; формы 132 нет — первичное обучение было в бумажную эпоху
        $this->assertSame(1, $records->where('form_type', '112')->count());
        $this->assertSame(0, $records->where('form_type', '132')->count());
    }

    public function test_admin_can_force_form_132_for_legacy_pair(): void
    {
        $admin = $this->createUserWithRole('Admin', ['stamp' => 'AD']);
        $technician = $this->createUserWithRole('Technician', [
            'stamp' => 'F2',
            'is_admin' => false,
            'show_in_training_matrix' => true,
        ]);
        $manual = $this->createManual(['unit_name_training' => 'FORCE132-PN-' . uniqid()]);

        Training::query()->create([
            'user_id' => $technician->id,
            'manuals_id' => $manual->id,
            'date_training' => null,
            'form_type' => null,
            'is_legacy' => true,
        ]);

        $response = $this->actingAs($admin)->postJson(route('trainings.createTraining'), [
            'manuals_id' => [$manual->id],
            'date_training' => [now()->format('Y-m-d')],
            'form_type' => ['112'],
            'user_id' => $technician->id,
            'create_form_132' => 1,
        ]);

        $response->assertOk();

        $records = Training::query()
            ->where('user_id', $technician->id)
            ->where('manuals_id', $manual->id)
            ->get();
        // Явный запрос (бланк потерян/перевыпуск): 112 + 132
        $this->assertSame(1, $records->where('form_type', '112')->count());
        $this->assertSame(1, $records->where('form_type', '132')->count());
    }

    public function test_admin_can_reorder_groups(): void
    {
        $admin = $this->createUserWithRole('Admin', ['stamp' => 'AD']);

        $first = TrainingCategory::query()->create(['name' => 'QA Group A ' . uniqid(), 'sort_order' => 101]);
        $second = TrainingCategory::query()->create(['name' => 'QA Group B ' . uniqid(), 'sort_order' => 102]);

        $response = $this->actingAs($admin)->post(
            route('trainings.matrixCategories.move', ['category' => $second->id]),
            ['direction' => 'up']
        );

        $response->assertRedirect();
        $this->assertSame(101, (int) $second->fresh()->sort_order);
        $this->assertSame(102, (int) $first->fresh()->sort_order);
    }

    public function test_old_training_with_reissued_form_132_still_shows_x(): void
    {
        $admin = $this->createUserWithRole('Admin', ['stamp' => 'AD']);
        $technician = $this->createUserWithRole('Technician', [
            'stamp' => 'RX',
            'is_admin' => false,
            'show_in_training_matrix' => true,
        ]);
        $manual = $this->createManual(['unit_name_training' => 'REISSUE-PN-' . uniqid()]);
        $this->createMatrixRowForManual($manual);

        $response = $this->actingAs($admin)->post(route('trainings.store'), [
            'is_legacy' => 1,
            'user_id' => $technician->id,
            'legacy_manuals_ids' => [$manual->id],
            'create_form_132' => 1,
            'form_132_date' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect();

        $records = Training::query()
            ->where('user_id', $technician->id)
            ->where('manuals_id', $manual->id)
            ->get();
        $this->assertSame(1, $records->where('is_legacy', true)->count());
        $this->assertSame(1, $records->where('form_type', '132')->count());

        // Перевыпущенная 132 — не тренинг: в матрице остаётся X, а не свежая дата
        $page = $this->actingAs($admin)->get(route('trainings.showAll'));
        $page->assertOk();
        $page->assertSee('training-x">X', false);
    }

    public function test_inactive_row_is_hidden_until_show_inactive_requested(): void
    {
        $admin = $this->createUserWithRole('Admin', ['stamp' => 'AD']);
        $manual = $this->createManual(['unit_name_training' => 'INACT-PN-' . uniqid()]);
        $row = $this->createMatrixRowForManual($manual);

        // Снимаем галку Active
        $response = $this->actingAs($admin)->post(route('trainings.matrixRows.toggleActive', ['row' => $row->id]));
        $response->assertRedirect();
        $this->assertFalse($row->fresh()->is_active);

        // По умолчанию строка скрыта, с show_inactive=1 — видна
        $this->actingAs($admin)->get(route('trainings.showAll'))
            ->assertOk()
            ->assertDontSee($row->part_number);
        $this->actingAs($admin)->get(route('trainings.showAll', ['show_inactive' => 1]))
            ->assertOk()
            ->assertSee($row->part_number);

        // Возврат в работу
        $this->actingAs($admin)->post(route('trainings.matrixRows.toggleActive', ['row' => $row->id]));
        $this->assertTrue($row->fresh()->is_active);
    }

    public function test_personnel_modal_controls_matrix_columns(): void
    {
        $admin = $this->createUserWithRole('Admin', ['stamp' => 'AD']);
        $technician = $this->createUserWithRole('Technician', [
            'name' => 'Personnel Tech ' . uniqid(),
            'stamp' => 'PL',
            'is_admin' => false,
            'show_in_training_matrix' => true,
        ]);
        $manual = $this->createManual(['unit_name_training' => 'PERS-PN-' . uniqid()]);
        $this->createMatrixRowForManual($manual);

        $this->actingAs($admin)->get(route('trainings.showAll'))->assertSee($technician->name);

        // Снимаем галку в Personnel (отправляем список без этого техника)
        $response = $this->actingAs($admin)->post(route('trainings.matrixPersonnel.update'), [
            'user_ids' => [],
        ]);
        $response->assertRedirect();

        $this->assertFalse($technician->fresh()->show_in_training_matrix);
        // Проверяем отсутствие колонки глазами другого техника: у админа имя
        // осталось бы в модалке Personnel, у самого техника — в сайдбаре.
        $otherViewer = $this->createUserWithRole('Technician', [
            'name' => 'Other Viewer ' . uniqid(),
            'stamp' => 'OV',
            'is_admin' => false,
            'show_in_training_matrix' => true,
        ]);
        $this->actingAs($otherViewer)->get(route('trainings.showAll'))->assertDontSee($technician->name);
    }

    public function test_technician_cannot_update_matrix_personnel(): void
    {
        $technician = $this->createUserWithRole('Technician', [
            'stamp' => 'NA',
            'is_admin' => false,
            'show_in_training_matrix' => true,
        ]);

        $this->actingAs($technician)
            ->post(route('trainings.matrixPersonnel.update'), ['user_ids' => []])
            ->assertForbidden();
    }

    public function test_technician_sees_only_own_column(): void
    {
        $tech1 = $this->createUserWithRole('Technician', [
            'name' => 'Own Column Tech ' . uniqid(),
            'stamp' => 'C1',
            'is_admin' => false,
            'show_in_training_matrix' => true,
        ]);
        $tech2 = $this->createUserWithRole('Technician', [
            'name' => 'Foreign Column Tech ' . uniqid(),
            'stamp' => 'C2',
            'is_admin' => false,
            'show_in_training_matrix' => true,
        ]);
        $manual = $this->createManual(['unit_name_training' => 'OWNCOL-PN-' . uniqid()]);
        $this->createMatrixRowForManual($manual);

        $response = $this->actingAs($tech1)->get(route('trainings.showAll'));

        $response->assertOk();
        $response->assertSee($tech1->name);
        $response->assertDontSee($tech2->name);
        // Техник не SCA — секции курсов ему не показываются
        $response->assertDontSee('CCAR145');
    }

    public function test_team_leader_sees_and_manages_only_his_team(): void
    {
        $leader = $this->createUserWithRole('Team Leader', [
            'stamp' => 'TL',
            'is_admin' => false,
            'show_in_training_matrix' => true,
        ]);
        $teammate = $this->createUserWithRole('Technician', [
            'name' => 'Teammate Tech ' . uniqid(),
            'stamp' => 'TM',
            'is_admin' => false,
            'show_in_training_matrix' => true,
            'team_id' => $leader->team_id,
        ]);
        $otherTeam = \App\Models\Team::query()->create(['name' => 'Other Team ' . uniqid()]);
        $outsider = $this->createUserWithRole('Technician', [
            'name' => 'Outsider Tech ' . uniqid(),
            'stamp' => 'OT',
            'is_admin' => false,
            'show_in_training_matrix' => true,
            'team_id' => $otherTeam->id,
        ]);
        $manual = $this->createManual(['unit_name_training' => 'TEAM-PN-' . uniqid()]);
        $this->createMatrixRowForManual($manual);

        $response = $this->actingAs($leader)->get(route('trainings.showAll'));
        $response->assertOk();
        $response->assertSee($teammate->name);
        $response->assertDontSee($outsider->name);

        // TL добавляет тренинг тиммату
        $this->actingAs($leader)->postJson(route('trainings.createTraining'), [
            'manuals_id' => [$manual->id],
            'date_training' => [now()->format('Y-m-d')],
            'form_type' => ['112'],
            'user_id' => $teammate->id,
        ])->assertOk();
        $this->assertTrue(Training::query()->where('user_id', $teammate->id)->where('manuals_id', $manual->id)->exists());

        // Но не чужому
        $this->actingAs($leader)->postJson(route('trainings.createTraining'), [
            'manuals_id' => [$manual->id],
            'date_training' => [now()->format('Y-m-d')],
            'form_type' => ['112'],
            'user_id' => $outsider->id,
        ]);
        $this->assertFalse(Training::query()->where('user_id', $outsider->id)->where('manuals_id', $manual->id)->exists());
    }

    public function test_manager_without_sca_flag_gets_403(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'stamp' => 'M0',
            'is_admin' => false,
            'can_sign_certificates' => false,
        ]);

        $this->actingAs($manager)->get(route('trainings.showAll'))->assertForbidden();
    }

    public function test_sca_view_shows_all_sca_trainings_and_production_view_excludes_sca_people(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'stamp' => 'M1',
            'is_admin' => false,
            'can_sign_certificates' => true,
            'show_in_training_matrix' => true,
        ]);
        $manual = $this->createManual(['unit_name_training' => 'MGRSCA-PN-' . uniqid()]);
        $row = $this->createMatrixRowForManual($manual);

        // SCA-вид: production-группы + курсы, колонка менеджера
        $scaView = $this->actingAs($manager)->get(route('trainings.showAll', ['sca' => 1]));
        $scaView->assertOk();
        $scaView->assertSee($row->part_number);
        $scaView->assertSee('CCAR145');
        $scaView->assertSee($manager->name);

        // Production-вид (глазами админа — иначе имя менеджера есть в сайдбаре):
        // SCA-людей в колонках нет, курсов нет
        $admin = $this->createUserWithRole('Admin', ['stamp' => 'A2']);
        // Смена юзера в том же тесте: сбросить сессию (AuthenticateSession) и заново посеять версию
        $this->flushSession();
        $this->withSession([\App\Http\Middleware\EnsureAuthSessionVersion::SESSION_KEY => 1]);
        $prodView = $this->actingAs($admin)->get(route('trainings.showAll'));
        $prodView->assertOk();
        $prodView->assertSee($row->part_number);
        $prodView->assertDontSee($manager->name);
        $prodView->assertDontSee('CCAR145');
    }

    public function test_course_date_is_stored_on_matrix_row(): void
    {
        $admin = $this->createUserWithRole('Admin', ['stamp' => 'AD']);
        $sca = $this->createUserWithRole('Manager', [
            'stamp' => 'S1',
            'is_admin' => false,
            'can_sign_certificates' => true,
            'show_in_training_matrix' => true,
        ]);

        $courseRow = TrainingMatrixRow::query()
            ->whereHas('category', fn ($q) => $q->where('is_sca', true))
            ->firstOrFail();

        $this->actingAs($admin)->postJson(route('trainings.matrixCourseDate.store'), [
            'matrix_row_id' => $courseRow->id,
            'user_id' => $sca->id,
            'date_training' => now()->format('Y-m-d'),
        ])->assertOk();

        $record = Training::query()
            ->where('user_id', $sca->id)
            ->where('matrix_row_id', $courseRow->id)
            ->first();
        $this->assertNotNull($record);
        $this->assertNull($record->manuals_id);
        $this->assertNull($record->form_type);

        // Дата видна в SCA-виде
        $page = $this->actingAs($admin)->get(route('trainings.showAll', ['sca' => 1]));
        $page->assertOk();
        $page->assertSee(\Carbon\Carbon::parse($record->date_training)->format('d/M/Y'));
    }

    public function test_index_shows_training_hours_per_unit_and_total(): void
    {
        $admin = $this->createUserWithRole('Admin', ['stamp' => 'AD']);
        $technician = $this->createUserWithRole('Technician', [
            'stamp' => 'TH',
            'is_admin' => false,
        ]);
        $manual = $this->createManual([
            'unit_name_training' => 'HOURS-PN-' . uniqid(),
            'training_hours' => '13', // часов в день
        ]);

        // Первый (13×5=65) + update через 100 дней (2×5=10) + refresh через 400 дней (65)
        foreach ([['2024-01-05', '132'], ['2024-01-05', '112'], ['2024-04-14', '112'], ['2025-05-19', '112']] as [$date, $type]) {
            Training::query()->create([
                'user_id' => $technician->id,
                'manuals_id' => $manual->id,
                'date_training' => $date,
                'form_type' => $type,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('trainings.index', ['user_id' => $technician->id]));

        $response->assertOk();
        $response->assertSee('Total Hours');
        $response->assertSee('140 hrs');
        $response->assertSee('Total training hours');
        $response->assertSee('<strong>140</strong>', false);
    }

    public function test_hours_counted_for_x_pairs_when_112_exists(): void
    {
        $admin = $this->createUserWithRole('Admin', ['stamp' => 'AD']);
        $technician = $this->createUserWithRole('Technician', [
            'stamp' => 'XH',
            'is_admin' => false,
        ]);
        $manual = $this->createManual([
            'unit_name_training' => 'XHOURS-PN-' . uniqid(),
            'training_hours' => '7',
        ]);

        // Legacy-пара (X) с refresh-112: legacy без часов, 112 = 7×5 как первичный
        Training::query()->create([
            'user_id' => $technician->id,
            'manuals_id' => $manual->id,
            'date_training' => null,
            'form_type' => null,
            'is_legacy' => true,
        ]);
        Training::query()->create([
            'user_id' => $technician->id,
            'manuals_id' => $manual->id,
            'date_training' => '2020-06-05', // старше 3 лет — в матрице X, но часы считаются
            'form_type' => '112',
        ]);

        $response = $this->actingAs($admin)->get(route('trainings.index', ['user_id' => $technician->id]));

        $response->assertOk();
        $response->assertSee('35 hrs');
        $response->assertSee('<strong>35</strong>', false);
    }

    public function test_index_shows_sca_courses_section_for_sca_user_only(): void
    {
        $admin = $this->createUserWithRole('Admin', ['stamp' => 'AD']);
        $sca = $this->createUserWithRole('Manager', [
            'stamp' => 'S1',
            'is_admin' => false,
            'can_sign_certificates' => true,
        ]);
        $technician = $this->createUserWithRole('Technician', [
            'stamp' => 'TN',
            'is_admin' => false,
        ]);

        $courseRow = TrainingMatrixRow::query()
            ->whereHas('category', fn ($q) => $q->where('is_sca', true))
            ->firstOrFail();
        Training::query()->create([
            'user_id' => $sca->id,
            'manuals_id' => null,
            'matrix_row_id' => $courseRow->id,
            'date_training' => '2026-08-21',
            'form_type' => null,
        ]);

        $scaPage = $this->actingAs($admin)->get(route('trainings.index', ['user_id' => $sca->id]));
        $scaPage->assertOk();
        $scaPage->assertSee('SCA Courses');
        $scaPage->assertSee($courseRow->part_number);
        $scaPage->assertSee('21/Aug/2026');
        // Модалка истории дат курса
        $scaPage->assertSee('courseModal' . $courseRow->id, false);

        // У не-SCA сотрудника секции нет
        $techPage = $this->actingAs($admin)->get(route('trainings.index', ['user_id' => $technician->id]));
        $techPage->assertOk();
        $techPage->assertDontSee('SCA Courses');
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
