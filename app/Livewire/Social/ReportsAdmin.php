<?php

namespace App\Livewire\Social;

use App\Models\CheckIn;
use App\Models\DiaryEntry;
use App\Models\Report;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

/** /admin/reports — moderation queue (admins only): remove reported content or dismiss. */
class ReportsAdmin extends Component
{
    use WithPagination;

    public function boot(): void
    {
        Gate::authorize('moderate'); // every request, not just page load
    }

    public function dismiss(int $id): void
    {
        Report::findOrFail($id)->update(['resolved_at' => now()]);
    }

    public function removeContent(int $id): void
    {
        $report = Report::findOrFail($id);
        $target = $report->reportable;

        if ($target instanceof CheckIn || $target instanceof DiaryEntry) {
            $target->delete();
        }

        // Resolve every open report about the same item.
        Report::where('reportable_type', $report->reportable_type)->where('reportable_id', $report->reportable_id)
            ->whereNull('resolved_at')->update(['resolved_at' => now()]);
    }

    public function render()
    {
        return view('livewire.social.reports-admin', [
            'reports' => Report::with(['reporter', 'reportable'])->whereNull('resolved_at')->latest()->paginate(20),
        ]);
    }
}
