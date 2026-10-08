<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelLeaveRequestRequest;
use App\Http\Requests\IndexLeaveRequestRequest;
use App\Http\Requests\StoreLeaveRequestRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Services\LeaveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaveRequestController extends Controller
{
    public function __construct(private readonly LeaveRequestService $requests) {}

    /**
     * ACT-13: filtered list, scoped by role — own history (employee),
     * direct reports (supervisor), company-wide (HR).
     */
    public function index(IndexLeaveRequestRequest $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 20);

        $query = $this->requests->filtered($filters);

        if (! $user->isHrAdmin()) {
            if ($user->isSupervisor()) {
                $query->whereIn('user_id', $user->directReports()->pluck('id'));
            } else {
                $query->where('user_id', $user->id);
            }
        }

        return LeaveRequestResource::collection($query->paginate($perPage));
    }

    /**
     * ACT-02: submit a new request (status pending_supervisor).
     */
    public function store(StoreLeaveRequestRequest $request): JsonResponse
    {
        $leaveRequest = $this->requests->submit(
            $request->user(),
            $request->validated(),
            $request->file('attachment'),
        );

        return (new LeaveRequestResource($leaveRequest->load(['user', 'leaveType', 'attachments'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Supporting endpoint: request detail (ownership/supervisor/HR scoped).
     */
    public function show(LeaveRequest $leaveRequest): LeaveRequestResource
    {
        $this->authorize('view', $leaveRequest);

        return new LeaveRequestResource(
            $leaveRequest->load(['user', 'leaveType', 'attachments', 'approvalActions.actor']),
        );
    }

    /**
     * ACT-04: cancel a pending request (owner) or any cancellable request
     * (HR).
     */
    public function cancel(CancelLeaveRequestRequest $request, LeaveRequest $leaveRequest): LeaveRequestResource
    {
        $cancelled = $this->requests->cancel($leaveRequest, $request->user());

        return new LeaveRequestResource(
            $cancelled->load(['user', 'leaveType', 'attachments', 'approvalActions.actor']),
        );
    }

    /**
     * ACT-14: stream a CSV for the same filtered period.
     */
    public function export(IndexLeaveRequestRequest $request): StreamedResponse
    {
        $this->authorize('export', LeaveRequest::class);

        $rows = $this->requests->filtered($request->validated())->get();
        $fileName = 'leave-requests-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID', 'Employee', 'Email', 'Department', 'Leave type',
                'Start date', 'End date', 'Days', 'Status', 'Submitted at',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->id,
                    $row->user?->name,
                    $row->user?->email,
                    $row->user?->department,
                    $row->leaveType?->name,
                    $row->start_date?->toDateString(),
                    $row->end_date?->toDateString(),
                    $row->days_requested,
                    $row->status->label(),
                    $row->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }
}
