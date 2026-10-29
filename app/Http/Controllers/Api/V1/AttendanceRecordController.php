<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use App\Http\Resources\Api\V1\AttendanceRecordResource;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class AttendanceRecordController extends Controller
{
    public function index(IndexAttendanceRecordRequest $request): AnonymousResourceCollection
    {
        $query = Attendance::with(['user']);
        $perPage = $request->input('per_page', 20);

        $attendanceRecord_records = $query
            ->when($request->filled('user_id'), function ($q) use ($request) {
                $q->where('user_id', $request->user_id);
            })
            ->when($request->filled('date'), function ($q) use ($request) {
                $q->where('date', $request->date);
            })
            ->when($request->filled('month'), function ($q) use ($request) {
                $q->where('date', 'like', $request->month.'%');
                $date = Carbon::parse($request->month);
                $q->whereYear('date', $date->year)->whereMonth('date', $date->month);
            })
            ->latest('date')
            ->paginate($perPage);

        return AttendanceRecordResource::collection($attendanceRecord_records);
    }

    public function show(Attendance $attendanceRecord): AttendanceRecordResource
    {
        $attendanceRecord->load(['user', 'rests', 'attendanceCorrections']);

        return new AttendanceRecordResource($attendanceRecord);
    }

    public function store(StoreAttendanceRecordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $attendanceRecord = DB::transaction(function () use ($request, $validated) {
            $attendance = $request->user()->attendances()->create($validated);

            $attendance->attendanceCorrections()->create([
                'user_id' => $request->user()->id,
                'comment' => $validated['comment'] ?? null,
            ]);

            return $attendance;
        });

        $attendanceRecord->load(['user', 'rests', 'attendanceCorrections']);

        return (new AttendanceRecordResource($attendanceRecord))
            ->response()->setStatusCode(201);
    }

    public function update(UpdateAttendanceRecordRequest $request, Attendance $attendanceRecord): AttendanceRecordResource
    {
        $this->authorize('update', $attendanceRecord);

        $validated = $request->validated();

        $attendanceRecord = DB::transaction(function () use ($request, $validated, $attendanceRecord) {
            $attendanceRecord->update($validated);

            $attendanceRecord->attendanceCorrections()->updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                ],
                [
                    'comment' => $validated['comment'] ?? null,
                ]
            );

            return $attendanceRecord;
        });

        $attendanceRecord->load(['user', 'rests', 'attendanceCorrections']);

        return new AttendanceRecordResource($attendanceRecord);
    }

    public function destroy(Attendance $attendanceRecord): JsonResponse
    {
        $this->authorize('delete', $attendanceRecord);

        $attendanceRecord->delete();

        return response()->json(null, 204);
    }
}
