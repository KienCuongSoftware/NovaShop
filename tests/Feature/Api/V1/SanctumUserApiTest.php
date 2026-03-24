<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SanctumUserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/v1/user')->assertUnauthorized();
    }

    public function test_user_endpoint_returns_profile_when_authenticated(): void
    {
        $user = User::factory()->create([
            'name' => 'API Tester',
            'email' => 'api-tester@example.com',
            'is_admin' => false,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('email', 'api-tester@example.com')
            ->assertJsonPath('is_admin', false);
    }
}
