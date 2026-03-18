<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LightBox extends Component
{
    /** @var array<string> Mảng URL ảnh để hiển thị trong lightbox. */
    public array $urls = [];

    public function __construct($images = null, public ?string $title = null)
    {
        if ($images === null) {
            return;
        }
        $this->urls = $this->normalizeToUrls($images);
    }

    /**
     * Chuẩn hóa input (collection ProductImage, array URL, ...) thành mảng URL.
     */
    private function normalizeToUrls(mixed $images): array
    {
        if (is_array($images)) {
            $out = [];
            foreach ($images as $item) {
                if (is_string($item)) {
                    $out[] = $item;
                } elseif (is_object($item) && isset($item->image)) {
                    $out[] = '/images/products/' . basename($item->image);
                }
            }
            return $out;
        }
        if (is_iterable($images)) {
            $out = [];
            foreach ($images as $item) {
                if (is_string($item)) {
                    $out[] = $item;
                } elseif (is_object($item) && isset($item->image)) {
                    $out[] = '/images/products/' . basename($item->image);
                }
            }
            return $out;
        }
        return [];
    }

    public function render(): View
    {
        return view('components.light-box');
    }
}
