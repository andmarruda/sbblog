<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('articles:publish-scheduled')->everyMinute()->withoutOverlapping()->onOneServer();
Schedule::command('snapshots:build')->dailyAt('03:00')->onOneServer();
Schedule::command('events:prune')->dailyAt('04:00')->onOneServer();
Schedule::command('events:dispatch')->everyMinute()->withoutOverlapping()->onOneServer();
