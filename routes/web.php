<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LawyerRegistrationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Lawyer;
use App\Http\Controllers\LawyerDirectoryController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// ---------- Public website ----------
Route::get('/', HomeController::class)->name('home');

Route::middleware('throttle:search')->group(function () {
    Route::get('/lawyers', [LawyerDirectoryController::class, 'index'])->name('lawyers.index');
    Route::get('/lawyers/{slug}', [LawyerDirectoryController::class, 'show'])->name('lawyers.show')->where('slug', '[a-z0-9\-]+');
    Route::get('/locations/{state}/cities', [LocationController::class, 'cities'])->name('locations.cities')->where('state', '[a-z0-9\-]+');
});

Route::post('/enquiries', [EnquiryController::class, 'store'])->middleware('throttle:enquiries')->name('enquiries.store');

Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/for-lawyers', [PageController::class, 'forLawyers'])->name('for-lawyers');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/legal/{page}', [PageController::class, 'legal'])->name('legal');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', fn () => response()->view('robots')->header('Content-Type', 'text/plain'))->name('robots');

// ---------- Authentication ----------
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');
    Route::get('/register', [LawyerRegistrationController::class, 'create'])->name('register');
    Route::post('/register', [LawyerRegistrationController::class, 'store'])->middleware('throttle:register');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:password-reset')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// ---------- Lawyer portal ----------
Route::middleware(['auth', 'role:lawyer'])->prefix('lawyer')->name('lawyer.')->group(function () {
    Route::get('/', Lawyer\DashboardController::class)->name('dashboard');
    Route::get('/profile', [Lawyer\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [Lawyer\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/password', [Lawyer\ProfileController::class, 'password'])->name('password.update');
    Route::get('/documents/{document}', [Lawyer\ProfileController::class, 'document'])->name('documents.show');
});

// ---------- Admin panel ----------
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::get('/lawyers', [Admin\LawyerController::class, 'index'])->name('lawyers.index');
    Route::get('/lawyers/{lawyer}', [Admin\LawyerController::class, 'show'])->name('lawyers.show');
    Route::post('/lawyers/{lawyer}/status', [Admin\LawyerController::class, 'updateStatus'])->name('lawyers.status');
    Route::post('/lawyers/{lawyer}/featured', [Admin\LawyerController::class, 'toggleFeatured'])->name('lawyers.featured');
    Route::post('/lawyers/{lawyer}/account', [Admin\LawyerController::class, 'toggleAccount'])->name('lawyers.account');
    Route::get('/documents/{document}', [Admin\LawyerController::class, 'document'])->name('documents.show');

    Route::get('/enquiries', [Admin\EnquiryController::class, 'index'])->name('enquiries.index');
    Route::post('/enquiries/spam-review', [Admin\EnquiryController::class, 'reviewSpam'])->name('enquiries.spam-review');
    Route::get('/enquiries/{enquiry}', [Admin\EnquiryController::class, 'show'])->name('enquiries.show');
    Route::put('/enquiries/{enquiry}', [Admin\EnquiryController::class, 'update'])->name('enquiries.update');

    Route::get('/changes', [Admin\ChangeLogController::class, 'index'])->name('changes.index');
    Route::post('/changes/review-all', [Admin\ChangeLogController::class, 'reviewAll'])->name('changes.review-all');
    Route::post('/changes/{log}/review', [Admin\ChangeLogController::class, 'review'])->name('changes.review');

    Route::get('/masters', [Admin\TaxonomyController::class, 'index'])->name('masters.index');
    Route::post('/masters/cities', [Admin\TaxonomyController::class, 'storeCity'])->name('masters.cities.store');
    Route::post('/masters/{type}', [Admin\TaxonomyController::class, 'store'])->name('masters.store');
    Route::post('/masters/{type}/{id}/toggle', [Admin\TaxonomyController::class, 'toggle'])->name('masters.toggle');

    Route::get('/settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');
    Route::get('/audit-log', Admin\AuditLogController::class)->name('audit');
});
