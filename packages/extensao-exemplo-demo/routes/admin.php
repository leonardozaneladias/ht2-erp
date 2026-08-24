<?php

declare(strict_types=1);

use HT2ML\ExemploDemo\Livewire\Exemplos\FormExemplo;
use HT2ML\ExemploDemo\Livewire\Exemplos\IndexExemplo;
use Illuminate\Support\Facades\Route;

/*
| Carregado pelo ModuleRegistry DENTRO do grupo admin do core, então herda
| prefixo /admin, nome "admin." e todo o middleware.
*/
Route::prefix('exemplos')->name('exemplos.')->group(function (): void {
    Route::get('/', IndexExemplo::class)->name('index');
    Route::get('/criar', FormExemplo::class)->name('create');
    Route::get('/{exemplo}/editar', FormExemplo::class)->name('edit');
});
