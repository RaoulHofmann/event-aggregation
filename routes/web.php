<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventTemplateController;
use App\Http\Controllers\FieldTemplateController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\IntegrationController;
use App\Http\Middleware\SetPostgresSchema;
use App\Http\Middleware\RequirePlatformAdmin;
use App\Support\PublicSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/pricing', fn () => Inertia::render('Pricing'))->name('pricing');

Route::get('/docs/api', fn () => Inertia::render('Docs/Api'))->name('docs.api');

Route::get('/designs/homepage', fn () => Inertia::render('Designs/Homepage'))->name('designs.homepage');

Route::get('/', fn () => Inertia::render('Welcome', ['canLogin' => Route::has('login'), 'canRegister' => Route::has('register')]))->name('home');

Route::get('/sitemap.xml', fn () => response()->view('sitemap', [
    'urls' => array_map(
        fn (string $name) => PublicSite::url(route($name, absolute: false)),
        array_keys(config('public-site.pages')),
    ),
])->header('Content-Type', 'application/xml; charset=UTF-8'))->name('sitemap');

Route::get('/robots.txt', fn () => response(implode("\n", [
    'User-agent: *',
    'Disallow:',
    'Sitemap: '.PublicSite::url('sitemap.xml'),
    '',
]))->header('Content-Type', 'text/plain; charset=UTF-8'))->name('robots');
Route::get('/account/status', function (Request $request) {
    return $request->user()->isActive()
        ? to_route('dashboard')
        : Inertia::render('Auth/AccountStatus', ['status' => $request->user()->account_status]);
})->middleware('auth')->name('account.status');

Route::prefix('admin')->name('admin.')->middleware(['auth', RequirePlatformAdmin::class])->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::patch('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::patch('/teams/{team}', [AdminController::class, 'updateTeam'])->name('teams.update');
});

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified', SetPostgresSchema::class])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/events/preview', [EventController::class, 'preview'])->name('events.preview');
    Route::post('/events/{event}/exceptions', [EventController::class, 'exception'])->name('events.exceptions');
    Route::resource('events', EventController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::resource('event-templates', EventTemplateController::class)->parameters(['event-templates' => 'eventTemplate'])->only(['index', 'store', 'update', 'destroy']);
    Route::resource('field-templates', FieldTemplateController::class)->parameters(['field-templates' => 'fieldTemplate'])->only(['index', 'store', 'update', 'destroy']);
    Route::get('/integrations/google/connect', [GoogleCalendarController::class, 'connect'])->middleware('throttle:20,1')->name('integrations.google.connect');
    Route::get('/integrations/google/callback', [GoogleCalendarController::class, 'callback'])->middleware('throttle:20,1')->name('integrations.google.callback');
    Route::get('/integrations/google/{connection}/edit', [GoogleCalendarController::class, 'edit'])->middleware('throttle:20,1')->name('integrations.google.edit');
    Route::put('/integrations/google/{connection}', [GoogleCalendarController::class, 'update'])->middleware('throttle:20,1')->name('integrations.google.update');
    Route::get('/integrations', [IntegrationController::class, 'index'])->name('integrations.index');
    Route::post('/integrations', [IntegrationController::class, 'store'])->name('integrations.store');
    Route::put('/integrations/{connection}', [IntegrationController::class, 'update'])->name('integrations.update');
    Route::post('/integrations/{connection}/sync', [IntegrationController::class, 'sync'])->name('integrations.sync');
    Route::delete('/integrations/{connection}', [IntegrationController::class, 'destroy'])->name('integrations.destroy');
});
