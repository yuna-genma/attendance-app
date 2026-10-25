<?php

namespace Tests\Feature;

use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Rest;
use Carbon\Carbon;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_admin_attendance_index_can_get_all_users(): void
    {
        Carbon::setTestNow('2026-10-04');

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $userA->id,
            'date' => '2026-10-03',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $userA->id,
            'date' => now()->format('Y-m-d'),
            'clock_in' => '08:00',
            'clock_out' => '19:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $userB->id,
            'date' => now()->format('Y-m-d'),
            'clock_in' => '10:00',
            'clock_out' => '20:00',
        ]);

        try {
            $response = $this->actingAs($admin)->get('/admin/attendance/list?date=2026-10-04');

            $response->assertStatus(200);

            $response->assertViewHas('attendanceRecords', function ($attendanceRecords) {
                return $attendanceRecords->count() === 2;
            });

            $response->assertSeeInOrder([
                $userA->name,
                '08:00',
                '19:00',
                $userB->name,
                '10:00',
                '20:00',
            ]);

            $response->assertDontSee('09:00');
        } finally {
            Carbon::setTestNow();
        }
    }

    /** @test */
    public function test_admin_attendance_index_can_get_today(): void
    {
        Carbon::setTestNow('2026-10-04');

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-03',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
            'clock_in' => '08:00',
            'clock_out' => '19:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-05',
            'clock_in' => '10:00',
            'clock_out' => '20:00',
        ]);

        try {
            $response = $this->actingAs($admin)->get('/admin/attendance/list');

            $response->assertStatus(200);

            $response->assertSee('2026/10/04');
            $response->assertSee('08:00');
            $response->assertSee('19:00');

            $response->assertDontSee('2026/10/03');
            $response->assertDontSee('09:00');
            $response->assertDontSee('2026/10/05');
            $response->assertDontSee('10:00');

        } finally {
            Carbon::setTestNow();
        }
    }

    /** @test */
    public function test_admin_attendance_index_can_get_previousday(): void
    {
        Carbon::setTestNow('2026-10-04');

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-03',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
            'clock_in' => '08:00',
            'clock_out' => '19:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-05',
            'clock_in' => '10:00',
            'clock_out' => '20:00',
        ]);

        try {
            $response = $this->actingAs($admin)->get('/admin/attendance/list?date=2026-10-03');

            $response->assertStatus(200);

            $response->assertSee('2026/10/03');
            $response->assertSee('09:00');
            $response->assertSee('18:00');

            $response->assertDontSee('2026/10/04');
            $response->assertDontSee('08:00');
            $response->assertDontSee('2026/10/05');
            $response->assertDontSee('10:00');

        } finally {
            Carbon::setTestNow();
        }
    }

    /** @test */
    public function test_admin_attendance_index_can_get_nextday(): void
    {
        Carbon::setTestNow('2026-10-04');

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-03',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
            'clock_in' => '08:00',
            'clock_out' => '19:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-05',
            'clock_in' => '10:00',
            'clock_out' => '20:00',
        ]);

        try {
            $response = $this->actingAs($admin)->get('/admin/attendance/list?date=2026-10-05');

            $response->assertStatus(200);

            $response->assertSee('2026/10/05');
            $response->assertSee('10:00');
            $response->assertSee('20:00');

            $response->assertDontSee('2026/10/04');
            $response->assertDontSee('08:00');
            $response->assertDontSee('2026/10/03');
            $response->assertDontSee('09:00');

        } finally {
            Carbon::setTestNow();
        }
    }

    /** @test */
    public function test_admin_can_get_attendance_detail(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-04',
            'clock_in' => '10:00',
            'clock_out' => '20:00',
        ]);

        Rest::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00',
            'break_out' => '13:00',
        ]);

        $response = $this->actingAs($admin)->get("/admin/attendance/{$attendance->id}");
        $response->assertStatus(200);
        $response->assertSeeInOrder([
            $user->name,
            '2026年',
            '10月04日',
            '10:00',
            '20:00',
            '12:00',
            '13:00',
        ]);
    }

    /** @test */
    public function test_admin_clock_in_time_cannot_be_after_clock_out_time(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-04',
            'clock_in' => '10:00',
            'clock_out' => '20:00',
        ]);

        Rest::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00',
            'break_out' => '13:00',
        ]);

        $showResponse = $this->actingAs($admin)->get("/admin/attendance/{$attendance->id}");
        $showResponse->assertStatus(200);

        $updateResponse = $this->put("/admin/attendance/{$attendance->id}", [
            'new_clock_in' => '21:00',
            'new_clock_out' => '08:00',
            'comment' => '更新失敗',
        ]);

        $updateResponse->assertStatus(302);
        $updateResponse->assertRedirect("/admin/attendance/{$attendance->id}");
        $updateResponse->assertSessionHasErrors([
            'new_clock_in' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /** @test */
    public function test_admin_break_in_time_cannot_be_after_clock_out_time(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-04',
            'clock_in' => '10:00',
            'clock_out' => '20:00',
        ]);

        Rest::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00',
            'break_out' => '13:00',
        ]);

        $showResponse = $this->actingAs($admin)->get("/admin/attendance/{$attendance->id}");
        $showResponse->assertStatus(200);

        $updateResponse = $this->put("/admin/attendance/{$attendance->id}", [
            'new_clock_in' => '08:00',
            'new_clock_out' => '18:00',
            'new_break_in' => ['20:00'],
            'new_break_out' => ['21:00'],
            'comment' => '更新失敗',
        ]);

        $updateResponse->assertStatus(302);
        $updateResponse->assertRedirect("/admin/attendance/{$attendance->id}");
        $updateResponse->assertSessionHasErrors([
            'new_break_in.0' => '休憩時間が不適切な値です'
        ]);
    }

    /** @test */
    public function test_admin_break_out_time_cannot_be_after_clock_out_time(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-04',
            'clock_in' => '10:00',
            'clock_out' => '20:00',
        ]);

        Rest::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00',
            'break_out' => '13:00',
        ]);

        $showResponse = $this->actingAs($admin)->get("/admin/attendance/{$attendance->id}");
        $showResponse->assertStatus(200);

        $updateResponse = $this->put("/admin/attendance/{$attendance->id}", [
            'new_clock_in' => '08:00',
            'new_clock_out' => '18:00',
            'new_break_in' => ['17:00'],
            'new_break_out' => ['21:00'],
            'comment' => '更新失敗',
        ]);

        $updateResponse->assertStatus(302);
        $updateResponse->assertRedirect("/admin/attendance/{$attendance->id}");
        $updateResponse->assertSessionHasErrors([
            'new_break_out.0' => '休憩時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /** @test */
    public function test_admin_comment_must_be_required(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-04',
            'clock_in' => '10:00',
            'clock_out' => '20:00',
        ]);

        Rest::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00',
            'break_out' => '13:00',
        ]);

        $showResponse = $this->actingAs($admin)->get("/admin/attendance/{$attendance->id}");
        $showResponse->assertStatus(200);

        $updateResponse = $this->put("/admin/attendance/{$attendance->id}", [
            'new_clock_in' => '08:00',
            'new_clock_out' => '18:00',
            'new_break_in' => ['12:00'],
            'new_break_out' => ['13:00'],
            'comment' => ' ',
        ]);

        $updateResponse->assertStatus(302);
        $updateResponse->assertRedirect("/admin/attendance/{$attendance->id}");
        $updateResponse->assertSessionHasErrors([
            'comment' => '備考を記入してください'
        ]);
    }

    /** @test */
    public function test_all_general_users_can_get(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        User::factory()->create([
            'name' => 'テスト花子',
            'email' => 'test1@example.com',
        ]);
        User::factory()->create([
            'name' => '山田太郎',
            'email' => 'test2@example.com',
        ]);

        $response = $this->actingAs($admin)->get('/admin/staff/list');
        $response->assertStatus(200);
        $response->assertSee('テスト花子');
        $response->assertSee('test1@example.com');
        $response->assertSee('山田太郎');
        $response->assertSee('test2@example.com');
        $response->assertDontSee($admin->name);
    }

    /** @test */
    public function test_user_attendance_list_can_get(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'テスト花子',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-04',
            'clock_in' => '10:00',
            'clock_out' => '20:00',
        ]);

        Rest::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00',
            'break_out' => '13:00',
        ]);

        $response = $this->actingAs($admin)->get("/admin/attendance/staff/{$user->id}");
        $response->assertStatus(200);
        $response->assertSeeInOrder([
            'テスト花子',
            '10/04(日)',
            '10:00',
            '20:00',
            '1:00',
            '9:00',
        ]);
    }

    /** @test */
    public function test_user_index_previous_month_deta_can_get(): void
    {
        Carbon::setTestNow('2026-10-04');

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create();
        $attendance = Attendance::factory()->count(5)
            ->sequence(
                ['date' => now()->startOfMonth()->format('Y-m-d')],
                ['date' => now()->startOfMonth()->subMonth()->format('Y-m-d')],
                ['date' => now()->startOfMonth()->subMonth()->addDay()->format('Y-m-d')],
                ['date' => now()->startOfMonth()->addMonth()->format('Y-m-d')],
                ['date' => now()->startOfMonth()->addMonth()->addDay()->format('Y-m-d')],
            )
            ->create(['user_id' => $user->id]);

        $previousMonthParam = now()->subMonth()->format('Y-m');

        $response = $this->actingAs($admin)->get("/admin/attendance/staff/{$user->id}?date={$previousMonthParam}");
        $response->assertStatus(200);
        $response->assertSee('2026/09');
        $response->assertSee(Carbon::parse($attendance[1]->date)->format('m/d'));
        $response->assertSee(Carbon::parse($attendance[2]->date)->format('m/d'));

        $response->assertDontSee(Carbon::parse($attendance[0]->date)->format('m/d'));
        $response->assertDontSee(Carbon::parse($attendance[3]->date)->format('m/d'));
        $response->assertDontSee(Carbon::parse($attendance[4]->date)->format('m/d'));

        Carbon::setTestNow();
    }

    /** @test */
    public function test_user_index_next_month_deta_can_get(): void
    {
        Carbon::setTestNow('2026-10-04');

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create();
        $attendance = Attendance::factory()->count(5)
            ->sequence(
                ['date' => now()->startOfMonth()->format('Y-m-d')],
                ['date' => now()->startOfMonth()->subMonth()->format('Y-m-d')],
                ['date' => now()->startOfMonth()->subMonth()->addDay()->format('Y-m-d')],
                ['date' => now()->startOfMonth()->addMonth()->format('Y-m-d')],
                ['date' => now()->startOfMonth()->addMonth()->addDay()->format('Y-m-d')],
            )
            ->create(['user_id' => $user->id]);

        $nextMonthParam = now()->addMonth()->format('Y-m');

        $response = $this->actingAs($admin)->get("/admin/attendance/staff/{$user->id}?date={$nextMonthParam}");
        $response->assertStatus(200);
        $response->assertSee('2026/11');
        $response->assertSee(Carbon::parse($attendance[3]->date)->format('m/d'));
        $response->assertSee(Carbon::parse($attendance[4]->date)->format('m/d'));

        $response->assertDontSee(Carbon::parse($attendance[0]->date)->format('m/d'));
        $response->assertDontSee(Carbon::parse($attendance[1]->date)->format('m/d'));
        $response->assertDontSee(Carbon::parse($attendance[2]->date)->format('m/d'));

        Carbon::setTestNow();
    }

    /** @test */
    public function test_transition_attendance_detail(): void
    {
        Carbon::setTestNow('2026-10-04');

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
        ]);

        $listResponse = $this->actingAs($admin)->get("/admin/attendance/staff/{$user->id}");
        $listResponse->assertStatus(200);

        $showResponse = $this->actingAs($admin)
            ->followingRedirects()
            ->get('/admin/attendance/' . $attendance->id);

        $showResponse->assertStatus(200);
        $showResponse->assertSee('勤怠詳細');
        $showResponse->assertSee('2026年');
        $showResponse->assertSee('10月04日');

        Carbon::setTestNow();
    }
}