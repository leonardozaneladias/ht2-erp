<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Rotas do módulo Rh
|--------------------------------------------------------------------------
|
| Este arquivo é incluído (require) DENTRO do grupo autenticado /admin do core
| (ver App\Support\Modules\ModuleRegistry e routes/admin.php), portanto as rotas
| abaixo já herdam o prefixo /admin, o name "admin." e todo o middleware admin.
| Use prefixos e names RELATIVOS (ex.: Route::prefix('rh')...).
|
*/

// make:modulo insere as rotas do módulo abaixo desta linha
Illuminate\Support\Facades\Route::prefix('rh/departamentos')->name('rh.departamentos.')->group(function (): void {
    Illuminate\Support\Facades\Route::get('/', HT2ERP\Rh\Livewire\Departamentos\IndexDepartamento::class)->name('index');
    Illuminate\Support\Facades\Route::get('/criar', HT2ERP\Rh\Livewire\Departamentos\FormDepartamento::class)->name('create');
    Illuminate\Support\Facades\Route::get('/{departamento}/editar', HT2ERP\Rh\Livewire\Departamentos\FormDepartamento::class)->name('edit');
});
Illuminate\Support\Facades\Route::prefix('rh/funcionarios')->name('rh.funcionarios.')->group(function (): void {
    Illuminate\Support\Facades\Route::get('/', HT2ERP\Rh\Livewire\Funcionarios\IndexFuncionario::class)->name('index');
    Illuminate\Support\Facades\Route::get('/criar', HT2ERP\Rh\Livewire\Funcionarios\FormFuncionario::class)->name('create');
    Illuminate\Support\Facades\Route::get('/{funcionario}/editar', HT2ERP\Rh\Livewire\Funcionarios\FormFuncionario::class)->name('edit');
});
