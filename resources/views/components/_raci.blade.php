@php
    $raciMatrix = $raciMatrix;
    $componentId = $component->id;
@endphp

<div class="bg-white rounded-xl border border-gray-200 p-5" id="raci-container">
    @if (!$raciMatrix)
        <div class="text-center py-8">
            <p class="text-gray-500 mb-4">No RACI matrix yet</p>
            <button type="button" id="initialize-raci"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Create RACI Matrix
            </button>
        </div>
    @else
        <div class="space-y-4">
            {{-- Column management --}}
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-semibold text-gray-800">Responsibilities</h3>
                <button type="button" id="add-column-btn"
                        class="inline-flex items-center gap-1 text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Column
                </button>
            </div>

            {{-- RACI Grid --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr>
                            <th class="border border-gray-200 px-4 py-3 text-left font-medium bg-gray-50 min-w-[200px] sticky left-0 z-10">
                                Responsibility
                            </th>
                            @foreach ($raciMatrix->columns as $column)
                                <th class="border border-gray-200 px-3 py-3 text-center font-medium bg-gray-50 min-w-[150px] group relative">
                                    <div class="flex items-center justify-center gap-2">
                                        <span class="editable-column-name" data-column-id="{{ $column->id }}" contenteditable="true" class="outline-none">{{ $column->name }}</span>
                                        <button type="button" class="delete-column-btn opacity-0 group-hover:opacity-100 transition-opacity" data-column-id="{{ $column->id }}">
                                            <svg class="w-3.5 h-3.5 text-red-500 hover:text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </th>
                            @endforeach
                            <th class="border border-gray-200 px-4 py-3 text-left font-medium bg-gray-50 min-w-[200px]">Notes</th>
                            <th class="border border-gray-200 px-4 py-3 text-center font-medium bg-gray-50 w-12"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($raciMatrix->rows as $row)
                            <tr class="hover:bg-gray-50 transition-colors" data-row-id="{{ $row->id }}">
                                <td class="border border-gray-200 px-4 py-3 font-medium text-gray-900 sticky left-0 bg-white z-10">
                                    {{ $row->responsibility }}
                                </td>
                                @foreach ($raciMatrix->columns as $column)
                                    @php
                                        $assignment = $row->assignments()->where('raci_column_id', $column->id)->first();
                                        $assignedName = $assignment?->getAssignedName();
                                    @endphp
                                    <td class="border border-gray-200 px-3 py-2 text-center">
                                        <select class="raci-assignment-select w-full text-xs border border-gray-300 rounded px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                data-row-id="{{ $row->id }}"
                                                data-column-id="{{ $column->id }}">
                                            <option value="">—</option>
                                            <optgroup label="Users">
                                                @foreach ($activeUsers as $user)
                                                    <option value="user:{{ $user->id }}" @selected($assignment?->assigned_to_type === 'user' && $assignment?->assigned_to_id === $user->id)>
                                                        {{ $user->name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                            <optgroup label="Teams">
                                                @foreach ($allTeams as $team)
                                                    <option value="team:{{ $team->id }}" @selected($assignment?->assigned_to_type === 'team' && $assignment?->assigned_to_id === $team->id)>
                                                        {{ $team->name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        </select>
                                    </td>
                                @endforeach
                                <td class="border border-gray-200 px-4 py-2">
                                    <textarea class="row-notes w-full text-xs border border-gray-300 rounded px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                              data-row-id="{{ $row->id }}"
                                              rows="2"
                                              placeholder="Add notes...">{{ $row->notes }}</textarea>
                                </td>
                                <td class="border border-gray-200 px-4 py-3 text-center">
                                    <button type="button" class="delete-row-btn text-red-500 hover:text-red-700 transition-colors" data-row-id="{{ $row->id }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $raciMatrix->columns->count() + 3 }}" class="border border-gray-200 px-4 py-6 text-center text-gray-400">
                                    No responsibilities defined yet
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Add responsibility form --}}
            <div class="mt-6 pt-6 border-t border-gray-200">
                <form id="add-responsibility-form" class="space-y-3">
                    <input type="text" id="responsibility-input" placeholder="New responsibility..." required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <div class="flex gap-3">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                            Add Responsibility
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

<script>
    const componentId = {{ $componentId }};

    // Initialize RACI matrix
    document.getElementById('initialize-raci')?.addEventListener('click', async function () {
        const response = await fetch(`/components/${componentId}/raci/initialize`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        if (response.ok) {
            location.reload();
        }
    });

    // Add responsibility
    document.getElementById('add-responsibility-form')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const input = document.getElementById('responsibility-input');
        const response = await fetch(`/components/${componentId}/raci/rows`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                responsibility: input.value
            })
        });
        if (response.ok) {
            const data = await response.json();
            const newRow = data.row;

            // Get columns from the table header
            const columnHeaders = document.querySelectorAll('table th');
            const numColumns = Array.from(columnHeaders).filter(h => h.textContent.includes('Responsible') || h.textContent.includes('Accountable') || h.textContent.includes('Consulted') || h.textContent.includes('Informed')).length;

            // Build the new row HTML
            let rowHtml = `
                <tr class="hover:bg-gray-50 transition-colors" data-row-id="${newRow.id}">
                    <td class="border border-gray-200 px-4 py-3 font-medium text-gray-900 sticky left-0 bg-white z-10">
                        ${newRow.responsibility}
                    </td>
            `;

            // Add assignment cells for each column
            const columns = document.querySelectorAll('table th:not(:first-child):not(:nth-last-child(2)):not(:last-child)');
            columns.forEach(th => {
                const columnId = th.querySelector('span').dataset.columnId;
                rowHtml += `
                    <td class="border border-gray-200 px-3 py-2 text-center">
                        <select class="raci-assignment-select w-full text-xs border border-gray-300 rounded px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                data-row-id="${newRow.id}"
                                data-column-id="${columnId}">
                            <option value="">—</option>
                            <optgroup label="Users">
                                @foreach ($activeUsers as $user)
                                    <option value="user:{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Teams">
                                @foreach ($allTeams as $team)
                                    <option value="team:{{ $team->id }}">{{ $team->name }}</option>
                                @endforeach
                            </optgroup>
                        </select>
                    </td>
                `;
            });

            rowHtml += `
                    <td class="border border-gray-200 px-4 py-2">
                        <textarea class="row-notes w-full text-xs border border-gray-300 rounded px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  data-row-id="${newRow.id}"
                                  rows="2"
                                  placeholder="Add notes..."></textarea>
                    </td>
                    <td class="border border-gray-200 px-4 py-3 text-center">
                        <button type="button" class="delete-row-btn text-red-500 hover:text-red-700 transition-colors" data-row-id="${newRow.id}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </td>
                </tr>
            `;

            // Add to table
            const tableBody = document.querySelector('table tbody');
            // Remove the empty state row if it exists
            const emptyRow = tableBody.querySelector('tr td[colspan]');
            if (emptyRow) {
                emptyRow.closest('tr').remove();
            }
            tableBody.insertAdjacentHTML('beforeend', rowHtml);

            // Re-attach event listeners to new row's assignment selects
            const newSelects = tableBody.querySelectorAll(`.raci-assignment-select[data-row-id="${newRow.id}"]`);
            newSelects.forEach(select => {
                select.addEventListener('change', assignmentChangeHandler);
            });

            // Re-attach delete button listener
            const deleteBtn = tableBody.querySelector(`.delete-row-btn[data-row-id="${newRow.id}"]`);
            deleteBtn.addEventListener('click', deleteRowHandler);

            // Re-attach notes listener
            const notesTextarea = tableBody.querySelector(`.row-notes[data-row-id="${newRow.id}"]`);
            let timeout;
            notesTextarea.addEventListener('input', function () {
                clearTimeout(timeout);
                const rowId = this.dataset.rowId;
                const notes = this.value;

                timeout = setTimeout(async () => {
                    await fetch(`/raci-rows/${rowId}/notes`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ notes })
                    });
                }, 500);
            });

            // Clear input
            input.value = '';
        }
    });

    function assignmentChangeHandler() {
        const rowId = this.dataset.rowId;
        const columnId = this.dataset.columnId;
        const value = this.value;
        const [type, id] = value ? value.split(':') : [null, null];

        fetch(`/components/${componentId}/raci/assignments`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                row_id: rowId,
                column_id: columnId,
                assigned_to_type: type,
                assigned_to_id: id ? parseInt(id) : null
            })
        }).catch(error => {
            console.error('Error updating assignment:', error);
        });
    }

    // Attach assignment change handlers to existing selects
    document.querySelectorAll('.raci-assignment-select').forEach(select => {
        select.addEventListener('change', assignmentChangeHandler);
    });

    function deleteRowHandler() {
        if (confirm('Delete this responsibility?')) {
            const rowId = this.dataset.rowId;
            fetch(`/raci-rows/${rowId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            }).then(() => {
                document.querySelector(`tr[data-row-id="${rowId}"]`).remove();
            });
        }
    }

    // Update column name
    document.querySelectorAll('.editable-column-name').forEach(span => {
        let originalText = span.textContent;
        span.addEventListener('blur', async function () {
            const columnId = this.dataset.columnId;
            const newName = this.textContent;

            if (newName !== originalText) {
                const response = await fetch(`/raci-columns/${columnId}/name`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ name: newName })
                });
                if (response.ok) {
                    originalText = newName;
                }
            }
        });
    });

    // Delete column
    document.querySelectorAll('.delete-column-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            if (confirm('Delete this column?')) {
                const columnId = this.dataset.columnId;
                await fetch(`/raci-columns/${columnId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                location.reload();
            }
        });
    });

    // Add column
    document.getElementById('add-column-btn')?.addEventListener('click', function () {
        const columnName = prompt('Column name:');
        if (columnName) {
            fetch(`/components/${componentId}/raci/columns`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ name: columnName })
            }).then(response => {
                if (response.ok) {
                    location.reload();
                }
            });
        }
    });

    // Attach delete row handlers to existing buttons
    document.querySelectorAll('.delete-row-btn').forEach(btn => {
        btn.addEventListener('click', deleteRowHandler);
    });

    // Attach notes handlers to existing textareas
    document.querySelectorAll('.row-notes').forEach(textarea => {
        let timeout;
        textarea.addEventListener('input', function () {
            clearTimeout(timeout);
            const rowId = this.dataset.rowId;
            const notes = this.value;

            timeout = setTimeout(async () => {
                await fetch(`/raci-rows/${rowId}/notes`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ notes })
                });
            }, 500);
        });
    });
</script>

<style>
    #raci-container table {
        background: white;
    }

    #raci-container th {
        position: relative;
    }

    #raci-container td {
        background: white;
    }

    #raci-container tbody tr:hover td {
        background: #f9fafb;
    }

    .editable-column-name {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        cursor: text;
    }

    .editable-column-name:focus {
        background: #e3f2fd;
        box-shadow: 0 0 0 2px #3b82f6;
    }
</style>
