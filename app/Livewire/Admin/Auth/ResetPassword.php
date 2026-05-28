<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Auth;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.admin.auth-layout')]
#[Title('Nova senha')]
final class ResetPassword extends Component
{
    public string $token = '';

    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|confirmed|min:8')]
    public string $password = '';

    #[Validate('required')]
    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->string('email', '');
    }

    public function resetPassword(): void
    {
        $this->validate();

        $status = Password::broker('admins')->reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            static function ($user, string $password): void {
                $user->forceFill(['password' => Hash::make($password)])
                    ->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            session()->flash('success', __($status));
            $this->redirect(route('admin.login'), navigate: true);
        } else {
            $this->addError('email', __($status));
        }
    }

    public function render(): View
    {
        return view('livewire.admin.auth.reset-password');
    }
}
