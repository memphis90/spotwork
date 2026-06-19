<?php

namespace App\Notifications;

use App\Models\PortalJob;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class HighScoreJobFound extends Notification
{
    use Queueable;

    public function __construct(
        public readonly PortalJob $job,
        public readonly int $score,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'high_score_job',
            'portal_job_id' => $this->job->id,
            'title' => $this->job->title,
            'company' => $this->job->company_name,
            'score' => $this->score,
            'url' => $this->job->url,
        ];
    }
}
