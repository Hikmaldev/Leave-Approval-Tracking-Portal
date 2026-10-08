<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaveTypeRequest;
use App\Http\Requests\UpdateLeaveTypeRequest;
use App\Http\Requests\UpdateLeaveTypeStatusRequest;
use App\Http\Resources\LeaveTypeResource;
use App\Models\LeaveType;
use App\Services\LeaveTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class LeaveTypeController extends Controller
{
    public function __construct(private readonly LeaveTypeService $leaveTypes) {}

    /**
     * ACT-09: create a leave type.
     */
    public function store(StoreLeaveTypeRequest $request): JsonResponse
    {
        $this->authorize('create', LeaveType::class);

        $leaveType = $this->leaveTypes->create($request->validated());

        return (new LeaveTypeResource($leaveType))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * ACT-09 (edit path): update a leave type.
     */
    public function update(UpdateLeaveTypeRequest $request, LeaveType $leaveType): LeaveTypeResource
    {
        $this->authorize('update', $leaveType);

        return new LeaveTypeResource($this->leaveTypes->update($leaveType, $request->validated()));
    }

    /**
     * ACT-10: activate/deactivate.
     */
    public function updateStatus(UpdateLeaveTypeStatusRequest $request, LeaveType $leaveType): LeaveTypeResource
    {
        $this->authorize('update', $leaveType);

        return new LeaveTypeResource($this->leaveTypes->setActive($leaveType, $request->boolean('is_active')));
    }
}
