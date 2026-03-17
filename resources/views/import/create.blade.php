@extends('layouts.app')

@section('title', 'Import Components')
@section('heading', 'Import Components')

@section('header-actions')
    <a href="{{ route('components.export') }}" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 border border-gray-300 px-3 py-2 rounded-lg transition-colors">
        Download Template
    </a>
@endsection

@section('content')
    <div class="max-w-lg">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <p class="text-sm text-gray-600 mb-4">
                Upload a CSV file to import components. The file should follow the same format as the export template.
                Maximum {{ 200 }} rows per import.
            </p>

            <form method="POST" action="{{ route('components.import.preview') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <label for="file" class="block text-sm font-medium text-gray-700 mb-1.5">CSV File</label>
                    <input
                        type="file"
                        id="file"
                        name="file"
                        accept=".csv,.txt"
                        required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('file') border-red-400 @enderror"
                    >
                    @error('file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                        Preview Import
                    </button>
                    <a href="{{ route('components.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
