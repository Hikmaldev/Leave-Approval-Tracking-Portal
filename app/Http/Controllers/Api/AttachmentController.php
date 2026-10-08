<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Models\LeaveRequest;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function __construct(private readonly AttachmentService $attachments) {}

    /**
     * ACT-03: attach a file to a pending request.
     */
    public function store(StoreAttachmentRequest $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $attachment = $this->attachments->store($leaveRequest, $request->file('attachment'));

        return (new AttachmentResource($attachment))->response()->setStatusCode(201);
    }

    /**
     * Secured download (employee, assigned supervisor, HR only, PRD 9.2).
     */
    public function download(Attachment $attachment): StreamedResponse
    {
        $this->authorize('download', $attachment);

        return $this->attachments->download($attachment);
    }
}
