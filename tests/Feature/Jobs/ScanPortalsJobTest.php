<?php

namespace Tests\Feature\Jobs;

use App\Jobs\EvaluatePortalJobJob;
use App\Jobs\ScanPortalsJob;
use App\Models\Company;
use App\Models\PortalJob;
use App\Models\User;
use App\Models\UserCareerProfile;
use App\Services\CareerOpsClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class ScanPortalsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_evaluate_job_for_new_portal_jobs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        UserCareerProfile::create([
            'user_id' => $user->id,
            'target_roles' => ['PHP Dev'],
            'skills' => ['PHP'],
            'preferred_locations' => ['Remote'],
            'is_active' => true,
        ]);

        Company::create([
            'name' => 'Acme',
            'lat' => 45.0,
            'lon' => 9.0,
            'portal_url' => 'https://acme.com/careers',
            'ats_provider' => 'greenhouse',
        ]);

        $client = Mockery::mock(CareerOpsClient::class);
        $client->shouldReceive('scan')->once()->andReturn([
            ['title' => 'PHP Dev', 'url' => 'https://acme.com/job/1', 'company' => 'Acme', 'location' => 'Remote'],
        ]);
        $this->app->instance(CareerOpsClient::class, $client);

        (new ScanPortalsJob)->handle($client);

        $this->assertDatabaseHas('portal_jobs', ['url' => 'https://acme.com/job/1']);
        Queue::assertPushed(EvaluatePortalJobJob::class, 1);
    }

    public function test_does_not_redispatch_for_existing_portal_jobs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        UserCareerProfile::create([
            'user_id' => $user->id,
            'target_roles' => ['PHP Dev'],
            'skills' => ['PHP'],
            'preferred_locations' => ['Remote'],
            'is_active' => true,
        ]);

        PortalJob::create([
            'company_name' => 'Acme',
            'title' => 'PHP Dev',
            'url' => 'https://acme.com/job/1',
        ]);

        Company::create([
            'name' => 'Acme',
            'lat' => 45.0,
            'lon' => 9.0,
            'portal_url' => 'https://acme.com/careers',
        ]);

        $client = Mockery::mock(CareerOpsClient::class);
        $client->shouldReceive('scan')->once()->andReturn([
            ['title' => 'PHP Dev', 'url' => 'https://acme.com/job/1', 'company' => 'Acme', 'location' => 'Remote'],
        ]);
        $this->app->instance(CareerOpsClient::class, $client);

        (new ScanPortalsJob)->handle($client);

        Queue::assertNothingPushed();
    }
}
