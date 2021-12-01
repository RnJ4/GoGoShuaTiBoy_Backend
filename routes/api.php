<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api;
use App\Http\Controllers\questionController;
use App\Http\Controllers\commentController;
use \App\Http\Controllers\favouriteController;
use App\Http\Controllers\likeController;
use App\Http\Controllers\user;

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

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});
Route::post('/wxLogin',[user::class,'wxLogin']);
Route::post('/syncUserInfo',[user::class,'syncUserInfo']);

Route::get('/getQuestion',[questionController::class,'getQuestion']);
Route::get('/getRandomQuestion',[questionController::class,'getRandomQuestionFromBook']);
Route::get('/getUser',[user::class,'getUser']);
Route::any('/getBooks',[questionController::class,'getBooks']);

Route::get('getFavouritesAll',[favouriteController::class,'getFavouritesAll']);
Route::any('addFavourite',[favouriteController::class,'addFavourite']);
Route::any('removeFavourite',[favouriteController::class,'removeFavourite']);

Route::any('postComment',[commentController::class,'postComment']);
Route::any('postCommentBook',[commentController::class,'postCommentBook']);
Route::any('getComment',[commentController::class,'getComment']);
Route::any('getAllReply',[commentController::class,'getAllReply']);

Route::any('addLike',[likeController::class,'addLike']);

Route::any('/saveUserRecord',[questionController::class,'saveUserRecord']);
Route::any('/getMostWrongPersonal',[questionController::class,'getMostWrongPersonal']);
Route::any('/getMostWrongGlobal',[questionController::class,'getMostWrongGlobal']);
