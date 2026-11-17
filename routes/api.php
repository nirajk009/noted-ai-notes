<?php

use App\Http\Controllers\NoteAiController;
use App\Http\Controllers\NoteController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:notes')->group(function () {
    Route::get('/notes', [NoteController::class, 'index']);
    Route::get('/notes/search', [NoteAiController::class, 'search'])->middleware('throttle:ai');
    Route::post('/notes/summary', [NoteAiController::class, 'summaryFromText'])->middleware('throttle:ai');
    Route::get('/notes/{note}', [NoteController::class, 'show'])->whereNumber('note');
    Route::post('/notes', [NoteController::class, 'store'])->middleware('throttle:ai');
    Route::match(['put', 'patch'], '/notes/{note}', [NoteController::class, 'update'])->whereNumber('note')->middleware('throttle:ai');
    Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->whereNumber('note');
    Route::post('/notes/{note}/summary', [NoteAiController::class, 'summary'])->whereNumber('note')->middleware('throttle:ai');
    Route::post('/notes/{note}/embedding', [NoteAiController::class, 'embedding'])->whereNumber('note')->middleware('throttle:ai');
});
