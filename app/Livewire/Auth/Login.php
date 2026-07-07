<?php

namespace App\Livewire\Auth;

use App\Services\Progression\AchievementService;
use App\Services\Progression\StreakService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Masuk - OtakTangkas')]
class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function login(StreakService $streaks, AchievementService $achievements)
    {
        $this->validate();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            throw ValidationException::withMessages([
                'email' => __('Email atau kata sandi salah.'),
            ]);
        }

        session()->regenerate();

        // Update the daily login streak and unlock any streak achievements.
        $streaks->recordLogin(Auth::user());
        $achievements->evaluate(Auth::user());

        return $this->redirectIntended(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
