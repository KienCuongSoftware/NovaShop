<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserInitialsAvatarService
{
    /**
     * Curated backgrounds with readable white text (no raw hash-as-color).
     *
     * @var list<array{bg: string, fg: string}>
     */
    private const PALETTE = [
        ['bg' => '#5C6BC0', 'fg' => '#FFFFFF'],
        ['bg' => '#26A69A', 'fg' => '#FFFFFF'],
        ['bg' => '#EF5350', 'fg' => '#FFFFFF'],
        ['bg' => '#AB47BC', 'fg' => '#FFFFFF'],
        ['bg' => '#42A5F5', 'fg' => '#FFFFFF'],
        ['bg' => '#7E57C2', 'fg' => '#FFFFFF'],
        ['bg' => '#00897B', 'fg' => '#FFFFFF'],
        ['bg' => '#F4511E', 'fg' => '#FFFFFF'],
        ['bg' => '#3949AB', 'fg' => '#FFFFFF'],
        ['bg' => '#C62828', 'fg' => '#FFFFFF'],
        ['bg' => '#2E7D32', 'fg' => '#FFFFFF'],
        ['bg' => '#6D4C41', 'fg' => '#FFFFFF'],
    ];

    public static function paletteCount(): int
    {
        return count(self::PALETTE);
    }

    public static function randomPaletteIndex(): int
    {
        return random_int(0, self::paletteCount() - 1);
    }

    public function paletteIndexFor(User $user): int
    {
        $n = self::paletteCount();
        if ($user->avatar_palette_index !== null) {
            return (int) $user->avatar_palette_index % $n;
        }

        return (int) $user->id % $n;
    }

    /**
     * Two letters: first char of first word + first char of last word (e.g. Tran Kien Cuong → TC).
     * Single word: first two graphemes (e.g. Nguyen → NG).
     */
    public function initialsFromName(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name));
        if ($name === '') {
            return '?';
        }

        $parts = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($parts) >= 2) {
            $a = mb_substr($parts[0], 0, 1, 'UTF-8');
            $b = mb_substr($parts[count($parts) - 1], 0, 1, 'UTF-8');

            return mb_strtoupper($a.$b, 'UTF-8');
        }

        $word = $parts[0];
        $len = mb_strlen($word, 'UTF-8');
        if ($len >= 2) {
            return mb_strtoupper(mb_substr($word, 0, 2, 'UTF-8'), 'UTF-8');
        }

        return mb_strtoupper($word.$word, 'UTF-8');
    }

    public function cacheKey(User $user): string
    {
        return 'initials_avatar_svg:'.$user->id.':'.hash('sha256', $user->name.'|'.$user->avatar_palette_index.'|'.($user->avatar ?? ''));
    }

    public function svgForUser(User $user): string
    {
        $initials = htmlspecialchars($this->initialsFromName((string) $user->name), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $idx = $this->paletteIndexFor($user);
        $bg = self::PALETTE[$idx]['bg'];
        $fg = self::PALETTE[$idx]['fg'];
        $fontFamily = htmlspecialchars(
            'system-ui, -apple-system, Segoe UI, Roboto, Helvetica Neue, Arial, sans-serif',
            ENT_XML1 | ENT_QUOTES,
            'UTF-8'
        );

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128" role="img" aria-label="Avatar">'
            .'<circle cx="64" cy="64" r="64" fill="%s"/>'
            .'<text x="50%%" y="50%%" dominant-baseline="central" text-anchor="middle" fill="%s" font-family="%s" font-size="48" font-weight="600">%s</text>'
            .'</svg>',
            $bg,
            $fg,
            $fontFamily,
            $initials
        );
    }

    public function cachedSvgForUser(User $user): string
    {
        return Cache::remember($this->cacheKey($user), 86400, fn () => $this->svgForUser($user));
    }
}
