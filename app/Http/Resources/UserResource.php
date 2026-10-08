<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'role_label' => $this->role->label(),
            'department' => $this->department,
            'is_active' => $this->is_active,
            'supervisor' => new UserResource($this->whenLoaded('supervisor')),
            'direct_reports' => UserResource::collection($this->whenLoaded('directReports')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
