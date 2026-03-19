@php
    $statusColors = [
        'Pending'     => 'bg-yellow-100 text-yellow-700',
        'In Progress' => 'bg-blue-100 text-blue-700',
        'Completed'   => 'bg-green-100 text-green-700',
    ];
    $categoryColors = [
        'Security'      => 'bg-red-100 text-red-700',
        'Operational'   => 'bg-orange-100 text-orange-700',
        'Documentation' => 'bg-purple-100 text-purple-700',
        'Compliance'    => 'bg-teal-100 text-teal-700',
    ];
@endphp

<div class="space-y-6">
    {{-- To Dos table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">To Dos</h2>
        </div>

        @if ($component->todos->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Condition</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Category</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Status</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Accepted By</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Due Date</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Completed By</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($component->todos as $todo)
                            @php
                                $statusColor = $statusColors[$todo->status->value] ?? 'bg-gray-100 text-gray-700';
                                $categoryColor = $categoryColors[$todo->category->value] ?? 'bg-gray-100 text-gray-700';
                            @endphp
                            <tr class="hover:bg-gray-50 group">
                                <td class="px-5 py-4 text-gray-800 max-w-xs">
                                    <p class="whitespace-pre-wrap">{{ $todo->condition }}</p>
                                    @if ($todo->acceptance_notes)
                                        <p class="text-xs text-gray-400 mt-1 italic">{{ $todo->acceptance_notes }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $categoryColor }}">
                                        {{ $todo->category->value }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusColor }}">
                                        {{ $todo->status->value }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-gray-600">
                                    {{ $todo->acceptedByUser?->name ?? '—' }}
                                </td>
                                <td class="px-5 py-4 text-gray-600">
                                    {{ $todo->due_date?->format('M j, Y') ?? '—' }}
                                </td>
                                <td class="px-5 py-4 text-gray-500 text-xs">
                                    @if ($todo->completedByUser)
                                        <span class="block">{{ $todo->completedByUser->name }}</span>
                                        <span class="text-gray-400">{{ $todo->completed_at->format('M j, Y') }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button type="button"
                                                onclick="openEditTodo({{ $todo->id }}, {{ json_encode($todo->condition) }}, {{ json_encode($todo->category->value) }}, {{ json_encode($todo->status->value) }}, {{ $todo->accepted_by ?? 'null' }}, {{ json_encode($todo->acceptance_notes) }}, {{ json_encode($todo->due_date?->format('Y-m-d')) }})"
                                                class="text-gray-400 hover:text-blue-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <form method="POST"
                                              action="{{ route('components.todos.destroy', [$component, $todo]) }}"
                                              onsubmit="return confirm('Delete this to do?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-5 py-8 text-center">
                <p class="text-sm text-gray-400">No to dos yet.</p>
            </div>
        @endif
    </div>

    {{-- Add To Do form --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Add To Do</h3>
        </div>
        <form method="POST" action="{{ route('components.todos.store', $component) }}"
              class="px-5 py-4 space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Condition <span class="text-red-500">*</span></label>
                    <textarea name="condition" rows="3" required
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Describe the condition or action required..."></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Category <span class="text-red-500">*</span></label>
                    <select name="category" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach ($todoCategories as $cat)
                            <option value="{{ $cat->value }}">{{ $cat->value }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Status <span class="text-red-500">*</span></label>
                    <select name="status" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach ($todoStatuses as $status)
                            <option value="{{ $status->value }}">{{ $status->value }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Accepted By</label>
                    <select name="accepted_by"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">None</option>
                        @foreach ($activeUsers as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Due Date</label>
                    <input type="date" name="due_date"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Acceptance Notes</label>
                    <textarea name="acceptance_notes" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Optional notes about acceptance criteria..."></textarea>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                    Add To Do
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Edit To Do Modal --}}
<div id="edit-todo-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50" onclick="closeEditTodo(event)">
    <div class="bg-white rounded-xl border border-gray-200 w-full max-w-lg mx-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Edit To Do</h3>
            <button type="button" onclick="closeEditTodoModal()"
                    class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="edit-todo-form" method="POST" class="px-5 py-4 space-y-4">
            @csrf @method('PATCH')
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Condition <span class="text-red-500">*</span></label>
                    <textarea id="edit-condition" name="condition" rows="3" required
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Category <span class="text-red-500">*</span></label>
                    <select id="edit-category" name="category" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach ($todoCategories as $cat)
                            <option value="{{ $cat->value }}">{{ $cat->value }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Status <span class="text-red-500">*</span></label>
                    <select id="edit-status" name="status" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach ($todoStatuses as $status)
                            <option value="{{ $status->value }}">{{ $status->value }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Accepted By</label>
                    <select id="edit-accepted-by" name="accepted_by"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">None</option>
                        @foreach ($activeUsers as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Due Date</label>
                    <input id="edit-due-date" type="date" name="due_date"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Acceptance Notes</label>
                    <textarea id="edit-acceptance-notes" name="acceptance_notes" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeEditTodoModal()"
                        class="border border-gray-300 hover:border-gray-400 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const todoBaseUrl = '{{ route('components.show', $component) }}';

    function openEditTodo(id, condition, category, status, acceptedBy, acceptanceNotes, dueDate) {
        const form = document.getElementById('edit-todo-form');
        form.action = `/components/{{ $component->id }}/todos/${id}`;

        document.getElementById('edit-condition').value = condition || '';
        document.getElementById('edit-acceptance-notes').value = acceptanceNotes || '';
        document.getElementById('edit-due-date').value = dueDate || '';

        const catSelect = document.getElementById('edit-category');
        for (let opt of catSelect.options) { opt.selected = opt.value === category; }

        const statusSelect = document.getElementById('edit-status');
        for (let opt of statusSelect.options) { opt.selected = opt.value === status; }

        const acceptedBySelect = document.getElementById('edit-accepted-by');
        for (let opt of acceptedBySelect.options) {
            opt.selected = acceptedBy !== null && parseInt(opt.value) === acceptedBy;
        }

        const modal = document.getElementById('edit-todo-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditTodoModal() {
        const modal = document.getElementById('edit-todo-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function closeEditTodo(event) {
        if (event.target === document.getElementById('edit-todo-modal')) {
            closeEditTodoModal();
        }
    }
</script>
