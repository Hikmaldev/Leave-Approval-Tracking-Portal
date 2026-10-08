<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveTypeRequest;
use App\Http\Requests\UpdateLeaveTypeRequest;
use App\Http\Requests\UpdateLeaveTypeStatusRequest;
use App\Models\LeaveType;
use App\Services\LeaveTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LeaveTypeController extends Controller
{
    public function __construct(private readonly LeaveTypeService $leaveTypes) {}

    /**
     * Screen: Leave Type Settings. Inactive types stay visible here but not
     * in the request form (13.6).
     */
    public function index(): View
    {
        $this->authorize('manageAny', LeaveType::class);

        return view('hr.leave-types', [
            'leaveTypes' => LeaveType::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreLeaveTypeRequest $request): RedirectResponse
    {
        $this->authorize('create', LeaveType::class);

        $this->leaveTypes->create($request->validated());

        return back()->with('status', 'The leave type was created.');
    }

    public function update(UpdateLeaveTypeRequest $request, LeaveType $leaveType): RedirectResponse
    {
        $this->authorize('update', $leaveType);

        $this->leaveTypes->update($leaveType, $request->validated());

        return back()->with('status', 'The leave type was updated.');
    }

    /**
     * ACT-10: activate/deactivate toggle.
     */
    public function updateStatus(UpdateLeaveTypeStatusRequest $request, LeaveType $leaveType): RedirectResponse
    {
        $this->authorize('update', $leaveType);

        $this->leaveTypes->setActive($leaveType, $request->boolean('is_active'));

        return back()->with('status', 'The leave type status was updated.');
    }
}
