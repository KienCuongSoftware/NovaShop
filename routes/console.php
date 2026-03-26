<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\ProductSearchService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('search:reindex-products', function (ProductSearchService $searchService) {
    $this->info('Reindex products to Elasticsearch...');
    $searchService->reindexAllProducts();
    $this->info('Done.');
})->purpose('Reindex active products to Elasticsearch index');
