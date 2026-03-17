@php
    $colors = [
        'Plan' => 'bg-blue-100 text-blue-700',
        'Active' => 'bg-green-100 text-green-700',
        'Phase Out' => 'bg-yellow-100 text-yellow-700',
        'End of Life' => 'bg-red-100 text-red-700',
    ];
    $colorClass = $colors[$stage->value] ?? 'bg-gray-100 text-gray-700';
@endphp
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClass }}">
    {{ $stage->value }}
</span>
