<?php

namespace App\Livewire\Social;

use App\Models\Activity;
use App\Models\DiaryEntry;
use App\Support\TitleSnapshot;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** "Log / Rate" on movie & TV pages: ½-star rating, optional review (with spoiler flag), watch date. */
class LogTitle extends Component
{
    #[Locked]
    public int $tmdbId;

    #[Locked]
    public string $type = 'movie';

    public bool $open = false;

    public ?int $editingId = null;

    public int $rating = 0; // 0 = no rating, 1–10 = ½–5 stars

    public string $review = '';

    public bool $hasSpoilers = false;

    public string $watchedOn = '';

    public function mount(int $tmdbId, string $type = 'movie'): void
    {
        $this->tmdbId = $tmdbId;
        $this->type = $type === 'tv' ? 'tv' : 'movie';
    }

    public function start(?int $entryId = null)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->redirectRoute('login');
        }
        if (!$user->username) {
            return $this->redirectRoute('welcome', ['next' => url()->previous()]);
        }

        $entry = $entryId ? DiaryEntry::where('user_id', $user->id)->findOrFail($entryId) : null;
        $this->editingId = $entry?->id;
        $this->rating = $entry?->rating ?? 0;
        $this->review = $entry?->review ?? '';
        $this->hasSpoilers = $entry?->has_spoilers ?? false;
        $this->watchedOn = ($entry?->watched_on ?? now())->toDateString();
        $this->resetErrorBag();
        $this->open = true;

        return null;
    }

    public function save(): void
    {
        $user = auth()->user();
        abort_unless($user?->username, 403);

        $data = $this->validate([
            'rating' => ['integer', 'between:0,10'],
            'review' => ['nullable', 'string', 'max:2000'],
            'hasSpoilers' => ['boolean'],
            'watchedOn' => ['required', 'date', 'before_or_equal:today', 'after:1900-01-01'],
        ]);

        if (!$data['rating'] && !trim((string) $data['review'])) {
            $this->addError('rating', 'Add a rating or a few words.');

            return;
        }

        $snapshot = TitleSnapshot::fetch($this->tmdbId, $this->type);
        abort_unless($snapshot, 404);

        $attributes = [
            'rating' => $data['rating'] ?: null,
            'review' => trim((string) $data['review']) ?: null,
            'has_spoilers' => (bool) $data['hasSpoilers'],
            'watched_on' => $data['watchedOn'],
            'movie' => $snapshot,
        ];

        if ($this->editingId) {
            DiaryEntry::where('user_id', $user->id)->findOrFail($this->editingId)->update($attributes);
        } else {
            $entry = DiaryEntry::create($attributes + ['user_id' => $user->id, 'tmdb_id' => $this->tmdbId, 'media_type' => $this->type]);
            Activity::record($user, 'logged', $entry, ['movie' => $snapshot, 'rating' => $entry->rating]);
        }

        $this->open = false;
        $this->dispatch('diary-updated');
    }

    public function delete(): void
    {
        DiaryEntry::where('user_id', auth()->id())->findOrFail($this->editingId)->delete();
        $this->open = false;
        $this->dispatch('diary-updated');
    }

    public function render()
    {
        $latest = auth()->check()
            ? DiaryEntry::where('user_id', auth()->id())->where('tmdb_id', $this->tmdbId)->where('media_type', $this->type)->latest('watched_on')->latest()->first()
            : null;

        return view('livewire.social.log-title', ['latest' => $latest]);
    }
}
