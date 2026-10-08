<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'requires_attachment' => $this->requires_attachment,
            'default_annual_quota' => $this->default_annual_quota,
            'is_active' => $this->is_active,
        ];
    }
}
