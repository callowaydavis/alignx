@if ($graphEdges->isEmpty())
    {{-- Empty state --}}
    <div class="bg-white rounded-xl border border-gray-200 flex flex-col items-center justify-center py-20">
        <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
        </svg>
        <p class="text-gray-500 text-sm font-medium">No relationships to diagram</p>
        <p class="text-gray-400 text-xs mt-1">Add relationships to this component to visualise them here.</p>
    </div>
@else
    {{-- Diagram type selector --}}
    <div class="flex items-center gap-4 mb-4">
        <div class="flex gap-1 p-1 bg-gray-100 rounded-lg">
            <button data-diagram-type="network"
                    class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors bg-white shadow-sm text-gray-900">
                Network Map
            </button>
            <button data-diagram-type="dependency"
                    class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors text-gray-500 hover:text-gray-700">
                Dependency View
            </button>
            <button data-diagram-type="landscape"
                    class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors text-gray-500 hover:text-gray-700">
                Landscape
            </button>
        </div>

        {{-- Legend --}}
        <div class="flex items-center gap-3 flex-wrap">
            @foreach ($graphNodes->where('isRoot', false)->unique('type') as $node)
                @php
                    $colors = [
                        'Application' => 'bg-blue-500',
                        'Interface' => 'bg-violet-500',
                        'Data Object' => 'bg-emerald-500',
                        'IT Component' => 'bg-orange-500',
                        'Provider' => 'bg-teal-500',
                        'Process' => 'bg-yellow-600',
                        'Business Capability' => 'bg-red-500',
                    ];
                    $dot = $colors[$node['type']] ?? 'bg-gray-500';
                @endphp
                <span class="flex items-center gap-1.5 text-xs text-gray-500">
                    <span class="w-2.5 h-2.5 rounded-sm {{ $dot }}"></span>
                    {{ $node['type'] }}
                </span>
            @endforeach
        </div>
    </div>

    {{-- Diagram canvas --}}
    <div class="relative bg-white rounded-xl border border-gray-200 overflow-hidden" style="height: 520px;">
        {{-- Cytoscape container --}}
        <div id="cy-canvas" class="w-full h-full"></div>

        {{-- Landscape HTML view (hidden by default) --}}
        <div id="landscape-view" class="hidden w-full h-full overflow-auto p-6">
            @if ($landscapeGroups->isNotEmpty())
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 auto-rows-min">
                    @foreach ($landscapeGroups as $type => $items)
                        @php
                            $panelColors = [
                                'Application' => 'border-blue-200 bg-blue-50',
                                'Interface' => 'border-violet-200 bg-violet-50',
                                'Data Object' => 'border-emerald-200 bg-emerald-50',
                                'IT Component' => 'border-orange-200 bg-orange-50',
                                'Provider' => 'border-teal-200 bg-teal-50',
                                'Process' => 'border-yellow-200 bg-yellow-50',
                                'Business Capability' => 'border-red-200 bg-red-50',
                            ];
                            $panelClass = $panelColors[$type] ?? 'border-gray-200 bg-gray-50';
                        @endphp
                        <div class="rounded-xl border p-4 {{ $panelClass }}">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">{{ $type }}</p>
                                <span class="text-xs text-gray-400">{{ $items->count() }}</span>
                            </div>
                            <div class="space-y-2">
                                @foreach ($items as $item)
                                    <a href="{{ route('components.show', $item['component']) }}"
                                       class="flex items-center justify-between p-2.5 bg-white rounded-lg border border-white/80 hover:border-gray-200 hover:shadow-sm transition-all group">
                                        <span class="text-sm font-medium text-gray-800 group-hover:text-blue-600 truncate">
                                            {{ $item['component']->name }}
                                        </span>
                                        <span class="text-xs text-gray-400 shrink-0 ml-2 flex items-center gap-1">
                                            @if ($item['direction'] === 'outgoing')
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                                </svg>
                                            @else
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"/>
                                                </svg>
                                            @endif
                                            {{ $item['label'] }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Loading overlay --}}
        <div id="diagram-loading"
             class="absolute inset-0 flex items-center justify-center bg-white/90 backdrop-blur-sm">
            <div class="flex flex-col items-center gap-3">
                <svg class="w-6 h-6 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span class="text-sm text-gray-500">Rendering diagram…</span>
            </div>
        </div>

        {{-- Zoom / fit toolbar --}}
        <div id="diagram-toolbar" class="absolute bottom-4 right-4 flex gap-1.5">
            <button data-diagram-fit
                    class="w-8 h-8 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 text-gray-600 flex items-center justify-center transition-colors"
                    title="Fit to view">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                </svg>
            </button>
            <button data-diagram-zoom-in
                    class="w-8 h-8 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 text-gray-600 flex items-center justify-center text-lg font-light transition-colors"
                    title="Zoom in">+</button>
            <button data-diagram-zoom-out
                    class="w-8 h-8 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 text-gray-600 flex items-center justify-center text-lg font-light transition-colors"
                    title="Zoom out">−</button>
        </div>
    </div>

    {{-- Graph data for JS --}}
    <script id="graph-data" type="application/json">{!! json_encode($graphData, JSON_HEX_TAG) !!}</script>
@endif
