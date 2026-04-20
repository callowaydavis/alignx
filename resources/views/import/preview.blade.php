@extends('layouts.app')

@section('title', 'Import Preview')
@section('heading', 'Import Preview')

@section('content')
    @if ($exceeded)
        <div class="mb-4 rounded-lg bg-yellow-50 border border-yellow-200 px-4 py-3 text-sm text-yellow-800">
            Warning: Your file exceeds 200 rows. Only the first 200 rows will be imported.
        </div>
    @endif

    @php
        $validCount = collect($rows)->filter(fn($r) => empty($r['errors']))->count();
        $errorCount = collect($rows)->filter(fn($r) => !empty($r['errors']))->count();
        $createCount = collect($rows)->filter(fn($r) => empty($r['errors']) && ($r['action'] ?? 'create') === 'create')->count();
        $updateCount = collect($rows)->filter(fn($r) => empty($r['errors']) && ($r['action'] ?? 'create') === 'update')->count();
    @endphp

    <div class="mb-4 flex items-center gap-4">
        <span class="text-sm text-green-700 font-medium">{{ $validCount }} valid</span>
        @if ($createCount > 0)
            <span class="text-sm text-blue-700 font-medium">{{ $createCount }} new</span>
        @endif
        @if ($updateCount > 0)
            <span class="text-sm text-orange-700 font-medium">{{ $updateCount }} updates</span>
        @endif
        @if ($errorCount > 0)
            <span class="text-sm text-red-700 font-medium">{{ $errorCount }} with errors (will be skipped)</span>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Action</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Type</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Lifecycle</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Owner</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Tags</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Facts</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Errors</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($rows as $row)
                        <tr class="{{ empty($row['errors']) ? 'bg-green-50' : 'bg-red-50' }}">
                            <td class="px-4 py-3">
                                @if (($row['action'] ?? 'create') === 'create')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Create</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-700">Update</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if (empty($row['errors']))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Valid</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">Error</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-medium">{{ $row['data']['name'] }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $row['data']['type'] }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $row['data']['lifecycle_stage'] }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $row['data']['owner'] }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $row['data']['tags'] }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                @php $factCount = count($row['data']['_facts'] ?? []); @endphp
                                @if ($factCount > 0)
                                    <span class="text-xs font-medium text-blue-700">{{ $factCount }} {{ Illuminate\Support\Str::plural('fact', $factCount) }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-red-600">
                                @foreach ($row['errors'] as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if ($validCount > 0)
        <div class="flex items-center gap-4">
            <form method="POST" action="{{ route('components.import.store') }}">
                @csrf
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                    Confirm Import
                    @if ($createCount > 0 || $updateCount > 0)
                        ({{ $createCount }} new{{ $updateCount > 0 ? ', '.$updateCount.' updates' : '' }})
                    @else
                        ({{ $validCount }} components)
                    @endif
                </button>
            </form>
            <a href="{{ route('components.import.create') }}" class="text-sm text-gray-600 hover:text-gray-900">Start Over</a>
        </div>
    @else
        <a href="{{ route('components.import.create') }}" class="text-sm text-gray-600 hover:text-gray-900">← Back to upload</a>
    @endif
@endsection
