<?php

use App\Http\Controllers\Api\V1\AttributeController;
use App\Http\Controllers\Api\V1\ComponentController;
use App\Http\Controllers\Api\V1\ComponentFactController;
use App\Http\Controllers\Api\V1\ComponentRelationshipController;
use App\Http\Controllers\Api\V1\TagController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('v1')->name('api.v1.')->group(function () {
    Route::apiResource('components', ComponentController::class);
    Route::apiResource('components.relationships', ComponentRelationshipController::class)
        ->only(['index', 'store', 'show', 'destroy']);
    Route::apiResource('components.facts', ComponentFactController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::apiResource('attributes', AttributeController::class);
    Route::get('tags', [TagController::class, 'index'])->name('tags.index');
    Route::post('tags', [TagController::class, 'store'])->name('tags.store');
});
