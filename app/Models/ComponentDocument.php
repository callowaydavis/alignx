<?php

namespace App\Models;

use Database\Factories\ComponentDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComponentDocument extends Model
{
    /** @use HasFactory<ComponentDocumentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'component_id',
        'original_filename',
        'name',
        'tag',
        'stored_path',
        'disk',
        'mime_type',
        'file_size',
        'uploaded_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
