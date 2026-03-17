@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('header-actions')
    <a href="{{ route('components.create') }}"
       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        New Component
    </a>
@endsection

@section('content')
    {{-- Summary cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500 mb-1">Total Components</p>
            <p class="text-3xl font-bold text-gray-900">{{ $totalComponents }}</p>
        </div>

        @foreach ($types as $type)
            @php
                $colors = [
                    'Application' => 'bg-blue-50 border-blue-200',
                    'Interface' => 'bg-purple-50 border-purple-200',
                    'Data Object' => 'bg-green-50 border-green-200',
                    'IT Component' => 'bg-orange-50 border-orange-200',
                    'Provider' => 'bg-teal-50 border-teal-200',
                    'Process' => 'bg-yellow-50 border-yellow-200',
                    'Business Capability' => 'bg-red-50 border-red-200',
                ];
                $cardClass = $colors[$type->value] ?? 'bg-gray-50 border-gray-200';
                $count = $countsByType[$type->value] ?? 0;
            @endphp
            <div class="rounded-xl border p-5 {{ $cardClass }}">
                <p class="text-xs text-gray-500 mb-1">{{ $type->value }}</p>
                <p class="text-2xl font-bold text-gray-900">{{ $count }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-3 gap-6">
        {{-- Lifecycle Distribution --}}
        <div class="col-span-1 bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="font-semibold text-gray-800 mb-4">Lifecycle Distribution</h2>
            @php
                $hasLifecycleData = collect($countsByLifecycle)->sum() > 0;
            @endphp
            @if ($hasLifecycleData)
                <div class="space-y-3">
                    @foreach ($lifecycleStages as $stage)
                        @php
                            $count = $countsByLifecycle[$stage->value] ?? 0;
                            $pct = $totalComponents > 0 ? round(($count / $totalComponents) * 100) : 0;
                            $barColors = [
                                'Plan' => 'bg-blue-500',
                                'Active' => 'bg-green-500',
                                'Phase Out' => 'bg-yellow-500',
                                'End of Life' => 'bg-red-500',
                            ];
                            $barColor = $barColors[$stage->value] ?? 'bg-gray-400';
                        @endphp
                        <div>
                            <div class="flex justify-between text-xs text-gray-600 mb-1">
                                <span>{{ $stage->value }}</span>
                                <span>{{ $count }}</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2">
                                <div class="h-2 rounded-full {{ $barColor }}" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                    @php
                        $unset = $totalComponents - collect($countsByLifecycle)->sum();
                    @endphp
                    @if ($unset > 0)
                        <div>
                            <div class="flex justify-between text-xs text-gray-400 mb-1">
                                <span>Not set</span>
                                <span>{{ $unset }}</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2">
                                <div class="h-2 rounded-full bg-gray-300" style="width: {{ $totalComponents > 0 ? round(($unset / $totalComponents) * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <p class="text-sm text-gray-400 text-center py-6">No lifecycle data yet.<br>
                    <a href="{{ route('components.index') }}" class="text-blue-600 hover:underline text-xs mt-1 inline-block">Set lifecycle stages</a>
                </p>
            @endif
        </div>

        {{-- Recently Updated --}}
        <div class="col-span-2 bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Recently Updated</h2>
            </div>
            @if ($recentComponents->isNotEmpty())
                <div class="divide-y divide-gray-50">
                    @foreach ($recentComponents as $component)
                        <div class="flex items-center justify-between px-5 py-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <a href="{{ route('components.show', $component) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800 truncate">
                                    {{ $component->name }}
                                </a>
                                @include('components._type-badge', ['type' => $component->type])
                                @if ($component->lifecycle_stage)
                                    @include('components._lifecycle-badge', ['stage' => $component->lifecycle_stage])
                                @endif
                                @foreach ($component->tags as $tag)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ $tag->name }}</span>
                                @endforeach
                            </div>
                            <span class="text-xs text-gray-400 shrink-0 ml-4">{{ $component->updated_at->diffForHumans() }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-5 py-10 text-center">
                    <p class="text-sm text-gray-400">No components yet.</p>
                    <a href="{{ route('components.create') }}" class="text-sm text-blue-600 hover:underline mt-1 inline-block">Create your first component</a>
                </div>
            @endif
        </div>
    </div>

    {{-- Top Tags --}}
    @if ($topTags->isNotEmpty())
        <div class="mt-6 bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="font-semibold text-gray-800 mb-4">Top Tags</h2>
            <div class="flex flex-wrap gap-2">
                @foreach ($topTags as $tag)
                    <a href="{{ route('components.index', ['tag' => $tag->name]) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors">
                        {{ $tag->name }}
                        <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-300 text-gray-600 text-xs">{{ $tag->components_count }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
@endsection
