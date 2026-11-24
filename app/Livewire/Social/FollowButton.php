<?php

namespace App\Livewire\Social;

use App\Models\User;
use App\Services\SocialGraph;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Follow / Requested / Following / Follow back + Block & Report menu. */
class FollowButton extends Component
{
    #[Locked]
    public string $userId;

    public bool $compact = false;

    public bool $menu = true;

    public function mount(User $user, bool $compact = false, bool $menu = true): void
    {
        $this->userId = $user->id;
        $this->compact = $compact;
        $this->menu = $menu;
    }

    public function follow(SocialGraph $graph)
    {
        if (!auth()->check()) {
            return $this->redirectRoute('login');
        }
        $graph->follow(auth()->user(), $this->target());
        $this->dispatch('social-changed');

        return null;
    }

    public function unfollow(SocialGraph $graph): void
    {
        $graph->unfollow(auth()->user(), $this->target()); // also cancels a pending request
        $this->dispatch('social-changed');
    }

    public function block(SocialGraph $graph)
    {
        $graph->block(auth()->user(), $target = $this->target());
        session()->flash('status', "You blocked @{$target->username}. They can’t see your profile or find you.");

        return $this->redirectRoute('feed');
    }

    public function unblock(SocialGraph $graph): void
    {
        $graph->unblock(auth()->user(), $this->target());
    }

    private function target(): User
    {
        return User::findOrFail($this->userId);
    }

    public function render(SocialGraph $graph)
    {
        $target = $this->target();

        return view('livewire.social.follow-button', [
            'target' => $target,
            'r' => $graph->relation(auth()->user(), $target),
        ]);
    }
}
