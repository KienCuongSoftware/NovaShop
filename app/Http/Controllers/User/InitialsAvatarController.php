<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserInitialsAvatarService;
use Illuminate\Support\Facades\Storage;

class InitialsAvatarController extends Controller
{
    public function __invoke(User $user, UserInitialsAvatarService $initialsAvatar)
    {
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            return redirect('/images/avatars/'.basename($user->avatar));
        }

        $body = $initialsAvatar->cachedSvgForUser($user);

        return response($body, 200)
            ->header('Content-Type', 'image/svg+xml; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=604800, stale-while-revalidate=86400');
    }
}
