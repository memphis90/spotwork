<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyEmailTest extends TestCase
{
    use RefreshDatabase;

    private function makeCompany(array $attrs = []): Company
    {
        return Company::create(array_merge([
            'name'     => 'Acme Srl',
            'lat'      => 45.4641,
            'lon'      => 9.1896,
            'category' => 'all',
            'source'   => 'osm',
        ], $attrs));
    }

    public function test_guest_cannot_suggest_email(): void
    {
        $company = $this->makeCompany();
        $this->postJson("/companies/{$company->id}/suggest-email", ['email' => 'test@example.com'])
             ->assertUnauthorized();
    }

    public function test_authenticated_user_can_suggest_email(): void
    {
        $user    = User::factory()->create();
        $company = $this->makeCompany();

        $this->actingAs($user)
             ->postJson("/companies/{$company->id}/suggest-email", ['email' => 'info@acme.it'])
             ->assertOk()
             ->assertJson(['message' => 'Grazie! Email aggiunta.']);

        $this->assertSame('info@acme.it', $company->fresh()->email);
    }

    public function test_suggest_email_does_not_overwrite_existing(): void
    {
        $user    = User::factory()->create();
        $company = $this->makeCompany(['email' => 'existing@acme.it']);

        $this->actingAs($user)
             ->postJson("/companies/{$company->id}/suggest-email", ['email' => 'new@acme.it'])
             ->assertUnprocessable();

        $this->assertSame('existing@acme.it', $company->fresh()->email);
    }

    public function test_suggest_email_validates_format(): void
    {
        $user    = User::factory()->create();
        $company = $this->makeCompany();

        $this->actingAs($user)
             ->postJson("/companies/{$company->id}/suggest-email", ['email' => 'notanemail'])
             ->assertUnprocessable();
    }
}
