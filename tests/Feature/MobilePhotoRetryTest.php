<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\BuildsDomainData;
use Tests\TestCase;

class MobilePhotoRetryTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    private function token($user): array
    {
        $token = $this->postJson(route('api.android.auth.login'), ['email' => $user->email, 'password' => 'password'])
            ->assertOk()->json('data.token');

        return ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
    }

    private function photo(string $bytes): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('frame.jpg', $bytes);
    }

    private function imageBytes(int $width = 120): string
    {
        $source = UploadedFile::fake()->image('frame.jpg', $width, 80);

        return file_get_contents($source->getPathname());
    }

    public function test_native_workorder_retry_returns_the_existing_photo_without_consuming_a_sequence(): void
    {
        Bus::fake();
        Http::fake();
        $user = $this->createUserWithRole('Technician');
        $wo = $this->createWorkorder(['user_id' => $user->id]);
        $headers = $this->token($user);
        $url = route('api.android.workorders.media.store', $wo->id);
        $bytes = $this->imageBytes();
        $id = str_repeat('a', 64);
        $body = ['category' => 'Machining', 'upload_id' => $id];
        $first = $this->post($url, $body + ['photos' => [$this->photo($bytes)]], $headers)
            ->assertOk()->assertJsonPath('data.photo_count', 1)->json('data.media.0.id');
        $this->post($url, $body + ['photos' => [$this->photo($bytes)]], $headers)
            ->assertOk()->assertJsonPath('data.photo_count', 1)->assertJsonPath('data.media.0.id', $first);
        $this->assertCount(1, $wo->fresh()->getMedia('Machining'));
        $this->assertDatabaseHas('workorder_media_sequences', ['workorder_id' => $wo->id, 'collection_name' => 'Machining', 'last_sequence' => 1]);
        $changed = $this->imageBytes(121);
        $this->post($url, $body + ['photos' => [$this->photo($changed)]], $headers)->assertStatus(409);
        $this->post($url, $body + ['photos' => [$this->photo($bytes), $this->photo($bytes)]], $headers)->assertUnprocessable();
        $this->post($url, ['category' => 'repair', 'upload_id' => $id, 'photos' => [$this->photo($bytes)]], $headers)
            ->assertOk()->assertJsonPath('data.photo_count', 1);
        $other = $this->createWorkorder(['user_id' => $user->id]);
        $this->post(route('api.android.workorders.media.store', $other->id), $body + ['photos' => [$this->photo($bytes)]], $headers)
            ->assertOk()->assertJsonPath('data.photo_count', 1);
        // Older clients without upload_id keep their original explicit-upload behavior.
        $this->post($url, ['category' => 'Machining', 'photos' => [$this->photo($bytes)]], $headers)
            ->assertOk()->assertJsonPath('data.photo_count', 2);
        Http::assertNothingSent();
    }

    public function test_native_log_card_confirm_retry_keeps_one_photo_and_its_row_context(): void
    {
        Bus::fake();
        Http::fake();
        $user = $this->createUserWithRole('Technician');
        $wo = $this->createWorkorder(['user_id' => $user->id]);
        $headers = $this->token($user);
        $url = route('api.android.workorders.log-card.photo.store', $wo->id);
        $bytes = $this->imageBytes();
        $body = ['recognize' => 0, 'row_index' => 3, 'upload_id' => str_repeat('b', 64)];
        $first = $this->post($url, $body + ['photo' => $this->photo($bytes)], $headers)
            ->assertOk()->assertJsonPath('data.photo_count', 1)->assertJsonPath('data.pending_confirmation', false)->json('data.photo.id');
        $this->post($url, $body + ['photo' => $this->photo($bytes)], $headers)
            ->assertOk()->assertJsonPath('data.photo_count', 1)->assertJsonPath('data.photo.id', $first);
        $media = $wo->fresh()->getMedia('logs')->sole();
        $this->assertSame('mobile_log_card', $media->getCustomProperty('source'));
        $this->assertSame('3', (string) $media->getCustomProperty('log_card_row_index'));
        $this->assertNotEmpty($media->getCustomProperty('native_upload_key'));
        $changed = $this->imageBytes(121);
        $this->post($url, $body + ['photo' => $this->photo($changed)], $headers)->assertStatus(409);
        $this->post($url, array_merge($body, ['row_index' => 4, 'photo' => $this->photo($bytes)]), $headers)
            ->assertOk()->assertJsonPath('data.photo_count', 2);
        Http::assertNothingSent();
    }
}
