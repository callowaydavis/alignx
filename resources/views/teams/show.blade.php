@extends('layouts.app')

@section('title', $team->name)
@section('heading', $team->name)

@section('header-actions')
    @can('update', $team)
        <a href="{{ route('teams.edit', $team) }}" class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            Edit Team
        </a>
    @endcan
    @can('delete', $team)
        <form method="POST" action="{{ route('teams.destroy', $team) }}" onsubmit="return confirm('Delete this team? Components owned by this team will become unassigned.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="inline-flex items-center gap-2 bg-white border border-red-200 hover:bg-red-50 text-red-600 text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                Delete
            </button>
        </form>
    @endcan
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-6 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Team details --}}
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700">Team Details</h2>
                @if ($team->description)
                    <p class="text-sm text-gray-600">{{ $team->description }}</p>
                @endif
                <div class="space-y-2">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">AD Group</p>
                        <p class="text-sm text-gray-800 mt-0.5">{{ $team->ad_group ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Members</p>
                        <p class="text-sm text-gray-800 mt-0.5">{{ $team->users->count() }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Members --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700">Members</h2>
                </div>

                @can('update', $team)
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <form method="POST" action="{{ route('teams.members.add', $team) }}" class="flex items-center gap-3">
                            @csrf
                            <select name="user_id" required
                                    class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select a user to add...</option>
                                @foreach ($availableUsers as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors whitespace-nowrap">
                                Add Member
                            </button>
                        </form>
                    </div>
                @endcan

                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($team->users as $user)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-medium text-gray-900">{{ $user->name }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ $user->email }}</td>
                                <td class="px-6 py-3">
                                    @php
                                        $roleColors = ['admin' => 'bg-purple-100 text-purple-700', 'editor' => 'bg-blue-100 text-blue-700', 'viewer' => 'bg-gray-100 text-gray-700'];
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $roleColors[$user->role->value] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ ucfirst($user->role->value) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    @can('update', $team)
                                        <form method="POST" action="{{ route('teams.members.remove', [$team, $user]) }}" onsubmit="return confirm('Remove {{ $user->name }} from this team?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Remove</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-gray-400">No members yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
