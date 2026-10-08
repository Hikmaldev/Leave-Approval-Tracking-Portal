<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexLeaveRequestRequest;
use App\Models\LeaveRequest;
use App\Services\LeaveRequestService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HrLeaveRequestController extends Controller
{
    public function __construct(private readonly LeaveRequestService $requests) {}

    /**
     * Screen: HR All Requests (FR-HIS-03) — company-wide, filterable.
     */
    public function index(IndexLeaveRequestRequest $request): View
    {
        $this->authorize('viewAny', LeaveRequest::class);

        $filters = $request->validated();

        return view('hr.requests', [
            'leaveRequests' => $this->requests->filtered($filters)->get(),
            'filters' => $filters,
        ]);
    }

    /**
     * ACT-14 / FR-HIS-04: stream a CSV for the same filtered period.
     */
    public function export(IndexLeaveRequestRequest $request): StreamedResponse
    {
        $this->authorize('export', LeaveRequest::class);

        $filters = $request->validated();
        $rows = $this->requests->filtered($filters)->get();
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
