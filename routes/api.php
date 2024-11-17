<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\JWTValidation;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\CollectionController;

Route::match(array('GET', 'POST'),'token', [TokenController::class, 'getToken']);

Route::group(['middleware' => JWTValidation::class], function () {
    Route::match(array('GET', 'POST'),'collections/id/{id}', [CollectionController::class, 'getById']);
    Route::match(array('GET', 'POST'),'collections/files/{id}', [CollectionController::class, 'getFile']);
    Route::match(array('GET', 'POST'),'collections/list', [CollectionController::class, 'getList']);
    //Route::match(array('GET', 'POST'),'collections/id/{id}', [CollectionController::class, 'getById']);
    //Route::match(array('GET', 'POST'),'/permohonan/tracking/{noresi}', [PermohonanController::class, 'tracking']);
    //Route::match(array('GET', 'POST'), '/permohonan/submit', [PermohonanController::class, 'submit']);
    //Route::match(array('GET', 'POST'), '/permohonan/perbaikan/{noresi}', [PermohonanController::class, 'perbaikan']);
    //Route::match(array('GET', 'POST'), '/penerbit/detail', [PenerbitController::class, 'detail']);

    //Route::match(array('GET', 'POST'), '/isbn/data', [ISBNController::class, 'data']);
});
