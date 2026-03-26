<?php

namespace App\Observers;

use App\Jobs\SyncProductToSearchIndexJob;
use App\Models\Product;

class ProductObserver
{
    public function created(Product $product): void
    {
        SyncProductToSearchIndexJob::dispatch($product->id, 'upsert');
    }

    public function updated(Product $product): void
    {
        SyncProductToSearchIndexJob::dispatch($product->id, 'upsert');
    }

    public function deleted(Product $product): void
    {
        SyncProductToSearchIndexJob::dispatch($product->id, 'delete');
    }

    public function restored(Product $product): void
    {
        SyncProductToSearchIndexJob::dispatch($product->id, 'upsert');
    }
}

