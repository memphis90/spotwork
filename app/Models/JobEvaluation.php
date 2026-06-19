<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobEvaluation extends Model
{
    protected $fillable = [
        'portal_job_id',
        'user_career_profile_id',
        'score',
        'score_breakdown',
        'evaluated_at',
    ];

    protected function casts(): array
    {
        return [
            'score_breakdown' => 'array',
            'evaluated_at' => 'datetime',
        ];
    }

    public function portalJob(): BelongsTo
    {
        return $this->belongsTo(PortalJob::class);
    }

    public function careerProfile(): BelongsTo
    {
        return $this->belongsTo(UserCareerProfile::class, 'user_career_profile_id');
    }
}
