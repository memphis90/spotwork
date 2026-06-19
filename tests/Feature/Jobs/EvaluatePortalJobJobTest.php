<?php

namespace Tests\Feature\Jobs;

use App\Jobs\EvaluatePortalJobJob;
use App\Models\PortalJob;
use App\Models\User;
use App\Models\UserCareerProfile;
use App\Notifications\HighScoreJobFound;
use App\Services\CareerOpsClient;
use App\Services\JobEvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class EvaluatePortalJobJobTest extends TestCase
{
    use RefreshDatabase;

    private function makeFixtures(int $threshold = 60): array
    {
        $user = User::factory()->create();
        $profile = UserCareerProfile::create([
            'user_id' => $user->id,
            'target_roles' => ['PHP Dev'],
            'skills' => ['PHP'],
            'preferred_locations' => ['Remote'],
            'map_score_threshold' => $threshold,
        ]);
        $job = PortalJob::create([
            'company_name' => 'Acme',
            'title' => 'PHP Dev',
            'url' => 'https://acme.com/job/1',
            'description' => 'We need PHP.',
        ]);

        return [$user, $profile, $job];
    }

    public function test_creates_job_evaluation_record(): void
    {
        Notification::fake();
        [$user, $profile, $job] = $this->makeFixtures();

        $evaluator = Mockery::mock(JobEvaluationService::class);
        $evaluator->shouldReceive('score')->once()->andReturn(['score' => 75, 'breakdown' => ['role_match' => 80]]);
        $this->app->instance(JobEvaluationService::class, $evaluator);

        $client = Mockery::mock(CareerOpsClient::class);
        $client->shouldNotReceive('fetchJobDescription');
        $this->app->instance(CareerOpsClient::class, $client);

        (new EvaluatePortalJobJob($job->id, $profile->id))->handle($client, $evaluator);

        $this->assertDatabaseHas('job_evaluations', [
            'portal_job_id' => $job->id,
            'user_career_profile_id' => $profile->id,
            'score' => 75,
        ]);
    }

    public function test_sends_notification_when_score_meets_threshold(): void
    {
        Notification::fake();
        [$user, $profile, $job] = $this->makeFixtures(threshold: 60);

        $evaluator = Mockery::mock(JobEvaluationService::class);
        $evaluator->shouldReceive('score')->andReturn(['score' => 80, 'breakdown' => []]);
        $this->app->instance(JobEvaluationService::class, $evaluator);

        $client = Mockery::mock(CareerOpsClient::class);
        $client->shouldNotReceive('fetchJobDescription');
        $this->app->instance(CareerOpsClient::class, $client);

        (new EvaluatePortalJobJob($job->id, $profile->id))->handle($client, $evaluator);

        Notification::assertSentTo($user, HighScoreJobFound::class);
    }

    public function test_fetches_jd_when_description_is_null(): void
    {
        [$user, $profile, $job] = $this->makeFixtures();
        $job->update(['description' => null]);

        $client = Mockery::mock(CareerOpsClient::class);
        $client->shouldReceive('fetchJobDescription')->once()->with($job->url)->andReturn('Fetched description.');
        $this->app->instance(CareerOpsClient::class, $client);

        $evaluator = Mockery::mock(JobEvaluationService::class);
        $evaluator->shouldReceive('score')->once()->andReturn(['score' => 50, 'breakdown' => []]);
        $this->app->instance(JobEvaluationService::class, $evaluator);

        (new EvaluatePortalJobJob($job->id, $profile->id))->handle($client, $evaluator);

        $this->assertDatabaseHas('portal_jobs', ['id' => $job->id, 'description' => 'Fetched description.']);
    }
}
