<?php

namespace App\Livewire;

use App\Models\Achievement;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Prestasi - OtakTangkas')]
class Achievements extends Component
{
    /**
     * All active achievements, ordered for display.
     *
     * @return Collection<int, Achievement>
     */
    #[Computed]
    public function achievements(): Collection
    {
        return Achievement::where('is_active', true)
            ->orderBy('order')
            ->get();
    }

    /**
     * Map of achievement id => unlocked_at for the current user.
     *
     * @return Collection<int, mixed>
     */
    #[Computed]
    public function unlocked(): Collection
    {
        return auth()->user()->achievements()
            ->pluck('user_achievements.unlocked_at', 'achievements.id');
    }

    public function render()
    {
        return view('livewire.achievements');
    }
}
