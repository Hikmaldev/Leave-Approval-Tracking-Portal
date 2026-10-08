<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->file_name,
            // The stored file_path is intentionally not exposed. Attachments
            // are restricted to the employee, their supervisor, and HR
            // (PRD 9.2), so the UI uses this secured download route instead.
            'download_url' => route('api.attachments.download', ['attachment' => $this->id]),
            'uploaded_at' => $this->uploaded_at?->toIso8601String(),
        ];
    }
}
