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
                    <th class="px-6 py-3 text-left font-medium text-gray-500">Details</th>
                    <th class="px-6 py-3 text-left font-medium text-gray-500">Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($audits as $audit)
                    @php
                        $model = $audit->auditable;
                        $eventMeta = [
                            'created'              => ['label' => 'Created',              'color' => 'bg-green-100 text-green-700'],
                            'updated'              => ['label' => 'Updated',              'color' => 'bg-blue-100 text-blue-700'],
                            'deleted'              => ['label' => 'Deleted',              'color' => 'bg-red-100 text-red-700'],
                            'relationship_added'   => ['label' => 'Relationship Added',   'color' => 'bg-purple-100 text-purple-700'],
                            'relationship_removed' => ['label' => 'Relationship Removed', 'color' => 'bg-purple-100 text-purple-700'],
                            'fact_added'           => ['label' => 'Fact Added',           'color' => 'bg-teal-100 text-teal-700'],
                            'fact_updated'         => ['label' => 'Fact Updated',         'color' => 'bg-teal-100 text-teal-700'],
                            'fact_removed'         => ['label' => 'Fact Removed',         'color' => 'bg-teal-100 text-teal-700'],
                            'role_assigned'        => ['label' => 'Role Assigned',        'color' => 'bg-indigo-100 text-indigo-700'],
                            'role_unassigned'      => ['label' => 'Role Unassigned',      'color' => 'bg-indigo-100 text-indigo-700'],
                            'document_uploaded'    => ['label' => 'Document Uploaded',    'color' => 'bg-orange-100 text-orange-700'],
                            'document_viewed'      => ['label' => 'Document Viewed',      'color' => 'bg-gray-100 text-gray-700'],
                            'document_removed'     => ['label' => 'Document Removed',     'color' => 'bg-red-100 text-red-700'],
                        ];
                        $meta = $eventMeta[$audit->event] ?? ['label' => ucfirst($audit->event), 'color' => 'bg-gray-100 text-gray-700'];
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            @if ($model)
                                <a href="{{ route('components.show', $model) }}"
                                   class="font-medium text-blue-600 hover:text-blue-800">{{ $model->name }}</a>
                                @if (! $model->is_active)
                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700">Inactive</span>
                                @endif
                            @else
                                <span class="text-gray-400 italic">Deleted</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $meta['color'] }}">
                                {{ $meta['label'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $audit->user?->name ?? 'System' }}</td>
                        <td class="px-6 py-4 text-gray-500 text-xs space-y-0.5">
                            @if (in_array($audit->event, ['role_assigned', 'role_unassigned']))
                                @php $vals = $audit->new_values ?: $audit->old_values; @endphp
                                @if ($vals)
                                    <span class="block">
                                        <span class="font-medium text-gray-700">{{ $vals['role'] ?? '—' }}</span>
                                        <span class="text-gray-400 mx-1">→</span>
                                        {{ $vals['assignee'] ?? '—' }}
                                    </span>
                                @endif
                            @elseif (in_array($audit->event, ['relationship_added', 'relationship_removed']))
                                @php $vals = $audit->new_values ?: $audit->old_values; @endphp
                                @if ($vals)
                                    <span class="block">
                                        <span class="font-medium text-gray-700">{{ $vals['target'] ?? '—' }}</span>
                                        <span class="text-gray-400 mx-1">·</span>
                                        {{ $vals['type'] ?? '—' }}
                                    </span>
                                @endif
                            @elseif ($audit->event === 'updated' && $audit->new_values)
                                @foreach ($audit->new_values as $field => $newVal)
                                    <span class="block">
                                        <span class="font-medium text-gray-700">{{ \Illuminate\Support\Str::headline($field) }}:</span>
                                        @if (isset($audit->old_values[$field]))
                                            <span class="line-through text-gray-400">{{ $audit->old_values[$field] ?? '—' }}</span>
                                            <span class="text-gray-400 mx-0.5">→</span>
                                        @endif
                                        {{ $newVal ?? '—' }}
                                    </span>
                                @endforeach
                            @elseif (in_array($audit->event, ['fact_added', 'fact_updated', 'fact_removed']))
                                @php
                                    $old = $audit->old_values;
                                    $new = $audit->new_values;
                                    $fields = array_unique(array_merge(array_keys($old ?? []), array_keys($new ?? [])));
                                @endphp
                                @foreach ($fields as $field)
                                    <span class="block">
                                        <span class="font-medium text-gray-700">{{ $field }}:</span>
                                        @if (isset($old[$field]))
                                            <span class="line-through text-gray-400">{{ $old[$field] ?? '—' }}</span>
                                            @if (isset($new[$field]))<span class="text-gray-400 mx-0.5">→</span>@endif
                                        @endif
                                        @if (isset($new[$field])){{ $new[$field] ?? '—' }}@endif
                                    </span>
                                @endforeach
                            @elseif ($audit->event === 'created' && $audit->new_values)
                                @foreach ($audit->new_values as $field => $val)
                                    @if ($val !== null && $val !== '')
                                        <span class="block">
                                            <span class="font-medium text-gray-700">{{ \Illuminate\Support\Str::headline($field) }}:</span>
                                            {{ $val }}
                                        </span>
                                    @endif
                                @endforeach
                            @elseif ($audit->event === 'deleted' && $audit->old_values)
                                @foreach ($audit->old_values as $field => $val)
                                    @if ($val !== null && $val !== '')
                                        <span class="block">
                                            <span class="font-medium text-gray-700">{{ \Illuminate\Support\Str::headline($field) }}:</span>
                                            {{ $val }}
                                        </span>
                                    @endif
                                @endforeach
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-500 whitespace-nowrap">{{ $audit->created_at->diffForHumans() }}</td>
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
