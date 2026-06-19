<?php

namespace Tests\Unit\Services;

use App\Models\PortalJob;
use App\Models\UserCareerProfile;
use App\Services\JobEvaluationService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class JobEvaluationServiceTest extends TestCase
{
    private function makeJob(array $attrs = []): PortalJob
    {
        return new PortalJob(array_merge([
            'title' => 'Senior PHP Developer',
            'company_name' => 'Acme Corp',
            'location' => 'Remote',
            'description' => 'We need a PHP Laravel developer.',
        ], $attrs));
    }

    private function makeProfile(array $attrs = []): UserCareerProfile
    {
        return new UserCareerProfile(array_merge([
            'target_roles' => ['Senior PHP Developer'],
            'skills' => ['PHP', 'Laravel'],
            'preferred_locations' => ['Remote'],
            'map_score_threshold' => 60,
        ], $attrs));
    }

    public function test_score_returns_numeric_score_and_breakdown(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['text' => '{"score":85,"breakdown":{"role_match":90,"skills_match":80,"location_match":100}}']],
            ], 200),
        ]);

        $result = (new JobEvaluationService)->score($this->makeJob(), $this->makeProfile());

        $this->assertEquals(85, $result['score']);
        $this->assertEquals(90, $result['breakdown']['role_match']);
    }

    public function test_score_returns_zero_on_malformed_claude_response(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['text' => 'Sorry, I cannot score this.']],
            ], 200),
        ]);

        $result = (new JobEvaluationService)->score($this->makeJob(), $this->makeProfile());

        $this->assertEquals(0, $result['score']);
        $this->assertIsArray($result['breakdown']);
    }

    public function test_score_clamps_to_0_100(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['text' => '{"score":150,"breakdown":{}}']],
            ], 200),
        ]);

        $result = (new JobEvaluationService)->score($this->makeJob(), $this->makeProfile());

        $this->assertEquals(100, $result['score']);
    }
}
