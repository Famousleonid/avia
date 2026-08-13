<?php

namespace Tests\Unit;

use App\Models\ManualServiceBulletin;
use App\Support\ServiceBulletinPrintPaginator;
use PHPUnit\Framework\TestCase;

class ServiceBulletinPrintPaginatorTest extends TestCase
{
    public function test_short_rows_keep_existing_ten_row_first_page_limit(): void
    {
        $bulletins = collect(range(1, 11))->map(fn (int $index): ManualServiceBulletin =>
            $this->bulletin([
                'sort_order' => $index,
                'identification_method' => 'LOG',
                'description' => 'Short description',
            ])
        );

        $pages = (new ServiceBulletinPrintPaginator())->paginate($bulletins);

        $this->assertCount(2, $pages);
        $this->assertCount(10, $pages[0]);
        $this->assertCount(1, $pages[1]);
    }

    public function test_long_rows_like_w107874_are_split_across_two_pages(): void
    {
        $identificationLengths = [36, 3, 41, 3, 83, 124, 167, 209, 39];
        $descriptionLengths = [80, 28, 80, 110, 44, 91, 84, 129, 137];
        $bulletins = collect($identificationLengths)->map(fn (int $length, int $index): ManualServiceBulletin =>
            $this->bulletin([
                'sort_order' => $index + 1,
                'identification_method' => str_repeat('I', $length),
                'description' => str_repeat('D', $descriptionLengths[$index]),
            ])
        );

        $pages = (new ServiceBulletinPrintPaginator())->paginate($bulletins);

        $this->assertCount(2, $pages);
        $this->assertCount(7, $pages[0]);
        $this->assertCount(2, $pages[1]);
    }

    private function bulletin(array $attributes): ManualServiceBulletin
    {
        return new ManualServiceBulletin(array_merge([
            'year_introduced' => '30/Apr/2015',
            'ac_mfg_service_bulletin_no' => '190-32-0060 R1',
            'oem_service_bulletin_no' => '190-70453-32-06 R1',
            'awd_no' => 'N/A',
        ], $attributes));
    }
}
