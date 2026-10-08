<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Services\AttachmentService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function __construct(private readonly AttachmentService $attachments) {}

    /**
     * Secured download (PRD 9.2): only the employee, their assigned
     * supervisor, and HR may fetch the file.
     */
    public function download(Attachment $attachment): StreamedResponse
    {
        $this->authorize('download', $attachment);

        return $this->attachments->download($attachment);
    }
}
