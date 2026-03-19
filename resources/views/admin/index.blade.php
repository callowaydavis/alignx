@extends('layouts.app')

@section('title', 'Admin')
@section('heading', 'Admin Panel')

@section('content')
    {{-- Stats overview --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @foreach ([
            ['label' => 'Components', 'value' => $totalComponents, 'href' => route('components.index'), 'color' => 'blue'],
            ['label' => 'Users', 'value' => $totalUsers, 'sub' => $activeUsers . ' active', 'href' => route('users.index'), 'color' => 'purple'],
            ['label' => 'Tags', 'value' => $totalTags, 'href' => route('admin.tags.index'), 'color' => 'green'],
            ['label' => 'Component Types', 'value' => $totalTypes, 'href' => route('admin.component-types.index'), 'color' => 'orange'],
        ] as $stat)
            <a href="{{ $stat['href'] }}" class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-sm transition-shadow group">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">{{ $stat['label'] }}</p>
                <p class="text-3xl font-bold text-gray-900">{{ $stat['value'] }}</p>
                @isset($stat['sub'])
                    <p class="text-xs text-gray-400 mt-0.5">{{ $stat['sub'] }}</p>
                @endisset
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Components by type --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">Components by Type</h2>
            @if ($componentsByType->isEmpty())
                <p class="text-sm text-gray-400">No component types defined.</p>
            @else
                <div class="space-y-2">
                    @foreach ($componentsByType as $type)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700">{{ $type->name }}</span>
                            <div class="flex items-center gap-3">
                                <div class="w-32 bg-gray-100 rounded-full h-1.5">
                                    @if ($totalComponents > 0)
                                        <div class="bg-blue-500 h-1.5 rounded-full"
                                             style="width: {{ min(100, round($type->components_count / $totalComponents * 100)) }}%"></div>
                                    @endif
                                </div>
                                <span class="text-xs text-gray-500 w-6 text-right">{{ $type->components_count }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Quick links --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">Management</h2>
            <div class="space-y-2">
                @foreach ([
                    ['label' => 'Manage Users', 'sub' => 'Create, edit, and deactivate users', 'href' => route('users.index'), 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                    ['label' => 'Fact Definitions', 'sub' => 'Define what data fields components can have', 'href' => route('fact-definitions.index'), 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                    ['label' => 'Tags', 'sub' => 'Create and rename tags used on components', 'href' => route('admin.tags.index'), 'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
                    ['label' => 'Component Types', 'sub' => 'Add custom component types beyond the defaults', 'href' => route('admin.component-types.index'), 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                    ['label' => 'Activity Log', 'sub' => 'Review recent changes across all components', 'href' => route('activity.index'), 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ] as $link)
                    <a href="{{ $link['href'] }}"
                       class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                        <div class="w-8 h-8 bg-gray-100 group-hover:bg-blue-50 rounded-lg flex items-center justify-center shrink-0 transition-colors">
                            <svg class="w-4 h-4 text-gray-500 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['icon'] }}"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-800 group-hover:text-blue-700">{{ $link['label'] }}</p>
                            <p class="text-xs text-gray-400">{{ $link['sub'] }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endsection
