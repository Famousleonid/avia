<?php

namespace Tests\Feature;

use App\Models\ManualDimensionFigure;
use App\Models\ManualDimensionPoint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

/**
 * Covers the multi-arrow leaders (extra_anchors) on callouts / part labels and
 * the rotation (rotation_deg) of navigation areas.
 */
class PointAnchorsRotationTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    private function admin()
    {
        return $this->createUserWithRole('Admin', ['stamp' => 'AR' . random_int(1, 9999)]);
    }

    private function figure($manual): ManualDimensionFigure
    {
        return ManualDimensionFigure::query()->create([
            'manual_id'    => $manual->id,
            'figure_type'  => 'detail',
            'title'        => 'Fig ' . uniqid(),
            'image_path'   => 'test/fig.png',
            'image_width'  => 1000,
            'image_height' => 800,
            'sort_order'   => 0,
        ]);
    }

    public function test_callout_stores_extra_anchors_as_array(): void
    {
        $manual = $this->createManual();
        $fig    = $this->figure($manual);

        $resp = $this->actingAs($this->admin())->postJson(
            "/dimension-figures/{$fig->id}/points",
            [
                'point_type'   => 'measurement',
                'code'         => 'A1',
                'x_pct'        => 20, 'y_pct' => 30,
                'label_x_pct'  => 40, 'label_y_pct' => 50,
                'extra_anchors' => [
                    ['x_pct' => 60, 'y_pct' => 25],
                    ['x_pct' => 70, 'y_pct' => 35],
                ],
            ]
        );

        $resp->assertCreated();
        $point = ManualDimensionPoint::find($resp->json('id'));
        $this->assertIsArray($point->extra_anchors);
        $this->assertCount(2, $point->extra_anchors);
        $this->assertEquals(60, $point->extra_anchors[0]['x_pct']);
    }

    public function test_extra_anchor_coordinates_are_validated(): void
    {
        $manual = $this->createManual();
        $fig    = $this->figure($manual);

        $this->actingAs($this->admin())
            ->postJson("/dimension-figures/{$fig->id}/points", [
                'point_type'   => 'measurement',
                'code'         => 'A1',
                'x_pct'        => 20, 'y_pct' => 30,
                'label_x_pct'  => 40, 'label_y_pct' => 50,
                'extra_anchors' => [['x_pct' => 150, 'y_pct' => 25]],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('extra_anchors.0.x_pct');
    }

    public function test_editing_text_label_preserves_code_and_saves_anchors(): void
    {
        $manual = $this->createManual();
        $fig    = $this->figure($manual);

        $label = ManualDimensionPoint::query()->create([
            'manual_dimension_figure_id' => $fig->id,
            'point_type' => 'text',
            'code'       => 'lbl_existing',
            'x_pct' => 10, 'y_pct' => 10, 'label_x_pct' => 20, 'label_y_pct' => 20,
            'sort_order' => 0,
        ]);

        // The editor sends code=null for text points — the slug must survive.
        $this->actingAs($this->admin())
            ->patchJson("/dimension-points/{$label->id}", [
                'point_type'    => 'text',
                'code'          => null,
                'extra_anchors' => [['x_pct' => 30, 'y_pct' => 40]],
            ])
            ->assertOk();

        $fresh = $label->fresh();
        $this->assertSame('lbl_existing', $fresh->code);
        $this->assertCount(1, $fresh->extra_anchors);
    }

    public function test_area_rotation_is_saved_and_clamped(): void
    {
        $manual = $this->createManual();
        $fig    = $this->figure($manual);

        $area = ManualDimensionPoint::query()->create([
            'manual_dimension_figure_id' => $fig->id,
            'point_type' => 'navigation',
            'code'       => 'Z',
            'x_pct' => 10, 'y_pct' => 10, 'width_pct' => 20, 'height_pct' => 15,
            'sort_order' => 0,
        ]);

        $this->actingAs($this->admin())
            ->patchJson("/dimension-points/{$area->id}", ['rotation_deg' => 35.5])
            ->assertOk();
        $this->assertEqualsWithDelta(35.5, $area->fresh()->rotation_deg, 0.001);

        // out of range is rejected
        $this->actingAs($this->admin())
            ->patchJson("/dimension-points/{$area->id}", ['rotation_deg' => 400])
            ->assertStatus(422)
            ->assertJsonValidationErrors('rotation_deg');
    }
}
