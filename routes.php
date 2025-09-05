<?php

use Core\http\Router\Route;
use Source\Middleware\AuthMiddleware;
use Source\Controllers\HomeController;
use Source\Controllers\PostController;
use Source\Controllers\UserController;
use Source\Middleware\GuestMiddleware;
use Source\Controllers\AdminController;
use Source\Controllers\LoginController;
use Source\Middleware\RegisterMiddleware;
use Source\Controllers\RegisterController;
use Source\Controllers\AdminSettingsController;
use Source\Controllers\UserProfileController;

return [

    Route::get('/home', [HomeController::class, 'index']),
    Route::get('/', [HomeController::class, 'index']),
    Route::get('/admin/users', [AdminController::class, 'UserList'], [AuthMiddleware::class]),
    Route::get('/register', [RegisterController::class, 'index'], [GuestMiddleware::class]),
    Route::post('/register', [RegisterController::class, 'register']),
    Route::get('/login', [LoginController::class, 'index'], [GuestMiddleware::class]),
    Route::post('/login', [LoginController::class, 'login']),
    Route::post('/logout', [LoginController::class, 'logout']),
    Route::get('/admin/post/create', [PostController::class, 'post_creation'], [AuthMiddleware::class]),
    Route::post('/admin/post/create', [PostController::class, 'create_new_post'], [AuthMiddleware::class]),
    Route::get('/admin/dashboard/general', [AdminController::class, 'dashboardGeneral'], [AuthMiddleware::class]),
    Route::get('/admin/dashboard/users', [AdminController::class, 'dashboardUsers'], [AuthMiddleware::class]),
    Route::post('/admin/dashboard/deleleteuser', [AdminController::class, 'deleteuser'], [AuthMiddleware::class]),
    Route::get('/admin/dashboard/posts', [AdminController::class, 'posts'], [AuthMiddleware::class]),
    Route::get('/admin/user/account', [UserController::class, 'account'], [AuthMiddleware::class]),
    Route::post('/admin/user/add', [UserController::class, 'addNewUser'], [AuthMiddleware::class]),
    Route::post('/user/change-avatar', [UserController::class, 'changeAvatar'], [AuthMiddleware::class]),
    Route::get('/admin/dashboard/settings', [AdminSettingsController::class, 'settings'], [AuthMiddleware::class]),
    Route::get('/admin/profile/{id}', [UserProfileController::class, 'index'], [AuthMiddleware::class])->where(['id' => '[0-9]+']),

];
