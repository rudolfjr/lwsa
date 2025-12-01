<?php

namespace App\Providers;

use App\Events\SaleCompleted;
use App\Listeners\InvalidateCacheOnSaleCompleted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Event::listen(SaleCompleted::class, InvalidateCacheOnSaleCompleted::class);
    }
}
