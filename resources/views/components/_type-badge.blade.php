@php
    $colors = [
        'Application' => 'bg-blue-100 text-blue-700',
        'Interface' => 'bg-purple-100 text-purple-700',
        'Data Object' => 'bg-green-100 text-green-700',
        'IT Component' => 'bg-orange-100 text-orange-700',
        'Provider' => 'bg-teal-100 text-teal-700',
        'Process' => 'bg-yellow-100 text-yellow-700',
        'Business Capability' => 'bg-red-100 text-red-700',
    ];
    $colorClass = $colors[$type->value] ?? 'bg-gray-100 text-gray-700';
@endphp
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClass }}">
    {{ $type->value }}
</span>
