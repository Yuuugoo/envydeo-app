<?php

namespace App\Livewire\Social;

use App\Models\Follow;
use App\Models\User;
use App\Support\TitleSnapshot;
use Illuminate\Validation\Rule;
use Livewire\Component;

/** Social profile settings: username, bio, privacy and "Always down to watch" picks. */
class EditProfile extends Component
{
    public string $username = '';

    public string $bio = '';

    public bool $isPrivate = false;

    public array $topFilms = [];

    public string $search = '';

    public bool $saved = false;

    public function mount(): void
    {
        $user = auth()->user();
        $this->username = (string) $user->username;
        $this->bio = (string) $user->bio;
        $this->isPrivate = $user->is_private;
        $this->topFilms = array_values($user->top_films ?? []);
    }

    public function addFilm(int $id, string $type): void
    {
        if (count($this->topFilms) >= 4 || collect($this->topFilms)->contains('id', $id)) {
            return;
        }
        if ($snapshot = TitleSnapshot::fetch($id, $type)) {
            $this->topFilms[] = $snapshot;
        }
        $this->search = '';
    }

    public function removeFilm(int $index): void
    {
        unset($this->topFilms[$index]);
        $this->topFilms = array_values($this->topFilms);
    }

    public function save(): void
    {
        $user = auth()->user();
        $this->username = strtolower(trim($this->username));

        $data = $this->validate([
            'username' => [...User::USERNAME_RULES, Rule::notIn(User::RESERVED_USERNAMES), Rule::unique('users', 'username')->ignore($user->id)],
            'bio' => ['nullable', 'string', 'max:160'],
            'isPrivate' => ['boolean'],
            'topFilms' => ['array', 'max:4'],
        ], ['username.regex' => 'Use lowercase letters, numbers, dots or underscores.']);

        $wasPrivate = $user->is_private;
        $user->update([
            'username' => $data['username'],
            'bio' => $data['bio'] ?: null,
            'is_private' => $data['isPrivate'],
            // Only keep snapshot fields we generated (never trust client-sent arrays as-is).
            'top_films' => collect($this->topFilms)->map(fn ($f) => TitleSnapshot::fetch((int) $f['id'], $f['type'] ?? 'movie'))->filter()->values()->all(),
        ]);

        // Going public approves everyone who was waiting.
        if ($wasPrivate && !$data['isPrivate']) {
            Follow::where('followee_id', $user->id)->where('status', 'pending')->update(['status' => 'accepted']);
        }

        $this->saved = true;
    }

    public function render()
    {
        return view('livewire.social.edit-profile', [
            'results' => count($this->topFilms) < 4 ? array_values(array_filter(TitleSnapshot::search($this->search, 8), fn ($r) => $r['type'] === 'movie')) : [],
        ]);
    }
}
