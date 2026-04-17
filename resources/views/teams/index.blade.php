@extends('layouts.app')

@section('title', 'Teams')
@section('heading', 'Teams')

@section('header-actions')
    @can('create', \App\Models\Team::class)
        <a href="{{ route('teams.create') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Team
        </a>
    @endcan
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-6 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-6 py-3 text-left font-medium text-gray-500">Name</th>
                    <th class="px-6 py-3 text-left font-medium text-gray-500">AD Group</th>
                    <th class="px-6 py-3 text-left font-medium text-gray-500">Members</th>
                    <th class="px-6 py-3 text-right font-medium text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($teams as $team)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <a href="{{ route('teams.show', $team) }}" class="font-medium text-gray-900 hover:text-blue-600">{{ $team->name }}</a>
                            @if ($team->description)
                                <p class="text-xs text-gray-400 mt-0.5">{{ Str::limit($team->description, 60) }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            {{ $team->ad_group ?? '—' }}
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $team->users_count }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('teams.show', $team) }}" class="text-blue-600 hover:text-blue-800 font-medium">View</a>
                                @can('update', $team)
                                    <a href="{{ route('teams.edit', $team) }}" class="text-gray-600 hover:text-gray-800 font-medium">Edit</a>
                                @endcan
                                @can('delete', $team)
                                    <form method="POST" action="{{ route('teams.destroy', $team) }}" onsubmit="return confirm('Delete this team?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Delete</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-400">No teams found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($teams->hasPages())
        <div class="mt-4">{{ $teams->links() }}</div>
    @endif
@endsection
