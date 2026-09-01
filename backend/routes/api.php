<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectFormController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // --- COTIA platform level: onboarding client organisations (super_admin only) ---
    Route::middleware('role:super_admin')->group(function () {
        Route::apiResource('organizations', OrganizationController::class)->except(['destroy']);
    });

    // --- Everything below belongs to ONE organisation. The 'org' middleware
    //     rejects super_admin (and any user with no organization_id) up front;
    //     every controller additionally double-checks organization_id on the
    //     specific record being touched, so an ID from another organisation
    //     can never be guessed into view. ---
    // --- Projects are visible to the super-admin across all organisations, while org users still see only their organisation's projects. ---
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);

    Route::middleware('org')->group(function () {
        // --- Org admin: users & roles (ED of this organisation only) ---
        Route::middleware('role:super_admin,ed')->group(function () {
            Route::apiResource('users', UserController::class)->except(['show']);
        });

        // --- Projects (create/edit: super_admin on behalf of org, ed, meo) ---
        Route::middleware('role:super_admin,ed,meo')->group(function () {
            Route::post('/projects', [ProjectController::class, 'store']);
            Route::put('/projects/{project}', [ProjectController::class, 'update']);
        });

        // --- Dynamic forms (create/edit: super_admin on behalf of org, ed, meo) ---
        Route::get('/projects/{project}/forms', [ProjectFormController::class, 'index']);
        Route::get('/forms/{projectForm}', [ProjectFormController::class, 'show']);
        Route::middleware('role:super_admin,ed,meo')->group(function () {
            Route::post('/projects/{project}/forms', [ProjectFormController::class, 'store']);
            Route::put('/forms/{projectForm}', [ProjectFormController::class, 'update']);
        });

        // --- Data collection: all org roles can submit; super_admin and org admins can work on behalf of staff ---
        Route::get('/submissions', [SubmissionController::class, 'index']);
        Route::get('/submissions/{submission}', [SubmissionController::class, 'show']);
        Route::post('/submissions/check-duplicate', [SubmissionController::class, 'checkDuplicate']);
        Route::post('/submissions', [SubmissionController::class, 'store']);
        Route::middleware('role:super_admin,ed,meo,po')->group(function () {
            Route::post('/submissions/{submission}/review', [SubmissionController::class, 'review']);
        });

        // --- Attendance ---
        Route::get('/attendance', [AttendanceController::class, 'forDate']);

        // --- Reports ---
        Route::get('/reports', [ReportController::class, 'index']);
        Route::post('/reports/generate', [ReportController::class, 'generate']);
        Route::get('/reports/{report}/pdf', [ReportController::class, 'downloadPdf']);
        Route::put('/reports/{report}', [ReportController::class, 'update']);
    });
});
