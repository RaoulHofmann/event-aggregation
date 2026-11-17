<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('integrations:sync')->everyFifteenMinutes()->withoutOverlapping();
