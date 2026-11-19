<?php

use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\CalendarWidgetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventTemplateController;
use App\Http\Controllers\FieldTemplateController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\InboundImportController;
use App\Http\Middleware\SetPostgresSchema;
use App\Http\Middleware\RequirePlatformAdmin;
use App\Support\PublicSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/pricing', fn () => Inertia::render('Pricing'))->name('pricing');

Route::get('/docs/api', fn () => Inertia::render('Docs/Api'))->name('docs.api');

Route::prefix('embed/upcoming')->withoutMiddleware('web')->middleware('throttle:120,1')->group(function () {
    Route::get('/example', [CalendarWidgetController::class, 'example'])->name('widgets.upcoming.example');
    Route::get('/{widget}', [CalendarWidgetController::class, 'show'])->where('widget', '[A-Za-z0-9]{40}')->name('widgets.upcoming.show');
});

Route::post('/user/api-tokens/{token}/reveal', [ApiTokenController::class, 'reveal'])
    ->middleware(['auth', 'verified', 'throttle:15,1'])->whereNumber('token')->name('api-tokens.reveal');

Route::get('/designs/homepage', fn () => Inertia::render('Designs/Homepage'))->name('designs.homepage');

Route::get('/', fn () => Inertia::render('Welcome', [
    'canLogin' => Route::has('login'),
    'canRegister' => Route::has('register'),
    'pageTitle' => config('public-site.pages.home.title'),
]))->name('home');

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
    Route::post('/support-tickets', [SupportTicketController::class, 'store'])->middleware('throttle:5,1')->name('support-tickets.store');
    Route::post('/events/preview', [EventController::class, 'preview'])->name('events.preview');
    Route::patch('/events/bulk', [EventController::class, 'bulkUpdate'])->name('events.bulk.update');
    Route::delete('/events/bulk', [EventController::class, 'bulkDestroy'])->name('events.bulk.destroy');
    Route::get('/events/widget', [CalendarWidgetController::class, 'index'])->name('events.widget');
    Route::put('/events/widget', [CalendarWidgetController::class, 'save'])->name('events.widget.save');
    Route::delete('/events/widget', [CalendarWidgetController::class, 'disable'])->name('events.widget.disable');
    Route::post('/events/{event}/exceptions', [EventController::class, 'exception'])->name('events.exceptions');
    Route::resource('events', EventController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::resource('event-templates', EventTemplateController::class)->parameters(['event-templates' => 'eventTemplate'])->only(['index', 'store', 'update', 'destroy']);
    Route::resource('field-templates', FieldTemplateController::class)->parameters(['field-templates' => 'fieldTemplate'])->only(['index', 'store', 'update', 'destroy']);
    Route::get('/integrations/google/connect', [GoogleCalendarController::class, 'connect'])->middleware('throttle:20,1')->name('integrations.google.connect');
    Route::get('/integrations/google/callback', [GoogleCalendarController::class, 'callback'])->middleware('throttle:20,1')->name('integrations.google.callback');
    Route::get('/integrations/google/{connection}/edit', [GoogleCalendarController::class, 'edit'])->middleware('throttle:20,1')->name('integrations.google.edit');
    Route::put('/integrations/google/{connection}', [GoogleCalendarController::class, 'update'])->middleware('throttle:20,1')->name('integrations.google.update');
    Route::prefix('/integrations/imports')->name('integrations.imports.')->group(function () {
        Route::get('/', [InboundImportController::class, 'index'])->name('index');
        Route::post('/csv/preview', [InboundImportController::class, 'previewCsv'])->middleware('throttle:20,1')->name('csv.preview');
        Route::post('/csv', [InboundImportController::class, 'importCsv'])->middleware('throttle:10,1')->name('csv.store');
        Route::post('/website/preview', [InboundImportController::class, 'previewWebsite'])->middleware('throttle:10,1')->name('website.preview');
        Route::post('/website', [InboundImportController::class, 'importWebsite'])->middleware('throttle:10,1')->name('website.store');
        Route::post('/{provider}', [InboundImportController::class, 'store'])->middleware('throttle:20,1')->name('store');
        Route::put('/{connection}', [InboundImportController::class, 'update'])->middleware('throttle:20,1')->name('update');
    });
    Route::get('/integrations', [IntegrationController::class, 'index'])->name('integrations.index');
    Route::post('/integrations', [IntegrationController::class, 'store'])->name('integrations.store');
    Route::put('/integrations/{connection}', [IntegrationController::class, 'update'])->name('integrations.update');
    Route::post('/integrations/{connection}/sync', [IntegrationController::class, 'sync'])->name('integrations.sync');
    Route::delete('/integrations/{connection}', [IntegrationController::class, 'destroy'])->name('integrations.destroy');
});
