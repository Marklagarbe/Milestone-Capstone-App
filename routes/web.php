<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Groups and members
    Route::resource('groups', GroupController::class)->except(['edit']);
    Route::post('groups/{group}/members', [GroupController::class, 'addMember'])->name('groups.members.store');
    Route::delete('groups/{group}/members/{user}', [GroupController::class, 'removeMember'])->name('groups.members.destroy');

    // Projects, timeline
    Route::get('timeline', [ProjectController::class, 'timeline'])->name('timeline');
    Route::resource('projects', ProjectController::class);

    // Phases and milestones
    Route::post('projects/{project}/phases', [MilestoneController::class, 'storePhase'])->name('phases.store');
    Route::put('phases/{phase}', [MilestoneController::class, 'updatePhase'])->name('phases.update');
    Route::delete('phases/{phase}', [MilestoneController::class, 'destroyPhase'])->name('phases.destroy');
    Route::post('projects/{project}/milestones', [MilestoneController::class, 'store'])->name('milestones.store');
    Route::put('milestones/{milestone}', [MilestoneController::class, 'update'])->name('milestones.update');
    Route::delete('milestones/{milestone}', [MilestoneController::class, 'destroy'])->name('milestones.destroy');
    Route::post('milestones/{milestone}/start', [MilestoneController::class, 'start'])->name('milestones.start');
    Route::post('milestones/{milestone}/submit', [MilestoneController::class, 'submit'])->name('milestones.submit');
    Route::post('milestones/{milestone}/review', [MilestoneController::class, 'review'])->name('milestones.review');

    // Tasks and progress updates
    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::post('tasks/{task}/progress', [TaskController::class, 'progress'])->name('tasks.progress');

    // Files, versions and review
    Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::post('documents/{document}/versions', [DocumentController::class, 'storeVersion'])->name('documents.versions.store');
    Route::get('versions/{version}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::post('documents/{document}/favorite', [DocumentController::class, 'favorite'])->name('documents.favorite');
    Route::post('documents/{document}/submit', [DocumentController::class, 'submit'])->name('documents.submit');
    Route::post('documents/{document}/review', [DocumentController::class, 'review'])->name('documents.review');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::post('documents/{document}/restore', [DocumentController::class, 'restore'])->withTrashed()->name('documents.restore');
    Route::delete('documents/{document}/force', [DocumentController::class, 'forceDelete'])->withTrashed()->name('documents.force');

    // Feedback
    Route::get('feedback', [FeedbackController::class, 'index'])->name('feedback.index');
    Route::post('projects/{project}/feedback', [FeedbackController::class, 'store'])->name('feedback.store');

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('notifications/{alert}/read', [NotificationController::class, 'read'])->name('notifications.read');

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('reports/{project}', [ReportController::class, 'show'])->name('reports.show');

    // Account approval (coordinator only)
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users/{user}/approve', [UserController::class, 'approve'])->name('users.approve');
    Route::delete('users/{user}/reject', [UserController::class, 'reject'])->name('users.reject');

    // Profile
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'password'])->name('profile.password');
});
