<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\UserInitialsAvatarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserInitialsAvatarServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_initials_from_multi_word_name_uses_first_and_last_word(): void
    {
        $s = new UserInitialsAvatarService;

        $this->assertSame('TC', $s->initialsFromName('Tran Kien Cuong'));
        $this->assertSame('NA', $s->initialsFromName('Nguyễn Văn An'));
    }

    public function test_initials_from_single_word_uses_two_graphemes(): void
    {
        $s = new UserInitialsAvatarService;

        $this->assertSame('NG', $s->initialsFromName('Nguyen'));
    }

    public function test_initials_avatar_route_returns_svg(): void
    {
        $user = User::factory()->create([
            'name' => 'Tran Kien Cuong',
            'avatar' => null,
            'avatar_palette_index' => 2,
        ]);

        $response = $this->get($user->initialsAvatarUrl());

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml; charset=utf-8');
        $this->assertStringContainsString('TC', $response->getContent());
        $this->assertStringContainsString('<svg', $response->getContent());
    }
}
