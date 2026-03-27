<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CuestionarioController;
use App\Http\Controllers\FotoController;

Route::post('/subir-foto', [FotoController::class, 'subirFoto']);
Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
Route::post('/login', [UsuarioController::class, 'login'])->name('usuarios.login');
Route::get('/questionaire', function () { return redirect('/questions/datosbiologicos'); });
Route::get('/photos', function () { return redirect('/photos/foto-frontal'); });
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
Route::post('/guardar-cuestionario', [CuestionarioController::class, 'guardar']);
