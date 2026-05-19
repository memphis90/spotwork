<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_settings(): void
    {
        $this->get('/settings')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_settings(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/settings')->assertOk();
    }

    public function test_user_can_update_application_message(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->patch('/settings/message', ['message' => 'Ciao, mi candido.'])
            ->assertRedirect();
        $this->assertSame('Ciao, mi candido.', $user->fresh()->application_message);
    }

    public function test_message_max_length_is_enforced(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->patch('/settings/message', ['message' => str_repeat('a', 2001)])
            ->assertSessionHasErrors('message');
    }

    public function test_user_can_upload_cv(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('mycv.pdf', 1024, 'application/pdf');

        $this->actingAs($user)
            ->post('/settings/cv', ['cv' => $file])
            ->assertRedirect();

        $this->assertNotNull($user->fresh()->cv_path);
        Storage::disk('local')->assertExists($user->fresh()->cv_path);
    }

    public function test_cv_upload_rejects_non_pdf(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream');

        $this->actingAs($user)
            ->post('/settings/cv', ['cv' => $file])
            ->assertSessionHasErrors('cv');
    }

    public function test_user_can_delete_cv(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        Storage::disk('local')->put('cvs/1/test.pdf', 'content');
        $user->update(['cv_path' => 'cvs/1/test.pdf']);

        $this->actingAs($user)
            ->delete('/settings/cv')
            ->assertRedirect();

        $this->assertNull($user->fresh()->cv_path);
        Storage::disk('local')->assertMissing('cvs/1/test.pdf');
    }
}
