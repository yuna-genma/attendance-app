<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_guests_cannot_access_the_report_page(): void
    {
        $response = $this->get('/attendance/report');

        $response->assertRedirect('/login');
    }

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2026, 10, 15, 12, 0, 0));
    }

    /** @test */
    public function test_index_returns_correct_report_data_when_attendance_exists(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $attendance1 = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-01',
        ]);
        Rest::factory()->create([
            'attendance_id' => $attendance1->id,
        ]);

        $attendance2 = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-10-02',
            'clock_in' => '09:15:00',
            'clock_out' => '20:16:00',
        ]);
        Rest::factory()->create([
            'attendance_id' => $attendance2->id
        ]);

        $attendance3 = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-10-03',
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
        ]);
        Rest::factory()->create([
            'attendance_id' => $attendance3->id
        ]);

        $response = $this->actingAs($user)->get('/attendance/report');

        $response->assertStatus(200);

        $response->assertViewHas('summary', function ($summary) {
            return $summary['total_work_minutes'] === 1561
                && $summary['total_overtime_minutes'] === 121
                && $summary['avg_work_minutes'] === 520;
        });

        $response->assertViewHas('monthlyTrend', function ($monthlyTrend) {
            $thisMonthData = collect($monthlyTrend)->firstWhere('month', '2026年10月');
            return count($monthlyTrend) === 6
                && $thisMonthData['work_minutes'] === 1561
                && $thisMonthData['overtime_minutes'] === 121;
        });

        $response->assertViewHas('anomalies', function ($anomalies) {
            return $anomalies['late_count'] === 1
                && $anomalies['early_leave_count'] === 1
                && $anomalies['long_work_count'] === 1;
        });
    }

    /** @test */
    public function test_user_no_attendance_records_handled_safely(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get('/attendance/report');
        $response->assertStatus(200);

        $response->assertViewHas('summary', function ($summary) {
            return $summary['total_work_minutes'] === 0
                && $summary['total_overtime_minutes'] === 0
                && $summary['avg_work_minutes'] === 0;
        });

        $response->assertViewHas('monthlyTrend', function ($monthlyTrend) {
            $allZero = collect($monthlyTrend)->every(function ($row) {
                return $row['work_minutes'] === 0 && $row['overtime_minutes'] === 0;
            });
            return count($monthlyTrend) === 6 && $allZero;
        });

        $response->assertViewHas('anomalies', function ($anomalies) {
            return $anomalies['late_count'] === 0
                && $anomalies['early_leave_count'] === 0
                && $anomalies['long_work_count'] === 0;
        });
    }
}
