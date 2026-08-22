<?php

namespace Tests\Feature;

use App\Models\Training;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

/**
 * Вариант формы 132 определяется ОБУЧАЕМЫМ: SCA (флаг can_sign_certificates),
 * иначе Team Leader, иначе базовый. Просматривающий не влияет. Team Leader
 * не бывает SCA — тест на это сочетание лишь страхует от кривых данных.
 */
class Form132VariantTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    private const SCA_SECTION = 'REPORTS IN MAINTENACE RECORDS/FINAL RELEASE';
    private const TL_SECTION = 'SUPERVISION OF MAINTENANCE WORK';

    private function form132For(array $traineeAttributes, string $traineeRole)
    {
        $viewer = $this->createUserWithRole('Admin', ['stamp' => 'VW']);
        $trainee = $this->createUserWithRole($traineeRole, array_merge([
            'stamp' => 'TR',
            'is_admin' => false,
        ], $traineeAttributes));
        $manual = $this->createManual(['unit_name_training' => 'F132-PN-' . uniqid()]);

        $training = Training::query()->create([
            'user_id' => $trainee->id,
            'manuals_id' => $manual->id,
            'date_training' => '2026-08-21',
            'form_type' => '132',
        ]);

        return $this->actingAs($viewer)->get(route('trainings.form132', ['id' => $training->id]));
    }

    public function test_technician_form_has_no_extra_section(): void
    {
        $response = $this->form132For([], 'Technician');

        $response->assertOk();
        $response->assertDontSee(self::SCA_SECTION);
        $response->assertDontSee(self::TL_SECTION);
    }

    public function test_team_leader_form_has_supervision_section_even_for_admin_viewer(): void
    {
        $response = $this->form132For([], 'Team Leader');

        $response->assertOk();
        $response->assertSee(self::TL_SECTION);
        $response->assertDontSee(self::SCA_SECTION);
    }

    public function test_sca_flag_gives_sca_section_regardless_of_role(): void
    {
        $response = $this->form132For(['can_sign_certificates' => true], 'Manager');

        $response->assertOk();
        $response->assertSee(self::SCA_SECTION);
        $response->assertDontSee(self::TL_SECTION);
    }

    public function test_sca_overrides_team_leader_section(): void
    {
        $response = $this->form132For(['can_sign_certificates' => true], 'Team Leader');

        $response->assertOk();
        $response->assertSee(self::SCA_SECTION);
        $response->assertDontSee(self::TL_SECTION);
    }
}
