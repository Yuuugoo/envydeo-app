{{-- Portrait match card (1080×1350) people can post to stories or drop in the group chat. --}}
<aside class="rounded-3xl bg-ink-900/70 p-5 ring-1 ring-white/5"
       x-data="{
            url: @js($cardUrl),
            busy: false,
            async share() {
                this.busy = true;
                try {
                    const blob = await (await fetch(this.url)).blob();
                    const file = new File([blob], 'envydeo-match.png', { type: 'image/png' });
                    if (navigator.canShare && navigator.canShare({ files: [file] })) {
                        await navigator.share({ files: [file], title: 'It’s a match!', text: @js('We picked ' . $title . ' on Envydeo 🍿') });
                        $wire.trackShare('card', 'native');
                    } else {
                        this.download();
                    }
                } catch (e) {
                    // share sheet dismissed: nothing to do
                } finally {
                    this.busy = false;
                }
            },
            download() {
                const a = document.createElement('a');
                a.href = this.url;
                a.download = 'envydeo-match.png';
                a.click();
                $wire.trackShare('card', 'download');
            }
       }">
    <h3 class="text-sm font-bold text-white">Share the moment</h3>
    <p class="mt-1 text-xs text-gray-400">A card for your story or the group chat.</p>
    <button type="button" @click="share()" class="mt-4 block w-full overflow-hidden rounded-2xl ring-1 ring-white/10 transition hover:ring-brand-400/60" aria-label="Share match card">
        <img src="{{ $cardUrl }}" alt="Match card for {{ $title }}" loading="lazy" class="aspect-[4/5] w-full bg-ink-800 object-cover">
    </button>
    <div class="mt-4 grid grid-cols-2 gap-2">
        <button type="button" @click="share()" :disabled="busy" class="btn-brand !px-3 !py-2 text-sm">
            <x-icon name="share" class="size-4" /> Share
        </button>
        <button type="button" @click="download()" class="btn-glass !px-3 !py-2 text-sm">Download</button>
    </div>
</aside>
