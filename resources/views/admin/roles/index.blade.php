@extends('layouts.app')

@section('title', 'Roles')
@section('heading', 'Roles')

@section('header-actions')
    @can('create', \App\Models\Role::class)
        <a href="{{ route('admin.roles.create') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Role
        </a>
    @endcan
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-6 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-6 py-3 text-left font-medium text-gray-500">Name</th>
                    <th class="px-6 py-3 text-left font-medium text-gray-500">Assignee Type</th>
                    <th class="px-6 py-3 text-left font-medium text-gray-500">Allow Multiple</th>
                    <th class="px-6 py-3 text-left font-medium text-gray-500">Required</th>
                    <th class="px-6 py-3 text-left font-medium text-gray-500">Component Types</th>
                    <th class="px-6 py-3 text-right font-medium text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($roles as $role)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $role->name }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $role->assignee_type->label() }}</td>
                        <td class="px-6 py-4">
                            @if ($role->allow_multiple)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Yes</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">No</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if ($role->is_required)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">Yes</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">No</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if ($role->componentTypes->isEmpty())
                                <span class="text-xs text-gray-400">All types</span>
                            @else
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($role->componentTypes as $type)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">{{ $type->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-3">
                                @can('update', $role)
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="text-blue-600 hover:text-blue-800 font-medium">Edit</a>
                                @endcan
                                @can('delete', $role)
                                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('Delete this role? All assignments will be removed.')">
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
                        <td colspan="6" class="px-6 py-12 text-center text-gray-400">No roles defined yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($roles->hasPages())
        <div class="mt-4">{{ $roles->links() }}</div>
    @endif
@endsection
