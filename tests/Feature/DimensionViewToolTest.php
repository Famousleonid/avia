<?php

namespace Tests\Feature;

use App\Models\ManualDimensionFigure;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

/**
 * Covers the Dimensions "View" arrow tool: a point_type='view' annotation that
 * carries a letter (code), a direction (x2/y2) and links to a child figure.
 */
class DimensionViewToolTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    private function admin()
    {
        return $this->createUserWithRole('Admin', ['stamp' => 'VW' . random_int(1, 9999)]);
    }

    private function figure($manual, string $title = 'Fig'): ManualDimensionFigure
    {
        return ManualDimensionFigure::query()->create([
            'manual_id'    => $manual->id,
            'figure_type'  => 'detail',
            'title'        => $title . ' ' . uniqid(),
            'image_path'   => 'test/fig.png',
            'image_width'  => 1000,
            'image_height' => 800,
            'sort_order'   => 0,
        ]);
    }

    public function test_creates_view_point_with_letter_direction_and_child_figure(): void
    {
        $manual = $this->createManual();
        $parent = $this->figure($manual, 'Parent');
        $child  = $this->figure($manual, 'Child');

        $resp = $this->actingAs($this->admin())->postJson(
            "/dimension-figures/{$parent->id}/points",
            [
                'point_type'      => 'view',
                'code'            => 'D',
                'child_figure_id' => $child->id,
                'x_pct'           => 20,  'y_pct'  => 30,
                'x2_pct'          => 32,  'y2_pct' => 30,
                'sort_order'      => 0,
            ]
        );

        $resp->assertCreated();
        $this->assertDatabaseHas('manual_dimension_points', [
            'manual_dimension_figure_id' => $parent->id,
            'point_type'                 => 'view',
            'code'                       => 'D',
            'child_figure_id'            => $child->id,
            'x2_pct'                     => 32,
        ]);
    }

    public function test_view_point_requires_a_letter(): void
    {
        $manual = $this->createManual();
        $parent = $this->figure($manual, 'Parent');

        $this->actingAs($this->admin())
            ->postJson("/dimension-figures/{$parent->id}/points", [
                'point_type' => 'view',
                'x_pct' => 20, 'y_pct' => 30, 'x2_pct' => 32, 'y2_pct' => 30,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_view_point_rejects_unknown_child_figure(): void
    {
        $manual = $this->createManual();
        $parent = $this->figure($manual, 'Parent');

        $this->actingAs($this->admin())
            ->postJson("/dimension-figures/{$parent->id}/points", [
                'point_type'      => 'view',
                'code'            => 'C',
                'child_figure_id' => 999999,
                'x_pct' => 20, 'y_pct' => 30, 'x2_pct' => 32, 'y2_pct' => 30,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('child_figure_id');
    }

    public function test_view_point_can_be_relinked_via_update(): void
    {
        $manual = $this->createManual();
        $parent = $this->figure($manual, 'Parent');
        $childA = $this->figure($manual, 'ChildA');
        $childB = $this->figure($manual, 'ChildB');

        $point = \App\Models\ManualDimensionPoint::query()->create([
            'manual_dimension_figure_id' => $parent->id,
            'point_type'      => 'view',
            'code'            => 'B-B',
            'child_figure_id' => $childA->id,
            'x_pct' => 10, 'y_pct' => 10, 'x2_pct' => 22, 'y2_pct' => 10,
            'sort_order' => 0,
        ]);

        $this->actingAs($this->admin())
            ->patchJson("/dimension-points/{$point->id}", ['child_figure_id' => $childB->id])
            ->assertOk();

        $this->assertDatabaseHas('manual_dimension_points', [
            'id'              => $point->id,
            'child_figure_id' => $childB->id,
        ]);
    }
}
