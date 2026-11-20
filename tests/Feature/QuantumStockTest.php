<?php
namespace Tests\Feature;

use App\Services\QuantumStock;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\{BuildsDomainData, TestCase};

class QuantumStockTest extends TestCase
{
    use DatabaseTransactions, BuildsDomainData;
    private QuantumStock $stock;
    protected function setUp(): void {
        parent::setUp();
        $path = storage_path('framework/testing/stock-'.uniqid().'.json');
        $this->stock = new class($path) extends QuantumStock {
            public function __construct(private string $file) {}
            public function path(): string { return $this->file; }
        };
        $this->app->instance(QuantumStock::class, $this->stock);
    }
    protected function tearDown(): void {
        foreach (['','.lock','.request'] as $suffix) { @unlink($this->stock->path().$suffix); }
        parent::tearDown();
    }
    private function payload(): array {
        return ['version'=>1,'exported_at'=>now()->subMinute()->toIso8601String(),'row_count'=>3,'rows'=>[
            ['source_id'=>1,'part_number'=>'49000-1','description'=>'Main Washer','on_hand'=>5,'reserved'=>2,'available'=>3],
            ['source_id'=>2,'part_number'=>'170-70496','description'=>'Washer <script>bad</script>','on_hand'=>0,'reserved'=>0,'available'=>0],
            ['source_id'=>3,'part_number'=>'49101-3','description'=>'Bush','on_hand'=>2,'reserved'=>2,'available'=>0],
        ]];
    }
    public function test_search_preserves_zero_stock_and_matches_pn_prefix_and_description(): void {
        $this->stock->import($this->payload());
        $this->assertCount(3,$this->stock->search('','')['rows']);
        $rows=$this->stock->search(' 49 ','washer')['rows'];
        $this->assertCount(1,$rows); $this->assertSame('49000-1',$rows[0]['part_number']);
        $this->assertCount(2,$this->stock->search('','WASHER')['rows']);
    }
    public function test_live_filter_uses_pn_prefix_or_description_and_nonzero_on_hand(): void {
        $this->stock->import($this->payload());
        $this->actingAs($this->createUserWithRole('Admin',['is_admin'=>false]));
        $this->getJson('/stock?q=49')->assertOk()->assertJson(['total'=>3,'filtered'=>2,'next'=>null]);
        $this->getJson('/stock?q=washer&nonzero=1')->assertOk()->assertJson(['total'=>3,'filtered'=>1])->assertJsonPath('next',null);
        $this->getJson('/stock?q=704')->assertJson(['filtered'=>0]);
        $payload=$this->payload();$payload['row_count']=105;$payload['rows']=[];
        for($i=1;$i<=105;$i++)$payload['rows'][]=['source_id'=>$i,'part_number'=>'PN-'.str_pad($i,3,'0',STR_PAD_LEFT),'description'=>'Part','on_hand'=>1,'reserved'=>0,'available'=>1];
        $this->stock->import($payload);
        $this->getJson('/stock')->assertJson(['total'=>105,'filtered'=>105,'next'=>2]);
        $response=$this->getJson('/stock?page=2')->assertJson(['next'=>null]);
        $this->assertStringContainsString('PN-101',$response->json('html'));
        $this->assertStringNotContainsString('PN-100',$response->json('html'));
    }
    public function test_invalid_snapshot_leaves_previous_data_untouched(): void {
        $payload=$this->payload();$this->stock->import($payload);$original=file_get_contents($this->stock->path());
        foreach (['duplicate','incomplete','stale'] as $case) {
            $bad=$payload;
            if ($case==='duplicate') $bad['rows'][1]['source_id']=1;
            if ($case==='incomplete') $bad['row_count']=4;
            if ($case==='stale') $bad['exported_at']=now()->subDay()->toIso8601String();
            try { $this->stock->import($bad); $this->fail('Snapshot should fail'); } catch (ValidationException $e) {}
            $this->assertSame($original,file_get_contents($this->stock->path()));
        }
    }
    public function test_stock_is_admin_only_even_with_explicit_grant_and_output_is_escaped(): void {
        $this->stock->import($this->payload());
        $user=$this->createUserWithRole('Manager',['is_admin'=>false]);
        $this->actingAs($user)->get('/stock')->assertForbidden();
        $this->postJson('/stock/refresh')->assertNotFound();
        $this->grantFeatureAccess($user,'stock');
        $this->get('/stock')->assertForbidden();
        $this->postJson('/stock/refresh')->assertNotFound();
        $admin=$this->createUserWithRole('Admin',['is_admin'=>false]);
        $this->flushSession();
        $this->actingAs($admin);
        $this->get('/stock?description=washer')->assertOk()->assertSee('Washer &lt;script&gt;bad&lt;/script&gt;',false)->assertDontSee('<script>bad</script>',false);
        $this->postJson('/stock/refresh')->assertNotFound();
        $this->get('/stock')->assertDontSee('Refresh from Quantum')->assertSee('05:00');
    }
    public function test_api_requires_token_for_read_and_write(): void {
        config(['services.quantum_sync.token'=>'stock-test-token']);
        $this->getJson('/api/quantum/stock/state')->assertNotFound();
        $this->postJson('/api/quantum/stock',$this->payload())->assertUnauthorized();
        $this->withToken('stock-test-token')->postJson('/api/quantum/stock',$this->payload())->assertOk();
        $this->withToken('stock-test-token')->getJson('/api/quantum/stock/state')->assertNotFound();
    }
    public function test_reservation_snapshot_keeps_links_and_rejects_incomplete_or_duplicate_allocations(): void {
        $payload=$this->payload();$payload['version']=2;
        foreach($payload['rows'] as &$row){$row['reservations']=[];$row['unassigned_reserved']=0;}unset($row);
        $payload['rows'][0]['reservations']=[['wo'=>'W108095','quantity'=>2]];
        $this->stock->import($payload);
        $this->assertSame($payload['rows'][0]['reservations'],$this->stock->read()['rows'][0]['reservations']);
        $before=file_get_contents($this->stock->path());
        foreach(['missing','duplicate','negative'] as $case){
            $bad=$payload;
            if($case==='missing')unset($bad['rows'][0]['reservations']);
            if($case==='duplicate')$bad['rows'][0]['reservations'][]=$bad['rows'][0]['reservations'][0];
            if($case==='negative')$bad['rows'][0]['reservations'][0]['quantity']=-1;
            try{$this->stock->import($bad);$this->fail('Invalid allocation accepted');}catch(ValidationException $e){}
            $this->assertSame($before,file_get_contents($this->stock->path()));
        }
        $this->actingAs($this->createUserWithRole('Admin',['is_admin'=>false]))->get('/stock?pn=49')
            ->assertOk()->assertSee('Reserved for 49000-1')->assertSee('W108095')->assertSee('stockReservations');
    }
}
