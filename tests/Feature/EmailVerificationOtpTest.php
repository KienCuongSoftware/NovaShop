<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_is_redirected_to_otp_page_for_protected_route(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'email_verification_otp' => '123456',
            'email_verification_otp_expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->actingAs($user)->get('/cart');

        $response->assertRedirect(route('verification.otp.notice'));
    }

    public function test_user_can_verify_email_with_valid_otp(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'email_verification_otp' => '123456',
            'email_verification_otp_expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->actingAs($user)->post(route('verification.otp.verify'), [
            'otp' => '123456',
        ]);

        $response->assertRedirect(route('welcome'));
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertNull($user->fresh()->email_verification_otp);
    }
}
