{{-- One notification row. $n = DatabaseNotification --}}
@php $d = $n->data; @endphp
<a href="{{ $d['url'] ?? '#' }}" class="flex items-start gap-3 rounded-xl px-3 py-2.5 transition hover:bg-white/5 {{ $n->read_at ? '' : 'bg-brand-600/[0.07]' }}">
    @if (!empty($d['actor_avatar']))
        <img src="{{ $d['actor_avatar'] }}" alt="" class="size-9 shrink-0 rounded-full object-cover ring-1 ring-white/10">
    @else
        <span class="grid size-9 shrink-0 place-items-center rounded-full bg-gradient-to-br from-brand-600 to-brand-800 text-xs font-bold text-white">{{ $d['actor_initials'] ?? '?' }}</span>
    @endif
    <span class="min-w-0 flex-1 text-sm">
        <span class="font-bold text-white">{{ $d['actor_name'] ?? 'Someone' }}</span>
        <span class="text-gray-300">{{ $d['text'] ?? '' }}</span>
        <span class="block text-xs text-gray-500">{{ $n->created_at->diffForHumans() }}</span>
    </span>
    @unless ($n->read_at)
        <span class="mt-2 size-2 shrink-0 rounded-full bg-brand-400" aria-label="Unread"></span>
    @endunless
</a>
