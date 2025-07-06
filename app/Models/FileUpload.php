<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_name',
        'stored_name',
        'file_path',
        'file_size',
        'mime_type',
        'upload_status',
        'retry_count',
        'error_message',
        'metadata',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function isPending(): bool
    {
        return $this->upload_status === 'pending';
    }

    public function isUploading(): bool
    {
        return $this->upload_status === 'uploading';
    }

    public function isCompleted(): bool
    {
        return $this->upload_status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->upload_status === 'failed';
    }

    public function canRetry(): bool
    {
        return $this->isFailed() && $this->retry_count < 3;
    }

    public function markAsUploading(): void
    {
        $this->update([
            'upload_status' => 'uploading',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'upload_status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'upload_status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
        ]);
    }
}
