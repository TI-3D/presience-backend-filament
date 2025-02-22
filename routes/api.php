<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceInformationController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\PermitController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ApiMiddleware;

Route::post('/users/login', [AuthenticationController::class, 'login'])->middleware('throttle:5,1')->name('login');
Route::post('/users/reftoken', [AuthenticationController::class, 'refToken'])->name('reftoken');
Route::post('/forgot-password', [ResetPasswordController::class, 'passwordEmail']);

Route::middleware(ApiMiddleware::class)->group(function () {
    // Routes for AuthenticationController
    Route::put('users/update-password', [ProfileController::class, 'changePassword'])->name('changePassword');
    Route::put('users/update-fcmId', [ProfileController::class, 'updateFcmId'])->name('updateFcmId');

    // Routes for ProfileController
    Route::prefix('/users')->controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'getProfile')->name('indexProfile');
        Route::post('/store-photo', 'storePhotos')->name('storePhotos');
        Route::post('/face-recognition', 'faceRecognition')->name('faceRecognition');
        Route::post('/validate-password', 'validatePassword')->name('validatePassword');
    });

    // Routes for ScheduleController
    Route::prefix('/users')->controller(ScheduleController::class)->group(function () {
        Route::get('/schedule-week', 'getScheduleForToday')->name('getSchedule');
        Route::get('/schedule-date', 'getScheduleByDate')->name('getScheduleByDate');
        Route::get('/schedule-id', 'getSchedule')->name('getScheduleByID');
    });

    // Routes for AttendanceController
    Route::prefix('/users')->controller(AttendanceController::class)->group(function () {
        Route::post('/store-attendance', 'attendance')->name('storeAttendance');
        Route::get('/history', 'historyAttendance')->name('historyAttendance');
        Route::get('/history-week', 'getHistoryByWeek')->name('HistoryByWeek');
    });

    // Routes for PermitController
    Route::prefix('/users')->controller(PermitController::class)->group(function () {
        Route::post('/store-current-permit', 'storeCurrentPermit')->name('storeCurrentPermit');
        Route::post('/store-after-permit', 'permitAfter')->name('storeAfterPermit');
        Route::post('/store-before-schedule', 'permitBeforeSchedule')->name('permitBeforeSchedule');
        Route::get('/history-permit', 'getPermitHistory')->name('permitHistory');
    });

    // Routes for AttendanceInformationController
    Route::get('/users/attendance-information', [AttendanceInformationController::class, 'getAttendanceInformation'])->name('getAttendanceInformation');
});
