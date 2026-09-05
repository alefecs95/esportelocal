<?php

use App\Http\Controllers\ShareController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/share/noticias/{slug}', [ShareController::class, 'noticia']);
Route::get('/share/memoria/acervo/{slug}', [ShareController::class, 'memoriaAcervo']);
Route::get('/share/memoria/marcos/{slug}', [ShareController::class, 'memoriaMarco']);
Route::get('/share/memoria/personalidades/{slug}', [ShareController::class, 'memoriaPersonalidade']);
Route::get('/share/memoria/equipes/{slug}', [ShareController::class, 'memoriaEquipe']);
Route::get('/share/memoria/locais/{slug}', [ShareController::class, 'memoriaLocal']);
