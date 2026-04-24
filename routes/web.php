<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\ComponentTypeController;
use App\Http\Controllers\Admin\FactSheetController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\ComponentController;
use App\Http\Controllers\ComponentDocumentController;
use App\Http\Controllers\ComponentFactSheetController;
use App\Http\Controllers\ComponentRoleAssignmentController;
use App\Http\Controllers\ComponentTodoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\RaciController;
use App\Http\Controllers\TeamController;
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
    Route::post('components/{component}/fact-sheets/{factSheet}', [ComponentFactSheetController::class, 'submit'])
        ->name('components.fact-sheets.submit');
    Route::post('components/{component}/role-assignments', [ComponentRoleAssignmentController::class, 'store'])
        ->name('components.role-assignments.store');
    Route::delete('components/{component}/role-assignments/{assignment}', [ComponentRoleAssignmentController::class, 'destroy'])
        ->name('components.role-assignments.destroy');

    Route::post('components/{component}/todos', [ComponentTodoController::class, 'store'])
        ->name('components.todos.store');
    Route::patch('components/{component}/todos/{todo}', [ComponentTodoController::class, 'update'])
        ->name('components.todos.update');
    Route::delete('components/{component}/todos/{todo}', [ComponentTodoController::class, 'destroy'])
        ->name('components.todos.destroy');

    Route::post('components/{component}/documents', [ComponentDocumentController::class, 'store'])
        ->name('components.documents.store');
    Route::get('components/{component}/documents/{document}', [ComponentDocumentController::class, 'show'])
        ->name('components.documents.show');
    Route::delete('components/{component}/documents/{document}', [ComponentDocumentController::class, 'destroy'])
        ->name('components.documents.destroy');

    Route::post('components/{component}/raci/initialize', [RaciController::class, 'initializeMatrix'])
        ->name('components.raci.initialize');
    Route::post('components/{component}/raci/rows', [RaciController::class, 'addRow'])
        ->name('components.raci.rows.store');
    Route::patch('components/{component}/raci/assignments', [RaciController::class, 'updateAssignment'])
        ->name('components.raci.assignments.update');
    Route::delete('raci-rows/{row}', [RaciController::class, 'deleteRow'])
        ->name('raci.rows.destroy');
    Route::patch('raci-rows/{row}/notes', [RaciController::class, 'updateRowNotes'])
        ->name('raci.rows.notes.update');
    Route::patch('raci-columns/{column}/name', [RaciController::class, 'updateColumnName'])
        ->name('raci.columns.name.update');
    Route::post('components/{component}/raci/columns', [RaciController::class, 'addColumn'])
        ->name('components.raci.columns.store');
    Route::delete('raci-columns/{column}', [RaciController::class, 'deleteColumn'])
        ->name('raci.columns.destroy');

    Route::resource('attributes', AttributeController::class)->except(['show']);

    Route::resource('users', UserController::class)->except(['show']);

    Route::resource('teams', TeamController::class);
    Route::post('teams/{team}/members', [TeamController::class, 'addMember'])->name('teams.members.add');
    Route::delete('teams/{team}/members/{user}', [TeamController::class, 'removeMember'])->name('teams.members.remove');

    Route::get('activity', [ActivityController::class, 'index'])->name('activity.index');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::resource('tags', TagController::class)->except(['show', 'create', 'edit']);
        Route::resource('component-types', ComponentTypeController::class)->except(['create', 'edit'])
            ->parameter('component-types', 'componentType');
        Route::patch(
            'component-types/{componentType}/relationship-rules',
            [ComponentTypeController::class, 'updateRelationshipRules']
        )->name('component-types.relationship-rules.update');
        Route::resource('roles', RoleController::class)->except(['show']);
        Route::resource('fact-sheets', FactSheetController::class)
            ->parameter('fact-sheets', 'factSheet');
        Route::post('fact-sheets/{factSheet}/definitions', [FactSheetController::class, 'addDefinition'])
            ->name('fact-sheets.definitions.add');
        Route::delete('fact-sheets/{factSheet}/definitions/{attribute}', [FactSheetController::class, 'removeDefinition'])
            ->name('fact-sheets.definitions.remove');
        Route::patch('fact-sheets/{factSheet}/definitions/{attribute}', [FactSheetController::class, 'updateDefinition'])
            ->name('fact-sheets.definitions.update');
        Route::post('fact-sheets/{factSheet}/conditions', [FactSheetController::class, 'addCondition'])
            ->name('fact-sheets.conditions.add');
        Route::delete('fact-sheets/{factSheet}/conditions/{condition}', [FactSheetController::class, 'removeCondition'])
            ->name('fact-sheets.conditions.remove');
    });
});
