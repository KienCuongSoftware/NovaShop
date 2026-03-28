<?php

namespace Database\Seeders;

use App\Models\SearchSynonym;
use Illuminate\Database\Seeder;

class SearchSynonymSeeder extends Seeder
{
    public function run(): void
    {
        $pairs = [
            // iPhone / Apple
            ['iphone', 'điện thoại apple'],
            ['iphone', 'apple phone'],
            ['iphone', 'ip'],
            ['ip', 'iphone'],
            ['apple', 'iphone'],
            ['apple', 'iphone chính hãng'],

            // Samsung / Galaxy
            ['samsung', 'galaxy'],
            ['galaxy', 'samsung'],
            ['samsung', 'điện thoại samsung'],

            // Laptop / Notebook
            ['laptop', 'notebook'],
            ['notebook', 'laptop'],
            ['laptop', 'máy tính xách tay'],
            ['máy tính xách tay', 'laptop'],

            // Tai nghe
            ['tai nghe', 'headphone'],
            ['tai nghe', 'earphone'],
            ['earphone', 'tai nghe'],
            ['headphone', 'tai nghe'],

            // Sạc / Charger
            ['sạc', 'charger'],
            ['charger', 'sạc'],
            ['cáp sạc', 'cable sạc'],
            ['cable', 'cáp'],

            // Màu sắc thường gặp
            ['đen', 'black'],
            ['trắng', 'white'],
            ['xanh', 'blue'],
            ['đỏ', 'red'],

            // Giày / Sneaker
            ['giày', 'shoes'],
            ['sneaker', 'giày'],
            ['giày thể thao', 'sneaker'],
        ];

        $inserted = 0;
        foreach ($pairs as [$k, $s]) {
            $k = mb_strtolower(trim((string) $k));
            $s = mb_strtolower(trim((string) $s));
            if ($k === '' || $s === '' || $k === $s) {
                continue;
            }

            $row = SearchSynonym::query()->firstOrCreate([
                'keyword' => $k,
                'synonym' => $s,
            ]);

            if ($row->wasRecentlyCreated) {
                $inserted++;
            }
        }

        $this->command?->info("Seeded search_synonyms: +{$inserted} pairs");
    }
}

