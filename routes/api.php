<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MazeController;
use Illuminate\Support\Facades\DB;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/ping', function () {
    return ['pong' => true];
});

// Route::get('/env', function () {
//     return $_ENV;
// });

// Route::get('/check', function() {
//     try {
//         DB::connection()->getPdo();
//     } catch (\Exception $e) {
//         return("Could not connect to the database.  Please check your configuration. error:" . $e );
//     }
// });

Route::post('/user', [AuthController::class, 'register']);
Route::middleware('api')->post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('api')->post('/maze', [MazeController::class, 'save']);
Route::middleware('api')->get('/maze', [MazeController::class, 'list']);
Route::middleware('api')->get('/maze/{mazeId}/solution', [MazeController::class, 'solution']);

