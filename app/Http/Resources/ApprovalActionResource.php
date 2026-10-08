<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalActionResource extends JsonResource
{
    /**
     * Audit trail entry for one approval step (FR-APR-07).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'step' => $this->step->value,
            'step_label' => $this->step->label(),
            'decision' => $this->decision->value,
            'decision_label' => $this->decision->label(),
            'comment' => $this->comment,
            'actor' => new UserResource($this->whenLoaded('actor')),
            'decided_at' => $this->decided_at?->toIso8601String(),
        ];
    }
}
