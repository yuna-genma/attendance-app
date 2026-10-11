<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;

class AttendanceControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_get_date_and_time(): void
    {
        $knownDate = Carbon::create(2026, 10, 4, 13, 5, 0, 'Asia/Tokyo');
        Carbon::setTestNow($knownDate);

        $expectedDate = $knownDate->toDateString();
        $expectedTime = $knownDate->format('H:i');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertViewIs('user.attendance-register');

        $response->assertSee($expectedDate);
        $response->assertSee($expectedTime);

        Carbon::setTestNow();
    }

    /** @test */
    public function test_before_working_status_can_get(): void
    {
        $knownDate = Carbon::create(2026, 10, 4, 10, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($knownDate);

        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-03',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'attendance_status' => AttendanceStatus::LEFT,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('<p class="attendance__status--item">勤務外</p>', false);

        Carbon::setTestNow();
    }

    /** @test */
    public function test_working_status_can_get(): void
    {
        $knownDate = Carbon::create(2026, 10, 4, 10, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($knownDate);

        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $knownDate->toDateString(),
            'clock_in' => '09:00',
            'attendance_status' => AttendanceStatus::WORKING,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('<p class="attendance__status--item">出勤中</p>', false);

        Carbon::setTestNow();
    }

    /** @test */
    public function test_breaking_status_can_get(): void
    {
        $knownDate = Carbon::create(2026, 10, 4, 10, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($knownDate);

        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $knownDate->toDateString(),
            'clock_in' => '09:00',
            'attendance_status' => AttendanceStatus::BREAKING,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('<p class="attendance__status--item">休憩中</p>', false);

        Carbon::setTestNow();
    }

    /** @test */
    public function test_left_status_can_get(): void
    {
        $knownDate = Carbon::create(2026, 10, 4, 10, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($knownDate);

        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $knownDate->toDateString(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'attendance_status' => AttendanceStatus::LEFT,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('<p class="attendance__status--item">退勤済</p>', false);

        Carbon::setTestNow();
    }

    /** @test */
    public function test_user_can_clock_in(): void
    {
        $knownDate = Carbon::create(2026, 10, 4, 10, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($knownDate);

        $user = User::factory()->create();

        $showResponse = $this->actingAs($user)->get('/attendance');
        $showResponse->assertStatus(200);
        $showResponse->assertSee('value="clock_in"', false);
        $showResponse->assertSee('出勤</button>', false);

        $data = [
            'action' => 'clock_in',
        ];

        $postResponse = $this->actingAs($user)
            ->from('/attendance')
            ->followingRedirects()->post('/attendance', $data);

        $postResponse->assertStatus(200);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => '2026-10-04',
            'clock_in' => '10:00',
            'attendance_status' => AttendanceStatus::WORKING->value,
        ]);

        $postResponse->assertSee('<p class="attendance__status--item">出勤中</p>', false);

        Carbon::setTestNow();
    }

    /** @test */
    public function test_attendance_register_once_a_day(): void
    {
        $knownDate = Carbon::create(2026, 10, 4, 10, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($knownDate);

        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $knownDate->toDateString(),
            'clock_in' => '10:00',
            'clock_out' => '21:00',
            'attendance_status' => AttendanceStatus::LEFT,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertDontSee('value="clock_in"', false);
        $response->assertDontSee('出勤</button>', false);

        Carbon::setTestNow();
    }

    /** @test */
    public function test_attendance_index_can_get_work_time(): void
    {
        $knownDate = Carbon::create(2026, 10, 4, 10, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($knownDate);

        $user = User::factory()->create();

        $data = [
            'action' => 'clock_in',
        ];

        $response = $this->actingAs($user)
            ->from('/attendance')->post('/attendance', $data);

        $response->assertStatus(302);

        $expectedDate = $knownDate->isoFormat('MM/DD(ddd)');
        $expectedTime = '10:00';

        $listResponse = $this->get('/attendance/list');

        $listResponse->assertStatus(200);
        $listResponse->assertSee($expectedDate);
        $listResponse->assertSee($expectedTime);

        Carbon::setTestNow();
    }

    /** @test */
    public function test_user_can_break_in(): void
    {
        $knownDate = Carbon::create(2026, 10, 4, 10, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($knownDate);

        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $knownDate->toDateString(),
            'clock_in' => '10:00',
            'attendance_status' => AttendanceStatus::WORKING,
        ]);

        $showResponse = $this->actingAs($user)->get('/attendance');
        $showResponse->assertStatus(200);
        $showResponse->assertSee('value="break_in"', false);
        $showResponse->assertSee('休憩入</button>', false);

        $breakTime = Carbon::create(2026, 10, 4, 13, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($breakTime);

        $data = [
            'action' => 'break_in',
        ];

        $postResponse = $this->actingAs($user)
            ->from('/attendance')
            ->followingRedirects()->post('/attendance', $data);

        $postResponse->assertStatus(200);

        $this->assertDatabaseHas('rests', [
            'attendance_id' => $attendance->id,
            'break_in' => '13:00:00',
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'attendance_status' => AttendanceStatus::BREAKING,
        ]);

        $postResponse->assertSee('<p class="attendance__status--item">休憩中</p>', false);

        Carbon::setTestNow();
    }

    /** @test */
    public function test_user_can_break_out(): void
    {
        $knownDate = Carbon::create(2026, 10, 4, 10, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($knownDate);

        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $knownDate->toDateString(),
            'clock_in' => '10:00',
            'attendance_status' => AttendanceStatus::WORKING,
        ]);

        $clockInResponse = $this->actingAs($user)->get('/attendance');
        $clockInResponse->assertStatus(200);

        $breakInTime = Carbon::create(2026, 10, 4, 13, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($breakInTime);

        $breakIn = [
            'action' => 'break_in',
        ];

        $breakInResponse = $this->actingAs($user)
            ->from('/attendance')
            ->followingRedirects()->post('/attendance', $breakIn);
        $breakInResponse->assertStatus(200);
        $breakInResponse->assertSee('value="break_out"', false);
        $breakInResponse->assertSee('休憩戻</button>', false);

        $breakOutTime = Carbon::create(2026, 10, 4, 15, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($breakOutTime);

        $breakOut = [
            'action' => 'break_out',
        ];

        $breakOutResponse = $this->actingAs($user)
            ->from('/attendance')
            ->followingRedirects()->post('/attendance', $breakOut);

        $breakOutResponse->assertStatus(200);

        $this->assertDatabaseHas('rests', [
            'attendance_id' => $attendance->id,
            'break_in' => '13:00:00',
            'break_out' => '15:00:00',
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'attendance_status' => AttendanceStatus::WORKING,
        ]);

        $breakOutResponse->assertSee('<p class="attendance__status--item">出勤中</p>', false);

        Carbon::setTestNow();
    }

    /** @test */
    public function test_user_break_in_multiple_times_a_day(): void
    {
        $knownDate = Carbon::create(2026, 10, 4, 10, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($knownDate);

        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $knownDate->toDateString(),
            'clock_in' => '10:00',
            'attendance_status' => AttendanceStatus::WORKING,
        ]);

        $clockInResponse = $this->actingAs($user)->get('/attendance');
        $clockInResponse->assertStatus(200);

        $breakInTime = Carbon::create(2026, 10, 4, 13, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($breakInTime);

        $breakIn = [
            'action' => 'break_in',
        ];

        $breakInResponse = $this->actingAs($user)
            ->from('/attendance')
            ->followingRedirects()->post('/attendance', $breakIn);
        $breakInResponse->assertStatus(200);

        $breakOutTime = Carbon::create(2026, 10, 4, 14, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($breakOutTime);

        $breakOut = [
            'action' => 'break_out',
        ];

        $breakOutResponse = $this->actingAs($user)
            ->from('/attendance')
            ->followingRedirects()->post('/attendance', $breakOut);

        $breakOutResponse->assertStatus(200);

        $this->assertDatabaseHas('rests', [
            'attendance_id' => $attendance->id,
            'break_in' => '13:00:00',
            'break_out' => '14:00:00',
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'attendance_status' => AttendanceStatus::WORKING,
        ]);

        $breakOutResponse->assertSee('value="break_in"', false);
        $breakOutResponse->assertSee('休憩入</button>', false);

        Carbon::setTestNow();
    }

    /** @test */
    public function test_user_break_out_multiple_times_a_day(): void
    {
        $knownDate = Carbon::create(2026, 10, 4, 10, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($knownDate);

        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $knownDate->toDateString(),
            'clock_in' => '10:00',
            'attendance_status' => AttendanceStatus::WORKING,
        ]);

        $clockInResponse = $this->actingAs($user)->get('/attendance');
        $clockInResponse->assertStatus(200);

        $firstBreakInTime = Carbon::create(2026, 10, 4, 11, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($firstBreakInTime);

        $firstBreakIn = [
            'action' => 'break_in',
        ];

        $firstBreakInResponse = $this->actingAs($user)
            ->from('/attendance')
            ->followingRedirects()->post('/attendance', $firstBreakIn);
        $firstBreakInResponse->assertStatus(200);

        $firstBreakOutTime = Carbon::create(2026, 10, 4, 12, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($firstBreakOutTime);

        $firstBreakOut = [
            'action' => 'break_out',
        ];

        $firstBreakOutResponse = $this->actingAs($user)
            ->from('/attendance')
            ->followingRedirects()->post('/attendance', $firstBreakOut);
        $firstBreakOutResponse->assertStatus(200);

        $this->assertDatabaseHas('rests', [
            'attendance_id' => $attendance->id,
            'break_in' => '11:00:00',
            'break_out' => '12:00:00',
        ]);

        $secondBreakInTime = Carbon::create(2026, 10, 4, 16, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($secondBreakInTime);

        $secondBreakIn = [
            'action' => 'break_in',
        ];

        $secondBreakInResponse = $this->actingAs($user)
            ->from('/attendance')
            ->followingRedirects()->post('/attendance', $secondBreakIn);
        $secondBreakInResponse->assertStatus(200);

        $secondBreakOutTime = Carbon::create(2026, 10, 4, 17, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($secondBreakOutTime);

        $secondBreakOut = [
            'action' => 'break_out'
        ];

        $secondBreakOutResponse = $this->actingAs($user)
            ->from('/attendance')
            ->followingRedirects()->post('/attendance', $secondBreakOut);
        $secondBreakOutResponse->assertStatus(200);

        $this->assertDatabaseHas('rests', [
            'attendance_id' => $attendance->id,
            'break_in' => '16:00:00',
            'break_out' => '17:00:00',
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'attendance_status' => AttendanceStatus::WORKING,
        ]);

        $secondBreakOutResponse->assertSee('<p class="attendance__status--item">出勤中</p>', false);

        Carbon::setTestNow();
    }

    /** @test */
    public function test_attendance_index_can_get_break_time(): void
    {
        $knownDate = Carbon::create(2026, 10, 4, 10, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($knownDate);

        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $knownDate->toDateString(),
            'clock_in' => '10:00',
            'attendance_status' => AttendanceStatus::WORKING,
        ]);

        $clockInResponse = $this->actingAs($user)->get('/attendance');
        $clockInResponse->assertStatus(200);

        $breakInTime = Carbon::create(2026, 10, 4, 13, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($breakInTime);

        $breakIn = [
            'action' => 'break_in',
        ];

        $breakInResponse = $this->actingAs($user)
            ->from('/attendance')
            ->followingRedirects()->post('/attendance', $breakIn);
        $breakInResponse->assertStatus(200);

        $breakOutTime = Carbon::create(2026, 10, 4, 14, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($breakOutTime);

        $breakOut = [
            'action' => 'break_out',
        ];

        $breakOutResponse = $this->actingAs($user)
            ->from('/attendance')
            ->followingRedirects()->post('/attendance', $breakOut);

        $breakOutResponse->assertStatus(200);

        $this->assertDatabaseHas('rests', [
            'attendance_id' => $attendance->id,
            'break_in' => '13:00:00',
            'break_out' => '14:00:00',
        ]);

        $expectedDate = $attendance->date->isoFormat('MM/DD(ddd)');
        $expectedTime = $attendance->total_break_time;

        $listResponse = $this->actingAs($user)->get('/attendance/list');

        $listResponse->assertStatus(200);
        $listResponse->assertSee($expectedDate);
        $listResponse->assertSee($expectedTime);

        Carbon::setTestNow();
    }

    /** @test */
    public function test_user_can_clock_out(): void
    {
        $knownDate = Carbon::create(2026, 10, 4, 10, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($knownDate);

        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $knownDate->toDateString(),
            'clock_in' => '10:00',
            'attendance_status' => AttendanceStatus::WORKING,
        ]);

        $showResponse = $this->actingAs($user)->get('/attendance');
        $showResponse->assertStatus(200);
        $showResponse->assertSee('value="break_in"', false);
        $showResponse->assertSee('退勤</button>', false);

        $clockOutTime = Carbon::create(2026, 10, 4, 20, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($clockOutTime);

        $data = [
            'action' => 'clock_out',
        ];

        $postResponse = $this->actingAs($user)
            ->from('/attendance')
            ->followingRedirects()->post('/attendance', $data);

        $postResponse->assertStatus(200);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => '2026-10-04',
            'clock_in' => '10:00',
            'clock_out' => '20:00',
            'attendance_status' => AttendanceStatus::LEFT->value,
        ]);

        $postResponse->assertSee('<p class="attendance__status--item">退勤済</p>', false);

        Carbon::setTestNow();
    }

    /** @test */
    public function test_attendance_index_can_get_clock_out_time(): void
    {
        $knownDate = Carbon::create(2026, 10, 4, 10, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($knownDate);

        $user = User::factory()->create();

        $showResponse = $this->actingAs($user)->get('/attendance');
        $showResponse->assertStatus(200);

        $clockIn = [
            'action' => 'clock_in',
        ];

        $clockInResponse = $this->actingAs($user)
            ->from('/attendance')
            ->followingRedirects()->post('/attendance', $clockIn);
        $clockInResponse->assertStatus(200);

        $clockOutTime = Carbon::create(2026, 10, 4, 20, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($clockOutTime);

        $clockOut = [
            'action' => 'clock_out',
        ];

        $postResponse = $this->actingAs($user)
            ->from('/attendance')
            ->followingRedirects()->post('/attendance', $clockOut);

        $postResponse->assertStatus(200);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => '2026-10-04',
            'clock_in' => '10:00',
            'clock_out' => '20:00',
            'attendance_status' => AttendanceStatus::LEFT->value,
        ]);

        $attendance = Attendance::where('user_id', $user->id)->first();

        $expectedDate = $attendance->date->isoFormat('MM/DD(ddd)');
        $expectedTime = '20:00';

        $listResponse = $this->get('/attendance/list');

        $listResponse->assertStatus(200);
        $listResponse->assertSee($expectedDate);
        $listResponse->assertSee($expectedTime);

        Carbon::setTestNow();
    }

    /** @test */
    public function test_attendance_list_can_get(): void
    {
        $user = User::factory()->create();
        $attendances = Attendance::factory()->count(3)->
            sequence(
                ['date' => now()->startOfMonth()->format('Y-m-d')],
                ['date' => now()->startOfMonth()->addDay()->format('Y-m-d')],
                ['date' => now()->startOfMonth()->addDays(2)->format('Y-m-d')],
            )
            ->create([
                'user_id' => $user->id,
            ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);

        foreach ($attendances as $attendance) {
            $date = Carbon::parse($attendance->date);
            $response->assertSee($date->format('m/d'));
        }
    }

    /** @test */
    public function test_transition_attendance_list_can_get_this_month(): void
    {
        Carbon::setTestNow('2026-10-04');

        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $loginResponse = $this->post(route('user.login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $loginResponse->assertRedirect('/attendance');
        $this->assertAuthenticatedAs($user);

        $listResponse = $this->get('/attendance/list');

        $listResponse->assertStatus(200);
        $listResponse->assertSee('2026/10');

        Carbon::setTestNow();
    }

    /** @test */
    public function test_previous_month_deta_can_get(): void
    {
        Carbon::setTestNow('2026-10-04');

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

        $response = $this->actingAs($user)->get('/attendance/list');
        $response->assertStatus(200);

        $previousMonthParam = now()->subMonth()->format('Y-m');

        $responsePrevious = $this->actingAs($user)->get("/attendance/list?date={$previousMonthParam}");
        $responsePrevious->assertStatus(200);
        $responsePrevious->assertSee('2026/09');
        $responsePrevious->assertSee(Carbon::parse($attendance[1]->date)->format('m/d'));
        $responsePrevious->assertSee(Carbon::parse($attendance[2]->date)->format('m/d'));

        $responsePrevious->assertDontSee(Carbon::parse($attendance[0]->date)->format('m/d'));
        $responsePrevious->assertDontSee(Carbon::parse($attendance[3]->date)->format('m/d'));
        $responsePrevious->assertDontSee(Carbon::parse($attendance[4]->date)->format('m/d'));

        Carbon::setTestNow();
    }

    /** @test */
    public function test_next_month_deta_can_get(): void
    {
        Carbon::setTestNow('2026-10-04');

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

        $response = $this->actingAs($user)->get('/attendance/list');
        $response->assertStatus(200);

        $nextMonthParam = now()->addMonth()->format('Y-m');

        $responsePrevious = $this->actingAs($user)->get("/attendance/list?date={$nextMonthParam}");
        $responsePrevious->assertStatus(200);
        $responsePrevious->assertSee('2026/11');
        $responsePrevious->assertSee(Carbon::parse($attendance[3]->date)->format('m/d'));
        $responsePrevious->assertSee(Carbon::parse($attendance[4]->date)->format('m/d'));

        $responsePrevious->assertDontSee(Carbon::parse($attendance[0]->date)->format('m/d'));
        $responsePrevious->assertDontSee(Carbon::parse($attendance[1]->date)->format('m/d'));
        $responsePrevious->assertDontSee(Carbon::parse($attendance[2]->date)->format('m/d'));

        Carbon::setTestNow();
    }

    /** @test */
    public function test_transition_attendance_detail(): void
    {
        Carbon::setTestNow('2026-10-04');

        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
        ]);

        $listResponse = $this->actingAs($user)->get('/attendance/list');
        $listResponse->assertStatus(200);
        $listResponse->assertSee('/attendance/' . $attendance->id);

        $showResponse = $this->actingAs($user)
            ->followingRedirects()
            ->get('/attendance/' . $attendance->id);

        $showResponse->assertStatus(200);
        $showResponse->assertSee('勤怠詳細');
        $showResponse->assertSee('2026年');
        $showResponse->assertSee('10月04日');

        Carbon::setTestNow();
    }
}
