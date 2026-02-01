<?php

namespace App\Models;

use App\Enums\NaldaCsvType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class NaldaCsvUpload extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\NaldaCsvUploadFactory> */
    use HasFactory;

    use InteractsWithMedia;

    protected $fillable = [
        'license_id',
        'domain',
        'csv_type',
        'sftp_host',
        'sftp_port',
        'sftp_username',
        'sftp_path',
        'status',
        'error_message',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'csv_type' => NaldaCsvType::class,
            'sftp_port' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('csv')
            ->singleFile()
            ->useDisk('nalda-csv')
            ->acceptsMimeTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel']);
    }

    public function getCsvFile(): ?Media
    {
        return $this->getFirstMedia('csv');
    }

    public function getFileName(): ?string
    {
        return $this->getCsvFile()?->file_name;
    }

    public function getFileSize(): ?int
    {
        return $this->getCsvFile()?->size;
    }

    public function markAsUploaded(string $sftpPath): void
    {
        $this->update([
            'status' => 'completed',
            'sftp_path' => $sftpPath,
            'uploaded_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }
}
