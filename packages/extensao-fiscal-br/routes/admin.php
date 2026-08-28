<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas da extensão Fiscal BR
|--------------------------------------------------------------------------
|
| Registradas por ModuleRegistry::routes() no register() do ServiceProvider,
| dentro do grupo autenticado de /admin — herdam prefixo, middleware de tenant,
| 2FA e inatividade sem duplicar nada.
|
*/

Route::prefix('referencia')->name('referencia.')->group(function (): void {
    Route::prefix('cnaes')->name('cnaes.')->group(function (): void {
        Route::get('/', HT2ML\FiscalBr\Livewire\IndexCnae::class)->name('index');
        Route::get('/criar', HT2ML\FiscalBr\Livewire\FormCnae::class)->name('create');
        Route::get('/{cnae}/editar', HT2ML\FiscalBr\Livewire\FormCnae::class)->name('edit');
    });

    Route::prefix('cfops')->name('cfops.')->group(function (): void {
        Route::get('/', HT2ML\FiscalBr\Livewire\IndexCfop::class)->name('index');
        Route::get('/criar', HT2ML\FiscalBr\Livewire\FormCfop::class)->name('create');
        Route::get('/{cfop}/editar', HT2ML\FiscalBr\Livewire\FormCfop::class)->name('edit');
    });

    Route::prefix('ncms')->name('ncms.')->group(function (): void {
        Route::get('/', HT2ML\FiscalBr\Livewire\IndexNcm::class)->name('index');
        Route::get('/criar', HT2ML\FiscalBr\Livewire\FormNcm::class)->name('create');
        Route::get('/{ncm}/editar', HT2ML\FiscalBr\Livewire\FormNcm::class)->name('edit');
    });
});

// make:recurso insere as rotas do recurso abaixo desta linha
