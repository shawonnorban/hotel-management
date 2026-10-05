<?php

use Illuminate\Support\Facades\Schedule;

// Needs one cron entry on the server: * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
Schedule::command('hotel:backup --keep=14')->dailyAt('02:00')->withoutOverlapping();
