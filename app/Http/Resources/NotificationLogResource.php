<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationLogResource extends JsonResource
{
    /**
     * Delivery record, so HR can confirm whether a notification went out
     * (FR-NOT acceptance criteria).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'recipient' => new UserResource($this->whenLoaded('recipient')),
            'sent_at' => $this->sent_at?->toIso8601String(),
        ];
    }
}
