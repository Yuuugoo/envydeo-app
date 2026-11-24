<?php

namespace App\Livewire\Social;

use App\Models\CheckIn;
use App\Models\DiaryEntry;
use App\Models\Report;
use App\Models\User;
use App\Services\SocialGraph;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

/** Global "Report" sheet for users, check-ins and reviews (opened with the `open-report` browser event). */
class ReportModal extends Component
{
    public const TYPES = ['user' => User::class, 'check_in' => CheckIn::class, 'diary_entry' => DiaryEntry::class];

    public bool $open = false;

    public ?string $type = null;

    public ?string $targetId = null;

    public string $reason = 'spam';

    public string $details = '';

    public bool $sent = false;

    #[On('open-report')]
    public function openSheet(string $type, string|int $id): void
    {
        abort_unless(isset(self::TYPES[$type]), 422);
        $this->type = $type;
        $this->targetId = (string) $id;
        $this->reason = $type === 'diary_entry' ? 'spoilers' : 'spam';
        $this->details = '';
        $this->sent = false;
        $this->resetErrorBag();
        $this->open = true;
    }

    public function submit(SocialGraph $graph): void
    {
        $reporter = auth()->user();
        $data = $this->validate([
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
            'reason' => ['required', Rule::in(array_keys(Report::REASONS))],
            'details' => ['nullable', 'string', 'max:500'],
        ]);

        $target = (self::TYPES[$data['type']])::findOrFail($this->targetId);
        $owner = $target instanceof User ? $target : $target->user;

        // You can only report things you can actually see.
        $visible = match (true) {
            $target instanceof CheckIn => $graph->canViewFriendsContent($reporter, $owner),
            $target instanceof DiaryEntry => $graph->canViewProfile($reporter, $owner),
            default => true,
        };
        abort_unless($visible && !$owner->is($reporter), 403);

        if (!RateLimiter::attempt('report:' . $reporter->id, 20, fn () => true, 3600)) {
            $this->addError('reason', 'You’ve sent a lot of reports. Please try again later.');

            return;
        }

        Report::firstOrCreate(
            ['reporter_id' => $reporter->id, 'reportable_type' => $target->getMorphClass(), 'reportable_id' => (string) $target->getKey(), 'resolved_at' => null],
            ['reason' => $data['reason'], 'details' => trim((string) $data['details']) ?: null],
        );

        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.social.report-modal', ['reasons' => Report::REASONS]);
    }
}
