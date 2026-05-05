<?php

namespace Tests\Feature;

use App\Services\Ai\Tools\CountWorkorderImagesTool;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\BuildsDomainData;
use Tests\TestCase;

class AiCountWorkorderImagesToolTest extends TestCase
{
    use DatabaseTransactions;
    use BuildsDomainData;

    public function test_total_mode_counts_images_across_workorders(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $tool = app(CountWorkorderImagesTool::class);
        $before = $tool->run($admin, ['mode' => 'total']);

        $first = $this->createWorkorder(['number' => 710001]);
        $second = $this->createWorkorder(['number' => 710002]);

        $this->insertMediaRow($first->id, 'photos', 'first-a.jpg', 'image/jpeg');
        $this->insertMediaRow($first->id, 'damages', 'first-b.jpg', 'image/jpeg');
        $this->insertMediaRow($second->id, 'received', 'second-a.jpg', 'image/jpeg');
        $this->insertMediaRow($second->id, 'photos', 'manual.pdf', 'application/pdf');

        $result = $tool->run($admin, ['mode' => 'total']);

        $this->assertTrue($result['ok']);
        $this->assertSame($before['total_images'] + 3, $result['total_images']);
        $this->assertSame($before['workorders_with_images'] + 2, $result['workorders_with_images']);
    }

    private function insertMediaRow(int $workorderId, string $collection, string $fileName, string $mimeType): void
    {
        DB::table('media')->insert([
            'model_type' => \App\Models\Workorder::class,
            'model_id' => $workorderId,
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'collection_name' => $collection,
            'name' => pathinfo($fileName, PATHINFO_FILENAME),
            'file_name' => $fileName,
            'mime_type' => $mimeType,
            'disk' => 'public',
            'conversions_disk' => 'public',
            'size' => 1,
            'manipulations' => '[]',
            'custom_properties' => '[]',
            'generated_conversions' => '[]',
            'responsive_images' => '[]',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
