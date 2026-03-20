@php
    $rating = $score->rating();
    $value = $score->score();
    $ratingColors = [
        'healthy' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'border' => 'border-green-200'],
        'at_risk' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'border' => 'border-amber-200'],
        'critical' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'border' => 'border-red-200'],
    ];
    $colors = $ratingColors[$rating];
    $ratingLabel = ['healthy' => 'Healthy', 'at_risk' => 'At Risk', 'critical' => 'Critical'][$rating];
    $compact = $compact ?? false;
@endphp

@if ($compact)
    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $colors['bg'] }} {{ $colors['text'] }}">
        {{ $value }}
    </span>
@else
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center gap-4 mb-4">
            <div class="flex items-center justify-center w-14 h-14 rounded-full {{ $colors['bg'] }} {{ $colors['border'] }} border-2 shrink-0">
                <span class="text-xl font-bold {{ $colors['text'] }}">{{ $value }}</span>
            </div>
            <div>
                <p class="text-xs text-gray-500 mb-1">Health Score</p>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colors['bg'] }} {{ $colors['text'] }}">
                    {{ $ratingLabel }}
                </span>
            </div>
        </div>
        <div class="space-y-2">
            @foreach ($score->breakdown() as $item)
                @php
                    $icon = match ($item['status']) {
                        'ok' => ['symbol' => '✓', 'class' => 'text-green-500'],
                        'warn' => ['symbol' => '⚠', 'class' => 'text-amber-500'],
                        default => ['symbol' => '✗', 'class' => 'text-red-500'],
                    };
                @endphp
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2">
                        <span class="text-xs {{ $icon['class'] }}">{{ $icon['symbol'] }}</span>
                        <span class="text-gray-700">{{ $item['label'] }}</span>
                    </div>
                    @if ($item['delta'] < 0)
                        <span class="text-xs text-red-600 font-medium">{{ $item['delta'] }}</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
