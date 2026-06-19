<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserCareerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'target_roles',
        'skills',
        'min_salary',
        'preferred_locations',
        'scan_frequency',
        'next_scan_at',
        'map_score_threshold',
        'is_active',
    ];

    protected $attributes = [
        'target_roles' => '[]',
        'skills' => '[]',
        'preferred_locations' => '[]',
    ];

    protected function casts(): array
    {
        return [
            'target_roles' => 'array',
            'skills' => 'array',
            'preferred_locations' => 'array',
            'next_scan_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(JobEvaluation::class);
    }
}
