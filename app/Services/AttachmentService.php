<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\LeaveRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentService
{
    /**
     * ACT-03 / FR-REQ-03: store a validated upload on the private disk and
     * record it against the request. Files are never publicly reachable;
     * access goes through the secured download route (PRD 9.2).
     */
    public function store(LeaveRequest $leaveRequest, UploadedFile $file): Attachment
    {
        $path = $file->store("attachments/{$leaveRequest->id}", 'local');

        return $leaveRequest->attachments()->create([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
        ]);
    }

    /**
     * Secured download. The caller must already have passed the attachment
     * policy (employee, assigned supervisor, or HR).
     */
    public function download(Attachment $attachment): StreamedResponse
    {
        $disk = Storage::disk('local');

        if (! $disk->exists($attachment->file_path)) {
            abort(404, 'The stored attachment could not be found.');
        }

        return $disk->download($attachment->file_path, $attachment->file_name);
    }
}
