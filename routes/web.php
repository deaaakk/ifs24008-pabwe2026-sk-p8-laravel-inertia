<?php

use App\Features\Admin\Controllers\AdminUserController;
use App\Features\Auth\Controllers\AuthController;
use App\Features\Post\Controllers\AdminPostController;
use App\Features\Post\Controllers\PostController;
use App\Features\Post\Controllers\PublicPostController;
use App\Features\Profile\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tingkat 1 — Publik (Guest): tanpa sidebar, Sticky Top Navbar
|--------------------------------------------------------------------------
*/
Route::get('/', [PublicPostController::class, 'home'])->name('home');
Route::get('/articles', [PublicPostController::class, 'index'])->name('articles.index');
Route::get('/articles/{slug}', [PublicPostController::class, 'show'])->name('articles.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Tingkat 2 — Anggota (Penulis): /dashboard/posts
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function (): void {
    Route::prefix('dashboard/posts')->name('posts.')->group(function (): void {
        // Rute statis harus didaftarkan sebelum rute berparameter {post}.
        Route::get('/', [PostController::class, 'index'])->name('index');
        Route::post('/', [PostController::class, 'store'])->name('store');
        Route::get('/export', [PostController::class, 'export'])->name('export');
        Route::post('/import', [PostController::class, 'import'])->name('import');
        Route::post('/ai-generate', [PostController::class, 'aiGenerate'])->name('ai-generate');
        Route::post('/ai-save', [PostController::class, 'aiSave'])->name('ai-save');

        // Semua rute di bawah ini hanya boleh diakses pemilik artikel (EnsurePostOwnership).
        Route::middleware('post.owner')->where(['post' => '[0-9]+'])->group(function (): void {
            Route::get('/{post}', [PostController::class, 'show'])->name('show');
            Route::put('/{post}', [PostController::class, 'update'])->name('update');
            Route::delete('/{post}', [PostController::class, 'destroy'])->name('destroy');
            Route::patch('/{post}/publish', [PostController::class, 'togglePublish'])->name('publish');
            Route::post('/{post}/cover', [PostController::class, 'uploadCover'])->name('cover.store');
            Route::delete('/{post}/cover', [PostController::class, 'removeCover'])->name('cover.destroy');
        });
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

/*
|--------------------------------------------------------------------------
| Tingkat 3 — Admin: /admin/*
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/posts', [AdminUserController::class, 'posts'])->name('users.posts');
    Route::get('/users/{user}/posts/export', [AdminUserController::class, 'exportPosts'])->name('users.posts.export');
    Route::get('/posts', [AdminPostController::class, 'index'])->name('posts.index');
    Route::get('/posts/export', [AdminPostController::class, 'export'])->name('posts.export');
});
