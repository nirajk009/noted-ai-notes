<?php

use App\Models\Note;
use Illuminate\Support\Facades\Route;

Route::view('/', 'notes.index')->name('notes.index');

Route::view('/docs', 'docs.api')->name('docs.api');
Route::get('/docs/openapi.json', function () {
    return response()->file(resource_path('api/openapi.json'), ['Content-Type' => 'application/json']);
})->name('docs.openapi');
Route::get('/docs/postman.json', function () {
    return response()->download(resource_path('api/Noted.postman_collection.json'), 'Noted.postman_collection.json', ['Content-Type' => 'application/json']);
})->name('docs.postman');

Route::get('/notes/{note}', function (Note $note) {
    return view('notes.index');
})->whereNumber('note')->name('notes.show');
