<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use Livewire\Component;

final class ExemploCounter extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }

    public function decrement(): void
    {
        if ($this->count > 0) {
            $this->count--;
        }
    }

    public function resetar(): void
    {
        $this->count = 0;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.exemplo-counter');
    }
}
