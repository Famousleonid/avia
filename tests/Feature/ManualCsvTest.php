<?php

namespace Tests\Feature;

use App\Models\StdProcess;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ManualCsvTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_manual_csv_store_imports_std_processes_and_data_endpoint_returns_rows(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();

        $csv = implode("\n", [
            'item no.,part no.,description,process no.,qty,manual,eff code',
            '1-10,PN-100,Sample Row,5,2,CMM-TEST,ALL',
        ]);

        $storeResponse = $this->actingAs($admin)->post(route('manuals.csv.store', $manual), [
            'process_type' => StdProcess::STD_NDT,
            'csv_file' => $this->makeUploadedFile('ndt.csv', $csv, 'text/csv'),
        ]);

        $storeResponse->assertOk();
        $storeResponse->assertJsonPath('success', true);

        $this->assertDatabaseHas('std_processes', [
            'manual_id' => $manual->id,
            'std' => StdProcess::STD_NDT,
            'ipl_num' => '1-10',
            'part_number' => 'PN-100',
            'qty' => 2,
        ]);

        $media = $manual->getMedia('csv_files')->first();
        $this->assertNotNull($media);

        $dataResponse = $this->actingAs($admin)->get(route('manuals.csv.data', [
            'manual' => $manual->id,
            'file' => $media->id,
        ]));

        $dataResponse->assertOk();
        $dataResponse->assertJsonPath('success', true);
        $dataResponse->assertJsonPath('process_type', StdProcess::STD_NDT);
        $dataResponse->assertJsonPath('records.0.0', '1-10');
        $dataResponse->assertJsonPath('records.0.1', 'PN-100');
    }

    public function test_manual_csv_store_rejects_invalid_process_type(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();

        $response = $this->actingAs($admin)->postJson(route('manuals.csv.store', $manual), [
            'process_type' => 'unknown',
            'csv_file' => $this->makeUploadedFile('bad.csv', "item no.\n1-10", 'text/csv'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_manual_csv_delete_removes_media_and_std_process_rows(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();

        $csv = implode("\n", [
            'item no.,part no.,description,process no.,qty',
            '1-20,PN-200,Delete Row,1,1',
        ]);

        $this->actingAs($admin)->post(route('manuals.csv.store', $manual), [
            'process_type' => StdProcess::STD_CAD,
            'csv_file' => $this->makeUploadedFile('cad.csv', $csv, 'text/csv'),
        ])->assertOk();

        $media = $manual->getMedia('csv_files')->firstOrFail();

        $response = $this->actingAs($admin)->delete(route('manuals.csv.delete', [
            'manual' => $manual->id,
            'file' => $media->id,
        ]));

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        $this->assertDatabaseMissing('std_processes', [
            'manual_id' => $manual->id,
            'std' => StdProcess::STD_CAD,
            'ipl_num' => '1-20',
        ]);
    }
}
