<?php

namespace App\Livewire\Social;

use App\Models\Activity;
use App\Models\CheckIn;
use App\Models\User;
use App\Notifications\FriendCheckedIn;
use App\Services\CheckInPhotos;
use App\Services\SocialGraph;
use App\Support\TitleSnapshot;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

/**
 * "Tonight" check-in sheet (included once per layout for signed-in users).
 * Opens on the `open-check-in` browser event, optionally pre-filled with {tmdbId, type}.
 */
class CheckInComposer extends Component
{
    use WithFileUploads;

    public bool $open = false;

    public string $query = '';

    public ?array $selected = null;

    public string $caption = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $photo = null;

    #[On('open-check-in')]
    public function openSheet(?int $tmdbId = null, ?string $type = null): void
    {
        $this->reset(['query', 'selected', 'caption', 'photo']);
        $this->resetErrorBag();
        if ($tmdbId) {
            $this->selected = TitleSnapshot::fetch($tmdbId, $type ?? 'movie');
        }
        $this->open = true;
    }

    public function choose(int $id, string $type): void
    {
        $this->selected = TitleSnapshot::fetch($id, $type);
        $this->query = '';
    }

    public function clearTitle(): void
    {
        $this->selected = null;
    }

    public function removePhoto(): void
    {
        $this->photo = null;
    }

    public function share(CheckInPhotos $photos, SocialGraph $graph)
    {
        $user = auth()->user();
        abort_unless($user?->username, 403);

        $this->validate([
            'selected' => ['required', 'array'],
            'caption' => ['nullable', 'string', 'max:140'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:12288'],
        ], ['selected.required' => 'Pick what you’re watching.']);

        $key = 'check-in:' . $user->id;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('caption', 'That’s a lot of check-ins! Try again in a bit.');

            return null;
        }
        RateLimiter::hit($key, 3600);

        try {
            $path = $this->photo ? $photos->store($this->photo, $user->id) : null;
        } catch (RuntimeException $e) {
            $this->addError('photo', $e->getMessage());

            return null;
        }

        $checkIn = CheckIn::create([
            'user_id' => $user->id,
            'tmdb_id' => $this->selected['id'],
            'media_type' => $this->selected['type'],
            'movie' => $this->selected,
            'caption' => trim($this->caption) ?: null,
            'photo_path' => $path,
            'expires_at' => now()->addDay(),
        ]);

        Activity::record($user, 'checked_in', $checkIn, ['movie' => $this->selected, 'caption' => $checkIn->caption], 'friends');

        // The Locket moment: friends get "Ana is watching Dune 🍿" on their phones.
        $friends = User::whereIn('id', array_diff($graph->friendIds($user), $graph->hiddenIds($user)))
            ->where('notify_friend_checkins', true)->get();
        Notification::send($friends, new FriendCheckedIn($user, $checkIn));

        $this->open = false;
        $this->reset(['query', 'selected', 'caption', 'photo']);

        return $this->redirectRoute('feed', ['tonight' => $checkIn->id]);
    }

    public function render(SocialGraph $graph)
    {
        $user = auth()->user();

        return view('livewire.social.check-in-composer', [
            'results' => $this->open && !$this->selected ? TitleSnapshot::search($this->query) : [],
            'friendCount' => $user ? count($graph->friendIds($user)) : 0,
        ]);
    }
}
