<?php

use App\Http\Controllers\TaskController;
use App\Models\Project;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', [TaskController::class, 'index'])->name('tasks.index');
Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
Route::post('/tasks/reorder', [TaskController::class, 'reorder'])->name('tasks.reorder');
Route::get('/tasks/reorder', function () {
    return redirect()->route('tasks.index');
});

// Quick route to create a project
Route::post('/projects', function (Request $request) {
    $request->validate(['name' => 'required|string|max:255']);
    Project::create(['name' => $request->name]);
    return redirect()->back()->with('success', 'Project created successfully.');
})->name('projects.store');
