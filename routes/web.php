<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\ComponentTypeController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\ComponentController;
use App\Http\Controllers\ComponentTodoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FactDefinitionController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->name('login.post')->middleware('guest');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/auth/azure/redirect', [SocialiteController::class, 'redirectToAzure'])->name('azure.redirect')->middleware('guest');
Route::get('/auth/azure/callback', [SocialiteController::class, 'handleAzureCallback'])->name('azure.callback');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('components/export', [ExportController::class, 'components'])->name('components.export');
    Route::get('components/import', [ImportController::class, 'create'])->name('components.import.create');
    Route::post('components/import/preview', [ImportController::class, 'preview'])->name('components.import.preview');
    Route::post('components/import', [ImportController::class, 'store'])->name('components.import.store');

    Route::resource('components', ComponentController::class);
    Route::post('components/{component}/relationships', [ComponentController::class, 'storeRelationship'])
        ->name('components.relationships.store');
    Route::delete('components/{component}/relationships/{relationship}', [ComponentController::class, 'destroyRelationship'])
        ->name('components.relationships.destroy');
    Route::post('components/{component}/facts', [ComponentController::class, 'storeFact'])
        ->name('components.facts.store');
    Route::delete('components/{component}/facts/{fact}', [ComponentController::class, 'destroyFact'])
        ->name('components.facts.destroy');

    Route::post('components/{component}/todos', [ComponentTodoController::class, 'store'])
        ->name('components.todos.store');
    Route::patch('components/{component}/todos/{todo}', [ComponentTodoController::class, 'update'])
        ->name('components.todos.update');
    Route::delete('components/{component}/todos/{todo}', [ComponentTodoController::class, 'destroy'])
        ->name('components.todos.destroy');

    Route::resource('fact-definitions', FactDefinitionController::class)->except(['show']);

    Route::resource('users', UserController::class)->except(['show']);

    Route::get('activity', [ActivityController::class, 'index'])->name('activity.index');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::resource('tags', TagController::class)->except(['show', 'create', 'edit']);
        Route::resource('component-types', ComponentTypeController::class)->except(['show', 'create', 'edit'])
            ->parameter('component-types', 'componentType');
    });
});
