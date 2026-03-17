@extends('layouts.app')

@section('title', 'Activity')
@section('heading', 'Activity Log')

@section('content')
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-6 py-3 text-left font-medium text-gray-500">Component</th>
                    <th class="px-6 py-3 text-left font-medium text-gray-500">Event</th>
                    <th class="px-6 py-3 text-left font-medium text-gray-500">User</th>
                    <th class="px-6 py-3 text-left font-medium text-gray-500">Changes</th>
                    <th class="px-6 py-3 text-left font-medium text-gray-500">Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($audits as $audit)
                    @php
                        $model = $audit->auditable;
                        $eventColors = [
                            'created' => 'bg-green-100 text-green-700',
                            'updated' => 'bg-blue-100 text-blue-700',
                            'deleted' => 'bg-red-100 text-red-700',
                        ];
                        $color = $eventColors[$audit->event] ?? 'bg-gray-100 text-gray-700';
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            @if ($model)
                                <a href="{{ route('components.show', $model) }}" class="font-medium text-blue-600 hover:text-blue-800">{{ $model->name }}</a>
                            @else
                                <span class="text-gray-400 italic">Deleted</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $color }}">
                                {{ ucfirst($audit->event) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $audit->user?->name ?? 'System' }}</td>
                        <td class="px-6 py-4 text-gray-500 text-xs">
                            @if ($audit->event === 'updated' && $audit->new_values)
                                @foreach (array_keys($audit->new_values) as $field)
                                    <span class="block">{{ $field }}</span>
                                @endforeach
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-500">{{ $audit->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-400">No activity recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($audits->hasPages())
        <div class="mt-4">{{ $audits->links() }}</div>
    @endif
@endsection
