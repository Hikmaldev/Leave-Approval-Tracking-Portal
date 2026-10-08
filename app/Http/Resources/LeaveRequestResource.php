<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // Raw status is for logic; status_label is the plain-language text
            // the UI must display (PRD 9.4, design system 5.1).
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'days_requested' => $this->days_requested,
            'reason' => $this->reason,
            'user' => new UserResource($this->whenLoaded('user')),
            'leave_type' => new LeaveTypeResource($this->whenLoaded('leaveType')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'approval_actions' => ApprovalActionResource::collection($this->whenLoaded('approvalActions')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
