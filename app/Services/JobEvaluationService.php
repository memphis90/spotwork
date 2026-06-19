<?php

namespace App\Services;

use App\Models\PortalJob;
use App\Models\UserCareerProfile;
use Illuminate\Support\Facades\Http;

class JobEvaluationService
{
    public function score(PortalJob $job, UserCareerProfile $profile): array
    {
        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-haiku-4-5-20251001',
            'max_tokens' => 256,
            'messages' => [['role' => 'user', 'content' => $this->buildPrompt($job, $profile)]],
        ]);

        $response->throw();

        return $this->parseScoreResponse($response->json('content.0.text', ''));
    }

    private function buildPrompt(PortalJob $job, UserCareerProfile $profile): string
    {
        $roles = implode(', ', $profile->target_roles ?? []);
        $skills = implode(', ', $profile->skills ?? []);
        $locations = implode(', ', $profile->preferred_locations ?? []);

        return <<<PROMPT
        Score this job against the candidate profile. Reply ONLY with valid JSON, nothing else:
        {"score": 0-100, "breakdown": {"role_match": 0-100, "skills_match": 0-100, "location_match": 0-100}}

        Job title: {$job->title}
        Company: {$job->company_name}
        Location: {$job->location}
        Description: {$job->description}

        Candidate target roles: {$roles}
        Candidate skills: {$skills}
        Preferred locations: {$locations}
        PROMPT;
    }

    private function parseScoreResponse(string $text): array
    {
        $decoded = json_decode(trim($text), true);
        if (! is_array($decoded) || ! array_key_exists('score', $decoded)) {
            return ['score' => 0, 'breakdown' => []];
        }

        return [
            'score' => max(0, min(100, (int) $decoded['score'])),
            'breakdown' => $decoded['breakdown'] ?? [],
        ];
    }
}
