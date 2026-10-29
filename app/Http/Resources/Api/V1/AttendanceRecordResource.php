<?php

namespace App\Http\Resources\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $isDetail = $request->route() && ($request->route()->getActionMethod() === 'show' || preg_match('/\/api\/v1\/attendance-records\/\d+$/', $request->url()));
        $latestCorrection = $this->relationLoaded('attendanceCorrections')
            ? $this->attendanceCorrections->sortByDesc('created_at')->first()
            : null;

        return [
            'id' => $this->id,

            'user_id' => $this->when(!$isDetail, $this->user_id),
            'user_name' => $this->when(
                !$isDetail,
                function () {
                    return $this->user?->name;
                }
            ),

            'user' => $this->when(
                $isDetail && $this->resource->relationLoaded('user'),
                function () {
                    return new UserResource($this->user);
                }
            ),

            'date' => $this->getRawOriginal('date'),
            'clock_in' => $this->getRawOriginal('clock_in') ? Carbon::parse($this->getRawOriginal('clock_in'))->format('H:i:s') : null,
            'clock_out' => $this->getRawOriginal('clock_out') ? Carbon::parse($this->getRawOriginal('clock_out'))->format('H:i:s') : null,

            'total_time' => $this->when(!$isDetail, $this->total_time),
            'total_break_time' => $this->when(!$isDetail, $this->total_break_time),

            'breaks' => $this->whenLoaded('rests', function () {
                return AttendanceBreakResource::collection($this->rests);
            }),

            'applications' => $this->whenLoaded('attendanceCorrections', function () {
                return ApplicationResource::collection($this->attendanceCorrections);
            }),

            'comment' => $latestCorrection?->comment ?? ' ',

        ];
    }
}