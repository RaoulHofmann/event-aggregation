<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventTemplate;
use App\Models\FieldTemplate;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return Inertia::render('Dashboard', ['stats' => ['events' => Event::count(), 'templates' => EventTemplate::count(), 'fields' => FieldTemplate::count()], 'recentEvents' => Event::with('template:id,name')->latest()->limit(5)->get()]);
    }
}
