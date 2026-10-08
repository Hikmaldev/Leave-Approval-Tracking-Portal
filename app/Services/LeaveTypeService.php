<?php

namespace App\Services;

use App\Models\LeaveType;

class LeaveTypeService
{
    /**
     * ACT-09 / FR 13.6: create a leave type. Only HR reaches this service
     * (enforced by the request + policy).
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): LeaveType
    {
        return LeaveType::query()->create([
            'name' => $data['name'],
            'requires_attachment' => (bool) $data['requires_attachment'],
            'default_annual_quota' => (int) $data['default_annual_quota'],
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }

    /**
     * ACT-09 (edit path): changes apply to new requests only; already
     * submitted requests keep their original configuration (business rule 5).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(LeaveType $leaveType, array $data): LeaveType
    {
        $leaveType->fill([
            'name' => $data['name'],
            'requires_attachment' => (bool) $data['requires_attachment'],
            'default_annual_quota' => (int) $data['default_annual_quota'],
        ]);

        if (array_key_exists('is_active', $data)) {
            $leaveType->is_active = (bool) $data['is_active'];
        }

        $leaveType->save();

        return $leaveType;
    }

    /**
     * ACT-10: activate/deactivate. Deactivating hides the type from the new
     * request form but never touches existing requests.
     */
    public function setActive(LeaveType $leaveType, bool $isActive): LeaveType
    {
        $leaveType->is_active = $isActive;
        $leaveType->save();

        return $leaveType;
    }
}
