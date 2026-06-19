<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\UserCareerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCareerProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_one_career_profile(): void
    {
        $user = User::factory()->create();
        $profile = UserCareerProfile::create([
            'user_id' => $user->id,
            'target_roles' => ['Senior PHP Developer'],
            'skills' => ['Laravel', 'Vue.js'],
            'preferred_locations' => ['Remote', 'Milano'],
        ]);

        $this->assertInstanceOf(UserCareerProfile::class, $user->careerProfile);
        $this->assertEquals($profile->id, $user->careerProfile->id);
    }

    public function test_career_profile_casts_json_columns(): void
    {
        $user = User::factory()->create();
        UserCareerProfile::create([
            'user_id' => $user->id,
            'target_roles' => ['Backend Developer'],
            'skills' => ['PHP', 'MySQL'],
            'preferred_locations' => ['Remote'],
        ]);

        $profile = UserCareerProfile::where('user_id', $user->id)->first();
        $this->assertIsArray($profile->target_roles);
        $this->assertIsArray($profile->skills);
        $this->assertIsArray($profile->preferred_locations);
    }
}
