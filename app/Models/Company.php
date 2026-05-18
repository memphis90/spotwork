<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = ['osm_id', 'name', 'lat', 'lon', 'category', 'address', 'source'];

    public function savedByUsers(): HasMany
    {
        return $this->hasMany(SavedCompany::class);
    }

    public function savedJobs(): HasMany
    {
        return $this->hasMany(SavedJob::class);
    }

    public static function upsertFromData(array $data): self
    {
        if (!empty($data['osm_id'])) {
            return static::firstOrCreate(
                ['osm_id' => $data['osm_id']],
                $data
            );
        }

        // Match by name + proximity (~100m)
        $existing = static::where('name', $data['name'])
            ->whereBetween('lat', [$data['lat'] - 0.001, $data['lat'] + 0.001])
            ->whereBetween('lon', [$data['lon'] - 0.001, $data['lon'] + 0.001])
            ->first();

        return $existing ?? static::create($data);
    }
}