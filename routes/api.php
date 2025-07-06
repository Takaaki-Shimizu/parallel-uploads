<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileUploadController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('files')->group(function () {
    Route::get('/', [FileUploadController::class, 'index']);
    Route::post('/upload', [FileUploadController::class, 'upload']);
    Route::post('/{fileUpload}/process', [FileUploadController::class, 'processUpload']);
    Route::post('/{fileUpload}/retry', [FileUploadController::class, 'retry']);
    Route::get('/{fileUpload}/status', [FileUploadController::class, 'status']);
    Route::get('/{fileUpload}/download', [FileUploadController::class, 'download']);
    Route::delete('/{fileUpload}', [FileUploadController::class, 'delete']);
});
