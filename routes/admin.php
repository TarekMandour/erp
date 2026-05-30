<?php
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\AdminsController;

Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
Route::get('/logout', [AdminAuthController::class, 'logout'])->name('logout');

Route::get('/lang-change', [HomeController::class, 'changLang'])->name('admin.lang.change');

Route::group(['middleware' => ['admin']], function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::name('settings.')->prefix('settings')->group(function () {
        Route::get('/edit/{id}', 'SettingsController@edit')->name('edit');
        Route::post('/update', 'SettingsController@update')->name('update');
    });

    Route::name('admins.')->prefix('admins')->group(function () {
        Route::get('/', [AdminsController::class, 'index'])->name('index');
        Route::get('/export', [AdminsController::class, 'export'])->name('export');
        Route::get('/show/{id}', [AdminsController::class, 'show'])->name('show');
        Route::post('/delete', [AdminsController::class, 'destroy'])->name('delete');
        Route::get('/create', [AdminsController::class, 'create'])->name('create');
        Route::post('/store', [AdminsController::class, 'store'])->name('store');
        Route::get('/edit/{id}', [AdminsController::class, 'edit'])->name('edit');
        Route::post('/update', [AdminsController::class, 'update'])->name('update');
    });

    Route::name('companys.')->prefix('companys')->group(function () {
        Route::get('/', 'CompaniesController@index')->name('index');
        Route::get('/export', 'CompaniesController@export')->name('export');
        Route::get('/show/{id}', 'CompaniesController@show')->name('show');
        Route::post('/delete', 'CompaniesController@destroy')->name('delete');
        Route::get('/create', 'CompaniesController@create')->name('create');
        Route::post('/store', 'CompaniesController@store')->name('store');
        Route::get('/edit/{id}', 'CompaniesController@edit')->name('edit');
        Route::post('/update', 'CompaniesController@update')->name('update');
    });

    Route::name('companyusers.')->prefix('companyusers')->group(function () {
        Route::get('/', 'CompanyUsersController@index')->name('index');
        Route::get('/export', 'CompanyUsersController@export')->name('export');
        Route::get('/show/{id}', 'CompanyUsersController@show')->name('show');
        Route::post('/delete', 'CompanyUsersController@destroy')->name('delete');
        Route::get('/create', 'CompanyUsersController@create')->name('create');
        Route::post('/store', 'CompanyUsersController@store')->name('store');
        Route::get('/edit/{id}', 'CompanyUsersController@edit')->name('edit');
        Route::post('/update', 'CompanyUsersController@update')->name('update');
    });

    Route::name('sliders.')->prefix('sliders')->group(function () {
        Route::get('/', 'SlidersController@index')->name('index');
        Route::get('/export', 'SlidersController@export')->name('export');
        Route::get('/show/{id}', 'SlidersController@show')->name('show');
        Route::post('/delete', 'SlidersController@destroy')->name('delete');
        Route::get('/create', 'SlidersController@create')->name('create');
        Route::post('/store', 'SlidersController@store')->name('store');
        Route::get('/edit/{id}', 'SlidersController@edit')->name('edit');
        Route::post('/update', 'SlidersController@update')->name('update');
    });

    Route::name('category.')->prefix('category')->group(function () {
        Route::get('/', 'CategoryController@index')->name('index');
        Route::get('/export', 'CategoryController@export')->name('export');
        Route::get('/show/{id}', 'CategoryController@show')->name('show');
        Route::post('/delete', 'CategoryController@destroy')->name('delete');
        Route::get('/create', 'CategoryController@create')->name('create');
        Route::post('/store', 'CategoryController@store')->name('store');
        Route::get('/edit/{id}', 'CategoryController@edit')->name('edit');
        Route::post('/update', 'CategoryController@update')->name('update');
    });

    Route::name('blogs.')->prefix('blogs')->group(function () {
        Route::get('/', 'BlogsController@index')->name('index');
        Route::get('/export', 'BlogsController@export')->name('export');
        Route::get('/show/{id}', 'BlogsController@show')->name('show');
        Route::post('/delete', 'BlogsController@destroy')->name('delete');
        Route::get('/create', 'BlogsController@create')->name('create');
        Route::post('/store', 'BlogsController@store')->name('store');
        Route::get('/edit/{id}', 'BlogsController@edit')->name('edit');
        Route::post('/update', 'BlogsController@update')->name('update');
    });

    Route::name('contacts.')->prefix('contacts')->group(function () {
        Route::get('/', 'ContactController@index')->name('index');
        Route::get('/export', 'ContactController@export')->name('export');
        Route::get('/show/{id}', 'ContactController@show')->name('show');
        Route::post('/delete', 'ContactController@destroy')->name('delete');
        Route::get('/create', 'ContactController@create')->name('create');
        Route::post('/store', 'ContactController@store')->name('store');
        Route::get('/edit/{id}', 'ContactController@edit')->name('edit');
        Route::post('/update', 'ContactController@update')->name('update');
    });

    Route::name('pages.')->prefix('pages')->group(function () {
        Route::get('/', 'PageController@index')->name('index');
        Route::get('/export', 'PageController@export')->name('export');
        Route::get('/show/{id}', 'PageController@show')->name('show');
        Route::post('/delete', 'PageController@destroy')->name('delete');
        Route::get('/create', 'PageController@create')->name('create');
        Route::post('/store', 'PageController@store')->name('store');
        Route::get('/edit/{id}', 'PageController@edit')->name('edit');
        Route::post('/update', 'PageController@update')->name('update');
    });

    Route::name('users.')->prefix('users')->group(function () {
        Route::get('/', 'UsersController@index')->name('index');
        Route::get('/export', 'UsersController@export')->name('export');
        Route::get('/show/{id}', 'UsersController@show')->name('show');
        Route::post('/delete', 'UsersController@destroy')->name('delete');
        Route::get('/create', 'UsersController@create')->name('create');
        Route::post('/store', 'UsersController@store')->name('store');
        Route::get('/edit/{id}', 'UsersController@edit')->name('edit');
        Route::post('/update', 'UsersController@update')->name('update');
    });

});
