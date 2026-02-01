<?php

namespace App\Http\Resources\Api\V3;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin \App\Models\NaldaCsvUpload
 */
class NaldaCsvUploadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->csv_type->value,
            'file_name' => $this->getFileName(),
            'file_url' => $this->getFileUrl(),
            'status' => $this->status,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }

    private function getFileUrl(): ?string
    {
        $media = $this->getCsvFile();

        if (! $media) {
            return null;
        }

        $disk = Storage::disk($media->disk);

        if (method_exists($disk->getAdapter(), 'temporaryUrl')) {
            return $media->getTemporaryUrl(now()->addMinutes(30));
        }

        return $media->getUrl();
    }
}
