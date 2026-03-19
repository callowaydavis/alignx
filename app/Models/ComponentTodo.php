<?php

namespace App\Models;

use App\Enums\TodoCategory;
use App\Enums\TodoStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComponentTodo extends Model
{
    use HasFactory;

    protected $fillable = [
        'component_id',
        'condition',
        'category',
        'status',
        'accepted_by',
        'acceptance_notes',
        'due_date',
        'completed_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => TodoCategory::class,
            'status' => TodoStatus::class,
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function acceptedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
