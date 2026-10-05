<?php

namespace Tests\Feature;

use App\Models\ProcessName;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Activitylog\Models\Activity;
use Tests\{BuildsDomainData, TestCase};

class ProcessNamePrintFormAuditTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_toggle_and_edit_log_print_form_actor_old_new_and_time(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manager = $this->createUserWithRole('Manager');
        $this->actingAs($admin);
        $name = ProcessName::create(['name'=>'Audit process', 'process_sheet_name'=>'NDT', 'print_form'=>false]);
        $logs = fn () => Activity::where('subject_type', ProcessName::class)->where('subject_id', $name->id)->where('log_name','process_name');
        $created = $logs()->firstOrFail();
        $this->assertSame('created', $created->event);
        $this->assertSame($admin->id, $created->causer_id);
        $this->assertFalse($created->properties['attributes']['print_form']);
        $toggle = route('directories.toggle', ['directory'=>'process_names','id'=>$name->id,'field'=>'print_form']);
        $this->patchJson($toggle)->assertOk()->assertJsonPath('value',true);
        $log = $logs()->latest('id')->firstOrFail();
        $this->assertSame($admin->id, $log->causer_id);
        $this->assertFalse($log->properties['old']['print_form']);
        $this->assertTrue($log->properties['attributes']['print_form']);
        $this->assertNotNull($log->created_at);
        $this->assertSame('Audit process', $log->properties['process_name']);

        $this->actingAs($manager)->withSession(['password_hash_web'=>$manager->getAuthPassword()])->patch(route('process_names.update', $name), [
            'name'=>'Audit process', 'process_sheet_name'=>'NDT', 'print_form'=>0,
        ])->assertSessionHasNoErrors()->assertRedirect(route('process_names.index'));
        $this->assertFalse($name->fresh()->print_form);
        $log = $logs()->latest('id')->firstOrFail();
        $this->assertSame($manager->id, $log->causer_id);
        $this->assertTrue($log->properties['old']['print_form']);
        $this->assertFalse($log->properties['attributes']['print_form']);
        $count = $logs()->count();
        $name->refresh()->save();
        $name->update(['std_days'=>12]);
        $this->assertSame($count, $logs()->count());
        $technician = $this->createUserWithRole('Technician');
        $this->actingAs($technician)->withSession(['password_hash_web'=>$technician->getAuthPassword()])->patchJson($toggle)->assertForbidden();
        $this->assertSame($count, $logs()->count());
        $this->assertFalse($name->fresh()->print_form);
    }
}
