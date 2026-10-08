<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelLeaveRequestRequest;
use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Requests\StoreLeaveRequestRequest;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveBalanceService;
use App\Services\LeaveRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function __construct(
        private readonly LeaveRequestService $requests,
        private readonly LeaveBalanceService $balances,
    ) {}

    /**
     * Screen: My Requests (FR-HIS-01). Scoped to the authenticated user.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', LeaveRequest::class);

        return view('requests.index', [
            'leaveRequests' => $this->requests->forUser($request->user()),
        ]);
    }

    /**
     * Screen: New Request form (FR-REQ-01..05). Only active leave types are
     * offered, and the user's own balances drive the preview.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', LeaveRequest::class);

        $user = $request->user();
        $year = now()->year;

        return view('requests.create', [
            'leaveTypes' => LeaveType::query()->active()->orderBy('name')->get(),
            'balances' => $this->balances->forUserYear($user, $year)->keyBy('leave_type_id'),
            'year' => $year,
        ]);
    }

    /**
     * ACT-02: submit the request and store the optional attachment.
     */
    public function store(StoreLeaveRequestRequest $request): RedirectResponse
    {
        $leaveRequest = $this->requests->submit(
            $request->user(),
            $request->validated(),
            $request->file('attachment'),
        );

        return redirect()
            ->route('requests.show', $leaveRequest)
            ->with('status', 'Your request was submitted and is now waiting for your supervisor.');
    }

    /**
     * Screen: Request Detail. Reads the ID from the URL via route model
     * binding and enforces the ownership/supervisor/HR rule (PRD 11.3).
     */
    public function show(Request $request, LeaveRequest $leaveRequest): View
    {
        $this->authorize('view', $leaveRequest);

        $leaveRequest->load(['user', 'leaveType', 'attachments', 'approvalActions.actor']);

        return view('requests.show', [
            'leaveRequest' => $leaveRequest,
        ]);
    }

    /**
     * ACT-03: standalone attachment upload while the request is pending.
     */
    public function addAttachment(StoreAttachmentRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->requests->addAttachment($leaveRequest, $request->file('attachment'));

        return $this->detailRedirect($request, $leaveRequest, 'Attachment uploaded.');
    }

    /**
     * ACT-04: cancel a pending request (owner) or any cancellable request
     * (HR).
     */
    public function cancel(CancelLeaveRequestRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->requests->cancel($leaveRequest, $request->user());

        return $this->detailRedirect($request, $leaveRequest, 'The request was cancelled.');
    }

    /**
     * Return to the request detail, preserving the HR "All Requests" origin so
     * the detail page's breadcrumb/back link returns to the list the viewer
     * navigated from (FR-HIS-03) instead of always resetting to "My Requests".
     */
    private function detailRedirect(Request $request, LeaveRequest $leaveRequest, string $status): RedirectResponse
    {
        $parameters = ['leaveRequest' => $leaveRequest];

        if ($request->input('from') === 'hr') {
            $parameters['from'] = 'hr';
        }

        return redirect()->route('requests.show', $parameters)->with('status', $status);
    }
}
