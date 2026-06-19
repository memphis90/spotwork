<?php

namespace App\Jobs;

use App\Models\JobEvaluation;
use App\Models\PortalJob;
use App\Models\UserCareerProfile;
use App\Notifications\HighScoreJobFound;
use App\Services\CareerOpsClient;
use App\Services\JobEvaluationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluatePortalJobJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public readonly int $portalJobId,
        public readonly int $userCareerProfileId,
    ) {}

    public function handle(CareerOpsClient $client, JobEvaluationService $evaluator): void
    {
        $job = PortalJob::findOrFail($this->portalJobId);
        $profile = UserCareerProfile::findOrFail($this->userCareerProfileId);

        if (! $job->description && $job->url) {
            $description = $client->fetchJobDescription($job->url);
            $job->update([
                'description' => $description ?: null,
                'description_fetched_at' => now(),
            ]);
        }

        $result = $evaluator->score($job, $profile);

        JobEvaluation::updateOrCreate(
            [
                'portal_job_id' => $job->id,
                'user_career_profile_id' => $profile->id,
            ],
            [
                'score' => $result['score'],
                'score_breakdown' => $result['breakdown'],
                'evaluated_at' => now(),
            ]
        );

        if ($result['score'] >= $profile->map_score_threshold) {
            $profile->user->notify(new HighScoreJobFound($job, $result['score']));
        }
    }
}
