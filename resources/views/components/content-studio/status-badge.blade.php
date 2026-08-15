@props(['status'])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $label = is_object($status) && method_exists($status, 'label') ? $status->label() : $value;

    [$badgeClasses, $dotClasses] = match ($value) {
        'draft' => ['border-slate-200 bg-slate-100 text-slate-700', 'bg-slate-500'],
        'in_review' => ['border-blue-200 bg-blue-50 text-blue-700', 'bg-blue-500'],
        'changes_requested' => ['border-amber-200 bg-amber-50 text-amber-800', 'bg-amber-500'],
        'approved' => ['border-emerald-200 bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
        'scheduled' => ['border-violet-200 bg-violet-50 text-violet-700', 'bg-violet-500'],
        'published' => ['border-teal-200 bg-teal-50 text-teal-700', 'bg-teal-500'],
        'withdrawn', 'archived', 'cancelled' => ['border-red-200 bg-red-50 text-red-700', 'bg-red-500'],
        default => ['border-slate-200 bg-white text-slate-700', 'bg-slate-400'],
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold', $badgeClasses]) }}>
    <span class="size-1.5 rounded-full {{ $dotClasses }}" aria-hidden="true"></span>
    {{ $label }}
</span>
