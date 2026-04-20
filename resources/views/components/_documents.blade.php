@php
    $documents = $component->documents;
@endphp

<div class="space-y-6">
    {{-- Upload Form --}}
    @can('update', $component)
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="font-semibold text-gray-800 mb-4">Upload Document</h3>
            <form method="POST" action="{{ route('components.documents.store', $component) }}" enctype="multipart/form-data"
                  class="space-y-4">
                @csrf

                <div>
                    <label for="document" class="block text-sm font-medium text-gray-700 mb-2">
                        Select Document
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="file" id="document" name="document" required
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.png,.jpg,.jpeg,.gif,.zip"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('document') border-red-300 @enderror">
                    <p class="mt-1 text-xs text-gray-500">Max 20MB. Supported: PDF, Word, Excel, PowerPoint, images, text, CSV, ZIP</p>
                    @error('document')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                        <input type="text" id="name" name="name" placeholder="e.g. Q1 Architecture Review"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-300 @enderror"
                               value="{{ old('name') }}">
                        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="tag" class="block text-sm font-medium text-gray-700 mb-2">Tag</label>
                        <input type="text" id="tag" name="tag" placeholder="e.g. Design, Review, Draft"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tag') border-red-300 @enderror"
                               value="{{ old('tag') }}">
                        @error('tag')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                        Upload
                    </button>
                </div>
            </form>
        </div>
    @endcan

    {{-- Documents List --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if ($documents->isNotEmpty())
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-5 py-3 text-left font-medium text-gray-500">Name</th>
                        <th class="px-5 py-3 text-left font-medium text-gray-500">Filename</th>
                        <th class="px-5 py-3 text-left font-medium text-gray-500">Tag</th>
                        <th class="px-5 py-3 text-left font-medium text-gray-500">Size</th>
                        <th class="px-5 py-3 text-left font-medium text-gray-500">Uploaded By</th>
                        <th class="px-5 py-3 text-left font-medium text-gray-500">Uploaded</th>
                        <th class="px-5 py-3 text-left font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($documents as $document)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3">
                                @if ($document->name)
                                    <span class="text-sm font-medium text-gray-800">{{ $document->name }}</span>
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <a href="{{ route('components.documents.show', [$component, $document]) }}"
                                   class="text-sm text-blue-600 hover:underline truncate block max-w-xs"
                                   title="{{ $document->original_filename }}">
                                    {{ $document->original_filename }}
                                </a>
                            </td>
                            <td class="px-5 py-3">
                                @if ($document->tag)
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-700">
                                        {{ $document->tag }}
                                    </span>
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-600">
                                {{ number_format($document->file_size / 1024, 1) }} KB
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-600">
                                @if ($document->uploadedBy)
                                    {{ $document->uploadedBy->name }}
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-500 whitespace-nowrap">
                                {{ $document->created_at->format('M j, Y') }}
                            </td>
                            <td class="px-5 py-3 text-sm">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('components.documents.show', [$component, $document]) }}"
                                       class="text-blue-600 hover:text-blue-800 transition-colors" title="Download">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                    </a>

                                    @can('update', $component)
                                        <form method="POST"
                                              action="{{ route('components.documents.destroy', [$component, $document]) }}"
                                              onsubmit="return confirm('Remove this document?')"
                                              class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors"
                                                    title="Remove">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="px-5 py-8 text-center text-sm text-gray-400">
                No documents yet.
                @can('update', $component)
                    <span class="text-gray-400">Upload a document above.</span>
                @endcan
            </div>
        @endif
    </div>
</div>
