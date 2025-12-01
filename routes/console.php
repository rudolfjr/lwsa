<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('inventory:archive-stale --days=90')
    ->daily()
    ->at('02:00')
    ->withoutOverlapping()
    ->onOneServer();
