<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AttendanceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceFilterRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\AttendanceService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendance)
    {
    }

    public function checkIn(Request $request): JsonResponse
    {
        $employee = $this->linkedEmployee($request);

        if ($employee instanceof JsonResponse) {
            return $employee;
        }

        try {
            $record = $this->attendance->checkIn($employee);
        } catch (AttendanceException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode() ?: 422);
        }

        return ApiResponse::success(
            (new AttendanceResource($record->load('employee')))->resolve($request),
            'Check-in recorded.',
            201
        );
    }

    public function checkOut(Request $request): JsonResponse
    {
        $employee = $this->linkedEmployee($request);

        if ($employee instanceof JsonResponse) {
            return $employee;
        }

        try {
            $record = $this->attendance->checkOut($employee);
        } catch (AttendanceException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode() ?: 422);
        }

        return ApiResponse::success(
            (new AttendanceResource($record->load('employee')))->resolve($request),
            'Check-out recorded.'
        );
    }

    public function index(AttendanceFilterRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $paginator = Attendance::query()
            ->with('employee')
            ->forFilters($filters, $request->user())
            ->orderByDesc('attendance_date')
            ->orderByDesc('check_in_at')
            ->paginate($filters['per_page'] ?? 15);

        return ApiResponse::success([
            'items' => AttendanceResource::collection($paginator->items())->resolve($request),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 'Attendance retrieved.');
    }

    /**
     * Check-in and check-out need a linked employee profile.
     * Returns the Employee, or a JSON error response to send back.
     */
    private function linkedEmployee(Request $request): Employee|JsonResponse
    {
        $employee = $request->user()->employee;

        if ($employee === null) {
            return ApiResponse::error('This account is not linked to an employee profile.', 403);
        }

        return $employee;
    }
}
