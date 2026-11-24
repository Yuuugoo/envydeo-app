<?php

namespace App\Livewire\MovieNight;

use App\Services\MovieNightDealer;
use App\Services\MovieNightRooms;
use App\Services\Tmdb;
use App\Support\MovieNightThemes;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Component;
use RuntimeException;

class CreateRoom extends Component
{
    public string $name = '';

    public string $source = 'discover'; // discover | watchlist

    public ?string $theme = null;

    /** @var array<int|string> */
    public array $genres = [];

    public ?string $maxRuntime = null;

    public ?string $minRating = null;

    public string $region = 'US';

    /** @var array<int|string> */
    public array $providers = [];

    /**
     * Params are named preset* (not theme/source) because Livewire auto-fills public
     * properties with matching names, which would assign null to the typed $source.
     */
    public function mount(?string $presetTheme = null, ?string $presetSource = null): void
    {
        $this->name = (string) (auth()->user()?->name ?? '');
        $this->region = app(\App\Services\ViewerRegion::class)->resolveForMovieNight(request());
        $this->theme = MovieNightThemes::get($presetTheme) ? $presetTheme : null;
        $this->source = $presetSource === 'watchlist' && auth()->check() ? 'watchlist' : 'discover';
    }

    /** Services differ per country, so reset the selection when the country changes. */
    public function updatedRegion(): void
    {
        $this->providers = [];
    }

    public function create(Tmdb $tmdb, MovieNightDealer $dealer, MovieNightRooms $rooms)
    {
        $validGenres = $tmdb->movieGenres()->keys()->all();
        $validProviders = $dealer->providers($this->region)->pluck('id')->all();

        $data = $this->validate([
            'name' => ['required', 'string', 'max:30'],
            'source' => ['required', Rule::in(auth()->check() ? ['discover', 'watchlist'] : ['discover'])],
            'theme' => ['nullable', Rule::in(MovieNightThemes::keys())],
            'genres' => ['array', 'max:10'],
            'genres.*' => [Rule::in($validGenres)],
            'maxRuntime' => ['nullable', Rule::in(array_keys(MovieNightDealer::RUNTIMES))],
            'minRating' => ['nullable', Rule::in(array_keys(MovieNightDealer::RATINGS))],
            'region' => ['required', Rule::in(array_keys(MovieNightDealer::REGIONS))],
            'providers' => ['array', 'max:12'],
            'providers.*' => [Rule::in($validProviders)],
        ], [], ['name' => 'your name']);

        if (!$this->allowAttempt()) {
            return null;
        }

        try {
            $room = $rooms->create([
                'source' => $data['source'],
                'theme' => $data['source'] === 'discover' ? ($data['theme'] ?: null) : null,
                'filters' => [
                    'genres' => array_map('intval', $data['genres']),
                    'max_runtime' => $data['maxRuntime'] ? (int) $data['maxRuntime'] : null,
                    'min_rating' => $data['minRating'] ? (float) $data['minRating'] : null,
                    'region' => $data['region'],
                    'providers' => array_map('intval', $data['providers']),
                ],
            ], $data['name'], auth()->user());
        } catch (RuntimeException $e) {
            $this->addError('form', $e->getMessage());

            return null;
        }

        return $this->redirectRoute('movie-night.show', $room->code);
    }

    private function allowAttempt(): bool
    {
        $key = 'movie-night:create:' . request()->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('form', 'You’re creating rooms too quickly. Please wait a minute and try again.');

            return false;
        }
        RateLimiter::hit($key, 60);

        return true;
    }

    public function render()
    {
        return view('livewire.movie-night.create-room', [
            'genreOptions' => app(Tmdb::class)->movieGenres(),
            'providerOptions' => app(MovieNightDealer::class)->providers($this->region),
            'regions' => MovieNightDealer::REGIONS,
            'runtimes' => MovieNightDealer::RUNTIMES,
            'ratings' => MovieNightDealer::RATINGS,
            'themes' => MovieNightThemes::all(),
        ]);
    }
}
