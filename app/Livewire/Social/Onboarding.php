<?php

namespace App\Livewire\Social;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;

/** First-run step for social features: pick a username, short bio and privacy. */
class Onboarding extends Component
{
    public string $username = '';

    public string $bio = '';

    public bool $isPrivate = false;

    #[Url]
    public ?string $next = null;

    public function mount(): void
    {
        $user = auth()->user();
        if ($user->username) {
            $this->redirect($this->safeNext(), navigate: false);

            return;
        }
        $this->username = $this->suggest($user->name);
    }

    public function updatedUsername(): void
    {
        $this->username = strtolower(trim($this->username));
        $this->validateOnly('username');
    }

    protected function rules(): array
    {
        return [
            'username' => [...User::USERNAME_RULES, Rule::notIn(User::RESERVED_USERNAMES), Rule::unique('users', 'username')->ignore(auth()->id())],
            'bio' => ['nullable', 'string', 'max:160'],
            'isPrivate' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return ['username.regex' => 'Use lowercase letters, numbers, dots or underscores.', 'username.not_in' => 'That username is reserved.'];
    }

    public function save()
    {
        $this->username = strtolower(trim($this->username));
        $data = $this->validate();

        auth()->user()->update([
            'username' => $data['username'],
            'bio' => $data['bio'] ?: null,
            'is_private' => $data['isPrivate'],
        ]);

        return $this->redirect($this->safeNext());
    }

    /** Only allow redirects back into this site (no open redirects via ?next=). */
    private function safeNext(): string
    {
        $next = (string) $this->next;

        if (str_starts_with($next, '/') && !str_starts_with($next, '//') && !str_contains($next, '\\')) {
            return $next;
        }

        $host = parse_url($next, PHP_URL_HOST);
        $scheme = parse_url($next, PHP_URL_SCHEME);

        return $host && in_array($scheme, ['http', 'https'], true) && strcasecmp($host, request()->getHost()) === 0
            ? $next
            : route('feed');
    }

    private function suggest(?string $name): string
    {
        $base = Str::of((string) $name)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '')->limit(20, '')->toString() ?: 'movielover';
        $candidate = $base;
        $i = 1;
        while (User::where('username', $candidate)->exists() || in_array($candidate, User::RESERVED_USERNAMES, true) || strlen($candidate) < 3) {
            $candidate = $base . ($i === 1 ? random_int(10, 99) : random_int(100, 9999));
            $i++;
        }

        return $candidate;
    }

    public function render()
    {
        return view('livewire.social.onboarding');
    }
}
