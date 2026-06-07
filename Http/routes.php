<?php

/*
|--------------------------------------------------------------------------
| Module Routes (SampleModule style)
|--------------------------------------------------------------------------
| These routes are intended to be included by FreeScout when the module is loaded.
*/
Route::group(['middleware' => ['web'], 'namespace' => 'Modules\MSTeamsSso\Http\Controllers'], function () {
    Route::get('/teams-entry', ['uses' => 'TeamsSsoController@entry'])->name('msteams.entry');
    Route::post('/teams-sso-login', ['uses' =>'TeamsSsoController@login'])->name('msteams.login');
    Route::get('/teams-fallback', ['uses' =>'TeamsSsoController@fallback'])->name('msteams.fallback');
});

Route::group(['middleware' => ['web', 'auth', 'roles'], 'roles' => ['admin'], 'namespace' => 'Modules\MSTeamsSso\Http\Controllers'], function () {
    Route::post('/admin/msteamssso/settings/save', 'MSTeamsSsoController@saveSettings')->name('msteamssso.settings.save');
    Route::post('/admin/msteamssso/license/manage', 'MSTeamsSsoController@manageLicense')->name('msteamssso.license.manage');
    Route::post('/admin/msteamssso/module-license-action', 'MSTeamsSsoController@handleModuleLicenseAction')->name('msteamssso.module.license.action');
});
