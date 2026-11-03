<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('articles:publish-scheduled')->everyMinute()->withoutOverlapping()->onOneServer();
