<div class="space-y-4">
    @forelse ($reports as $report)
        @php $target = $report->reportable; @endphp
        <article class="rounded-2xl bg-ink-900/70 p-5 ring-1 ring-white/5" wire:key="report-{{ $report->id }}">
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="rounded-full bg-rose-500/15 px-2.5 py-1 font-bold text-rose-300">{{ \App\Models\Report::REASONS[$report->reason] ?? $report->reason }}</span>
                <span class="text-gray-400">{{ class_basename($report->reportable_type) }} · reported by {{ $report->reporter?->username ? '@' . $report->reporter->username : 'deleted user' }} · {{ $report->created_at->diffForHumans() }}</span>
            </div>
            @if ($report->details)
                <p class="mt-2 text-sm text-gray-300">“{{ $report->details }}”</p>
            @endif

            <div class="mt-4 rounded-xl bg-black/30 p-4 text-sm text-gray-200">
                @if (!$target)
                    <span class="text-gray-500">Content already removed.</span>
                @elseif ($target instanceof \App\Models\CheckIn)
                    <div class="flex gap-4">
                        @if ($target->photo_path)
                            <img src="{{ route('check-ins.photo', $target) }}" alt="Reported photo" class="w-40 rounded-lg">
                        @endif
                        <div>
                            <p><strong>{{ '@' . $target->user->username }}</strong> checked in to {{ $target->movie['title'] ?? '' }}</p>
                            <p class="mt-1 text-gray-400">{{ $target->caption }}</p>
                        </div>
                    </div>
                @elseif ($target instanceof \App\Models\DiaryEntry)
                    <p><strong>{{ '@' . $target->user->username }}</strong> on {{ $target->movie['title'] ?? '' }}</p>
                    <p class="mt-1 whitespace-pre-line text-gray-400">{{ $target->review }}</p>
                @elseif ($target instanceof \App\Models\User)
                    <a href="{{ $target->profileUrl() }}" class="font-semibold text-brand-300">{{ $target->name }} ({{ '@' . $target->username }})</a>
                    <p class="mt-1 text-gray-400">{{ $target->bio }}</p>
                @endif
            </div>

            <div class="mt-4 flex gap-2">
                @if ($target && !$target instanceof \App\Models\User)
                    <button type="button" wire:click="removeContent({{ $report->id }})" wire:confirm="Remove this content for everyone?" class="btn-brand !bg-red-600 !px-4 !py-2 text-sm hover:!bg-red-500">Remove content</button>
                @endif
                <button type="button" wire:click="dismiss({{ $report->id }})" class="btn-glass !px-4 !py-2 text-sm">Dismiss</button>
            </div>
        </article>
    @empty
        <x-empty-state icon="check" title="No open reports" message="Reports from users show up here." />
    @endforelse
    {{ $reports->links() }}
</div>
