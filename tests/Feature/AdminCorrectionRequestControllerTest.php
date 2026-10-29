<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Rest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCorrectionRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_admin_application_list_can_get_approval_waiting(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $userA = User::factory()->create([
            'name' => 'テスト花子',
        ]);

        $userB = User::factory()->create([
            'name' => '山田太郎',
        ]);

        $attendanceUserA = Attendance::factory()->create([
            'user_id' => $userA->id,
            'date' => '2026-10-20',
        ]);

        $attendanceUserB = Attendance::factory()->create([
            'user_id' => $userB->id,
            'date' => '2026-10-10',
        ]);

        AttendanceCorrection::factory()->create([
            'user_id' => $userA->id,
            'attendance_id' => $attendanceUserA->id,
            'approval_status' => '承認待ち',
            'comment' => '入力ミス',
            'created_at' => '2026-10-22',
        ]);

        AttendanceCorrection::factory()->create([
            'user_id' => $userB->id,
            'attendance_id' => $attendanceUserB->id,
            'approval_status' => '承認待ち',
            'comment' => '入力ミス',
            'created_at' => '2026-10-22',
        ]);

        AttendanceCorrection::factory()->create([
            'user_id' => $userA->id,
            'attendance_id' => $attendanceUserA->id,
            'approval_status' => '承認済み',
            'comment' => '打刻忘れ',
            'created_at' => '2026-10-22',
        ]);

        $response = $this->actingAs($admin)
            ->from('/admin/attendance/list')
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);

        $response->assertSee('<input class="application__tab--input" id="tab1" type="radio" name="tab_item" checked>', false);

        $html = $response->getContent();
        $sections = explode('id="content2"', $html);
        $approval_waiting_zone = $sections[0];

        $this->assertStringContainsString('テスト花子', $approval_waiting_zone);
        $this->assertStringContainsString('2026/10/20', $approval_waiting_zone);
        $this->assertStringContainsString('山田太郎', $approval_waiting_zone);
        $this->assertStringContainsString('2026/10/10', $approval_waiting_zone);

        $this->assertStringNotContainsString('打刻忘れ', $approval_waiting_zone);
    }

    /** @test */
    public function test_admin_application_list_can_get_approval_checked(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $userA = User::factory()->create([
            'name' => 'テスト花子',
        ]);

        $userB = User::factory()->create([
            'name' => '山田太郎',
        ]);

        $attendanceUserA = Attendance::factory()->create([
            'user_id' => $userA->id,
            'date' => '2026-10-20',
        ]);

        $attendanceUserB = Attendance::factory()->create([
            'user_id' => $userB->id,
            'date' => '2026-10-10',
        ]);

        AttendanceCorrection::factory()->create([
            'user_id' => $userA->id,
            'attendance_id' => $attendanceUserA->id,
            'approval_status' => '承認済み',
            'comment' => '入力ミス',
            'created_at' => '2026-10-22',
        ]);

        AttendanceCorrection::factory()->create([
            'user_id' => $userB->id,
            'attendance_id' => $attendanceUserB->id,
            'approval_status' => '承認済み',
            'comment' => '入力ミス',
            'created_at' => '2026-10-22',
        ]);

        AttendanceCorrection::factory()->create([
            'user_id' => $userA->id,
            'attendance_id' => $attendanceUserA->id,
            'approval_status' => '承認待ち',
            'comment' => '打刻忘れ',
            'created_at' => '2026-10-22',
        ]);

        $response = $this->actingAs($admin)
            ->from('/admin/attendance/list')
            ->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        $response->assertSee('<input class="application__tab--input" id="tab2" type="radio" name="tab_item">', false);

        $html = $response->getContent();
        $sections = explode('id="content2"', $html);
        $approved_zone = $sections[1] ?? '';

        $this->assertStringContainsString('テスト花子', $approved_zone);
        $this->assertStringContainsString('2026/10/20', $approved_zone);
        $this->assertStringContainsString('入力ミス', $approved_zone);
        $this->assertStringContainsString('山田太郎', $approved_zone);
        $this->assertStringContainsString('2026/10/10', $approved_zone);
        $this->assertStringContainsString('入力ミス', $approved_zone);

        $this->assertStringNotContainsString('打刻忘れ', $approved_zone);

    }

    /** @test */
    public function test_admin_can_get_show_application(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'テスト花子',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-20',
        ]);

        $attendanceCorrection = AttendanceCorrection::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'approval_status' => '承認待ち',
            'comment' => '入力ミス',
            'created_at' => '2026-10-22',
        ]);

        $response = $this->actingAs($admin)->get("/stamp_correction_request/approve/{$attendanceCorrection->id}");
        $response->assertStatus(200);

        $response->assertSeeInOrder([
            'テスト花子',
            '2026年',
            '10月20日',
            '入力ミス',
        ]);
    }

    /** @test */
    public function test_admin_can_application_approve(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'テスト花子',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-20',
            'clock_in' => '10:00',
            'clock_out' => '21:00',
        ]);

        $rest = Rest::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '11:00',
            'break_out' => '12:00',
        ]);

        $attendanceCorrection = AttendanceCorrection::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'approval_status' => '承認待ち',
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'comment' => '入力ミス',
            'created_at' => '2026-10-22',
        ]);

        $response = $this->actingAs($admin)
            ->from("/stamp_correction_request/approve/{$attendanceCorrection->id}")
            ->put("/stamp_correction_request/approve/{$attendanceCorrection->id}");
        $response->assertRedirect("/stamp_correction_request/approve/{$attendanceCorrection->id}");
        $response->assertSessionHas('success', '勤怠修正申請を承認しました');

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'date' => '2026-10-20',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $this->assertDatabaseHas('attendance_corrections', [
            'id' => $attendanceCorrection->id,
            'approval_status' => '承認済み',
            'approved_by' => $admin->id,
        ]);
    }
}
