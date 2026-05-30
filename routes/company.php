<?php
use App\Http\Controllers\Auth\CompanyAuthController;
use App\Http\Controllers\Company\HomeController;
use App\Http\Controllers\Company\WhatsAppController;

Route::get('/login', [CompanyAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [CompanyAuthController::class, 'login'])->name('login.submit');
Route::get('/logout', [CompanyAuthController::class, 'logout'])->name('logout');

Route::get('/lang-change', [HomeController::class, 'changLang'])->name('company.lang.change');

Route::group(['middleware' => ['company']], function () {
    Route::get('/', 'HomeController@index');
    Route::get('/dashboard', 'HomeController@index')->name('dashboard');

    Route::name('companys.')->prefix('companys')->middleware(['company'])->group(function () {
        Route::get('/', 'CompanyUsersController@index')->name('index');
        Route::get('/export', 'CompanyUsersController@export')->name('export');
        Route::get('/show/{id}', 'CompanyUsersController@show')->name('show');
        Route::post('/delete', 'CompanyUsersController@destroy')->name('delete');
        Route::get('/create', 'CompanyUsersController@create')->name('create');
        Route::post('/store', 'CompanyUsersController@store')->name('store');
        Route::get('/edit/{id}', 'CompanyUsersController@edit')->name('edit');
        Route::post('/update', 'CompanyUsersController@update')->name('update');
        Route::get('/editcompany/{id}', 'CompanyUsersController@editcompany')->name('edit.company');
        Route::post('/updatecompany', 'CompanyUsersController@updatecompany')->name('update.company');
    });


});