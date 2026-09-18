<?php

use App\Http\Controllers\{ApplicationController, AuthController, DashboardController, NotificationController, OperatorController, OperatorPortalController, PermissionController, ProfileController, PublicTransportController, ReportController, UserController, VehicleController};
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.attempt');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:3,10')->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('permission:view dashboard');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::prefix('operator')->name('operator.')->middleware('role:operator')->group(function () {
        Route::get('/dashboard', [OperatorPortalController::class, 'dashboard'])->name('dashboard')->middleware('permission:operator portal');
        Route::get('/profile', [OperatorPortalController::class, 'profile'])->name('profile')->middleware('permission:operator portal');
        Route::put('/profile', [OperatorPortalController::class, 'updateProfile'])->name('profile.update')->middleware('permission:operator portal');
        Route::put('/password', [OperatorPortalController::class, 'changePassword'])->name('password.update')->middleware('permission:operator portal');
        Route::get('/vehicle', [OperatorPortalController::class, 'vehicles'])->name('vehicle')->middleware('permission:operator portal');
        Route::get('/applications', [OperatorPortalController::class, 'applications'])->name('applications.index')->middleware('permission:operator applications');
        Route::get('/applications/create', [OperatorPortalController::class, 'createApplication'])->name('applications.create')->middleware('permission:operator applications');
        Route::post('/applications', [OperatorPortalController::class, 'storeApplication'])->name('applications.store')->middleware('permission:operator applications');
        Route::get('/applications/{application}', [OperatorPortalController::class, 'application'])->name('applications.show')->middleware('permission:operator applications');
        Route::get('/permits', [OperatorPortalController::class, 'permits'])->name('permits.index')->middleware('permission:operator permits');
        Route::get('/permits/{permit}', [OperatorPortalController::class, 'permit'])->name('permits.show')->middleware('permission:operator permits');
        Route::get('/franchise', [OperatorPortalController::class, 'franchises'])->name('franchises.index')->middleware('permission:operator franchises');
        Route::get('/franchise/{franchise}', [OperatorPortalController::class, 'franchise'])->name('franchises.show')->middleware('permission:operator franchises');
        Route::get('/renewals', [OperatorPortalController::class, 'renewals'])->name('renewals.index')->middleware('permission:operator renewals');
        Route::get('/violations', [OperatorPortalController::class, 'violations'])->name('violations.index')->middleware('permission:operator violations');
        Route::get('/notifications', [OperatorPortalController::class, 'notifications'])->name('notifications.index')->middleware('permission:operator notifications');
    });

    Route::middleware('permission:view reports')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    });
    Route::get('/my-reports', [ReportController::class, 'mine'])->name('reports.mine')->middleware('permission:view my reports');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index')->middleware('permission:view notifications');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read')->middleware('permission:view notifications');
    Route::get('/franchises', [PublicTransportController::class, 'franchises'])->name('franchises.index')->middleware('permission:view franchises');
    Route::get('/franchises/{franchise}', [PublicTransportController::class, 'franchise'])->name('franchises.show')->middleware('permission:view franchises');
    Route::get('/permits', [PublicTransportController::class, 'permits'])->name('permits.index')->middleware('permission:view permits');
    Route::get('/permits/{permit}', [PublicTransportController::class, 'permit'])->name('permits.show')->middleware('permission:view permits');
    Route::get('/renewals', [PublicTransportController::class, 'renewals'])->name('renewals.index')->middleware('permission:view renewals');
    Route::get('/renewals/{renewal}', [PublicTransportController::class, 'renewal'])->name('renewals.show')->middleware('permission:view renewals');
    Route::get('/violations', [PublicTransportController::class, 'violations'])->name('violations.index')->middleware('permission:view violations');
    Route::get('/violations/{violation}', [PublicTransportController::class, 'violation'])->name('violations.show')->middleware('permission:view violations');

    Route::middleware('permission:manage operators')->group(function () {
        Route::resource('operators', OperatorController::class)->only(['create', 'store', 'edit', 'update', 'destroy']);
    });
    Route::middleware('permission:view operators')->group(function () {
        Route::resource('operators', OperatorController::class)->only(['index', 'show']);
    });

    Route::middleware('permission:manage vehicles')->group(function () {
        Route::resource('vehicles', VehicleController::class)->only(['create', 'store', 'edit', 'update', 'destroy']);
    });
    Route::middleware('permission:view vehicles')->group(function () {
        Route::resource('vehicles', VehicleController::class)->only(['index', 'show']);
    });

    Route::middleware('permission:view applications')->group(function () {
        Route::resource('applications', ApplicationController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:create applications')->group(function () {
        Route::resource('applications', ApplicationController::class)->only(['create', 'store']);
    });
    Route::patch('/applications/{application}/status', [ApplicationController::class, 'updateStatus'])->name('applications.status')->middleware('permission:manage applications');

    Route::get('/users', [UserController::class, 'index'])->name('users.index')->middleware('permission:manage users');
    Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update')->middleware('permission:manage users');
    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index')->middleware('permission:manage users');
    Route::put('/permissions/{role}', [PermissionController::class, 'update'])->name('permissions.update')->middleware('permission:manage users');
});
