<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PortalJob extends Model
{
    protected $fillable = [
        'company_id',
        'company_name',
        'title',
        'url',
        'location',
        'description',
        'description_fetched_at',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'description_fetched_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(JobEvaluation::class);
    }
}
