<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_online_returns_true_when_seen_within_5_minutes(): void
    {
        $user = User::factory()->make(['last_seen_at' => now()->subMinutes(2)]);
        $this->assertTrue($user->isOnline());
    }

    public function test_is_online_returns_false_when_seen_more_than_5_minutes_ago(): void
    {
        $user = User::factory()->make(['last_seen_at' => now()->subMinutes(6)]);
        $this->assertFalse($user->isOnline());
    }

    public function test_is_online_returns_false_when_never_seen(): void
    {
        $user = User::factory()->make(['last_seen_at' => null]);
        $this->assertFalse($user->isOnline());
    }

    public function test_avatar_url_is_null_when_no_avatar(): void
    {
        $user = User::factory()->make(['avatar' => null]);
        $this->assertNull($user->avatar_url);
    }
}
