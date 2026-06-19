<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CuestionarioController;
use App\Http\Controllers\FotoController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DatofotoController;
use App\Http\Controllers\ComparisonController;

Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
Route::post('/login', [UsuarioController::class, 'login'])->name('usuarios.login');
Route::get('/questionaire', function () { return redirect('/questions/datosbiologicos'); });
Route::statamic('/photos/menu', 'photos/menu');
Route::statamic('analysis/menu_analysis', 'analysis/menu_analysis');
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
Route::post('/guardar-cuestionario', [CuestionarioController::class, 'guardar']);
Route::post('/subir-foto', [FotoController::class, 'subirFoto'])->middleware('web');
Route::statamic('/privacy', 'legal/privacy');

Route::post('/analysis/store-json', [AnalysisController::class, 'storeJson']);

Route::get('/cuestionario/{user_id}', function($user_id) {
    return \App\Models\Cuestionario::where('user_id', $user_id)->latest()->first();
});

Route::get('/fotos-validadas/{user_id}', function($user_id) {
    return \App\Models\Foto::where('user_id', $user_id)
                           ->where('valida', true)
                           ->get();
});
Route::post('/chat/enviar', [ChatController::class, 'enviar']);

Route::post('/datofotos/guardar', [DatofotoController::class, 'guardar']);

Route::post('/cuenta/eliminar', [AnalysisController::class, 'destroyAccount']);

Route::statamic('/analysis/evolution', '/analysis/evolution');

Route::post('/comparison/run', [ComparisonController::class, 'compare']);

