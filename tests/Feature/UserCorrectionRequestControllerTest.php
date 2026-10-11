<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use Illuminate\Support\Facades\DB;

class UserCorrectionRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_user_detail_can_get_user_name(): void
    {
        $otherUser = User::factory()->create();
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id
        ]);

        $this->actingAs($user);
        $response = $this->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee('value="' . $user->name . '"', false);
        $response->assertDontSee('value="' . $otherUser->name . '"', false);
    }

    /** @test */
    public function test_user_detail_can_get_date(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-04',
        ]);

        $this->actingAs($user);
        $response = $this->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee('value="2026年"', false);
        $response->assertSee('value="10月04日"', false);
    }

    /** @test */
    public function test_user_detail_can_get_stamping_time(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-04',
            'clock_in' => '08:00:00',
            'clock_out' => '18:00:00',
        ]);

        $this->actingAs($user);
        $response = $this->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            'value="08:00"',
            'value="18:00"',
        ], false);
    }

    /** @test */
    public function test_user_detail_can_get_break_time(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-04',
            'clock_in' => '08:00:00',
            'clock_out' => '18:00:00',
        ]);

        Rest::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $this->actingAs($user);
        $response = $this->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            'value="12:00"',
            'value="13:00"',
        ], false);
    }

    /** @test */
    public function test_clock_in_time_cannot_be_after_clock_out_time(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-04',
            'clock_in' => '08:00:00',
            'clock_out' => '18:00:00',
        ]);

        $this->actingAs($user);
        $showResponse = $this->get('/attendance/detail/' . $attendance->id);
        $showResponse->assertStatus(200);

        $updateResponse = $this->post("/attendance/{$attendance->id}", [
            'new_clock_in' => '21:00',
            'new_clock_out' => '08:00',
            'comment' => '更新失敗',
        ]);

        $updateResponse->assertStatus(302);
        $updateResponse->assertRedirect('/attendance/detail/' . $attendance->id);
        $updateResponse->assertSessionHasErrors([
            'new_clock_in' => '出勤時間が不適切な値です'
        ]);
    }

    /** @test */
    public function test_break_in_time_cannot_be_after_clock_out_time(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-04',
            'clock_in' => '08:00:00',
            'clock_out' => '18:00:00',
        ]);

        Rest::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '13:00:00',
            'break_out' => '14:00:00',
        ]);

        $this->actingAs($user);
        $showResponse = $this->get('/attendance/detail/' . $attendance->id);
        $showResponse->assertStatus(200);

        $updateResponse = $this->post("/attendance/{$attendance->id}", [
            'new_clock_in' => '08:00',
            'new_clock_out' => '18:00',
            'new_break_in' => ['20:00'],
            'new_break_out' => ['21:00'],
            'comment' => '更新失敗',
        ]);

        $updateResponse->assertStatus(302);
        $updateResponse->assertRedirect('/attendance/detail/' . $attendance->id);
        $updateResponse->assertSessionHasErrors([
            'new_break_in.0' => '休憩時間が不適切な値です'
        ]);
    }

    /** @test */
    public function test_break_out_time_cannot_be_after_clock_out_time(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-04',
            'clock_in' => '08:00:00',
            'clock_out' => '18:00:00',
        ]);

        Rest::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '13:00:00',
            'break_out' => '14:00:00',
        ]);

        $this->actingAs($user);
        $showResponse = $this->get('/attendance/detail/' . $attendance->id);
        $showResponse->assertStatus(200);

        $updateResponse = $this->post("/attendance/{$attendance->id}", [
            'new_clock_in' => '08:00',
            'new_clock_out' => '18:00',
            'new_break_in' => ['17:00'],
            'new_break_out' => ['21:00'],
            'comment' => '更新失敗',
        ]);

        $updateResponse->assertStatus(302);
        $updateResponse->assertRedirect('/attendance/detail/' . $attendance->id);
        $updateResponse->assertSessionHasErrors([
            'new_break_out.0' => '休憩時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /** @test */
    public function test_comment_must_be_required(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-04',
            'clock_in' => '08:00:00',
            'clock_out' => '18:00:00',
        ]);

        Rest::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '13:00:00',
            'break_out' => '14:00:00',
        ]);

        $this->actingAs($user);
        $showResponse = $this->get('/attendance/detail/' . $attendance->id);
        $showResponse->assertStatus(200);

        $updateResponse = $this->post("/attendance/{$attendance->id}", [
            'new_clock_in' => '08:00',
            'new_clock_out' => '18:00',
            'new_break_in' => ['12:00'],
            'new_break_out' => ['13:00'],
            'comment' => ' ',
        ]);

        $updateResponse->assertStatus(302);
        $updateResponse->assertRedirect('/attendance/detail/' . $attendance->id);
        $updateResponse->assertSessionHasErrors([
            'comment' => '備考を記入してください'
        ]);
    }

    /** @test */
    public function test_user_correction_request_success(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-04',
            'clock_in' => '08:00:00',
            'clock_out' => '18:00:00',
        ]);

        Rest::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '13:00:00',
            'break_out' => '14:00:00',
        ]);

        $this->actingAs($user);
        $showResponse = $this->get('/attendance/detail/' . $attendance->id);
        $showResponse->assertStatus(200);

        $updateResponse = $this->post("/attendance/{$attendance->id}", [
            'new_clock_in' => '09:00',
            'new_clock_out' => '19:00',
            'new_break_in' => ['12:00'],
            'new_break_out' => ['13:00'],
            'comment' => '修正成功',
        ]);

        $updateResponse->assertStatus(302);
        $this->assertDatabaseHas('attendance_corrections', [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '19:00:00',
        ]);
        $this->assertDatabaseHas('rest_corrections', [
            'new_break_in' => '12:00:00',
            'new_break_out' => '13:00:00',
        ]);

        $correction = DB::table('attendance_corrections')
            ->where('attendance_id', $attendance->id)
            ->first();

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $adminListResponse = $this->actingAs($admin)->get('/stamp_correction_request/list', [
            'HTTP_REFERER' => 'http://localhost/admin/dashboard'
        ]);
        $adminListResponse->assertStatus(200);
        $adminListResponse->assertSee($user->name);
        $adminListResponse->assertSee('修正成功');

        $adminApproveResponse = $this->get("/stamp_correction_request/approve/{$correction->id}");
        $adminApproveResponse->assertStatus(200);
        $adminApproveResponse->assertSee('09:00');
    }

    /** @test */
    public function test_pending_approval_can_get_stamp_correction_request_list(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-04',
            'clock_in' => '08:00:00',
            'clock_out' => '18:00:00',
        ]);

        Rest::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '13:00:00',
            'break_out' => '14:00:00',
        ]);

        $this->actingAs($user);

        $updateResponse = $this->post("/attendance/{$attendance->id}", [
            'new_clock_in' => '09:00',
            'new_clock_out' => '19:00',
            'new_break_in' => ['12:00'],
            'new_break_out' => ['13:00'],
            'comment' => '修正成功',
        ]);

        $updateResponse->assertStatus(302);
        $this->assertDatabaseHas('attendance_corrections', [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '19:00:00',
        ]);
        $this->assertDatabaseHas('rest_corrections', [
            'new_break_in' => '12:00:00',
            'new_break_out' => '13:00:00',
        ]);

        $userListResponse = $this->get('/stamp_correction_request/list');
        $userListResponse->assertStatus(200);
        $userListResponse->assertSeeInOrder([
            '承認待ち',
            $user->name,
            '修正成功'
        ]);
    }

    /** @test */
    public function test_approved_can_get_stamp_correction_request_list(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-04',
            'clock_in' => '08:00:00',
            'clock_out' => '18:00:00',
        ]);

        Rest::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '13:00:00',
            'break_out' => '14:00:00',
        ]);

        $this->actingAs($user);

        $updateResponse = $this->post("/attendance/{$attendance->id}", [
            'new_clock_in' => '09:00',
            'new_clock_out' => '19:00',
            'new_break_in' => ['12:00'],
            'new_break_out' => ['13:00'],
            'comment' => '修正成功',
        ]);

        $updateResponse->assertStatus(302);
        $this->assertDatabaseHas('attendance_corrections', [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '19:00:00',
        ]);
        $this->assertDatabaseHas('rest_corrections', [
            'new_break_in' => '12:00:00',
            'new_break_out' => '13:00:00',
        ]);

        DB::table('attendance_corrections')
            ->where('attendance_id', $attendance->id)
            ->update([
                'approval_status' => '承認済み'
            ]);

        $userListResponse = $this->get('/stamp_correction_request/list');
        $userListResponse->assertStatus(200);
        $userListResponse->assertSeeInOrder([
            '承認済み',
            $user->name,
            '修正成功'
        ]);
    }

    /** @test */
    public function test_stamp_correction_request_list_can_redirect_for_user_detail(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-04',
            'clock_in' => '08:00:00',
            'clock_out' => '18:00:00',
        ]);

        Rest::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '13:00:00',
            'break_out' => '14:00:00',
        ]);

        $this->actingAs($user);

        $updateResponse = $this->post("/attendance/{$attendance->id}", [
            'new_clock_in' => '09:00',
            'new_clock_out' => '19:00',
            'new_break_in' => ['12:00'],
            'new_break_out' => ['13:00'],
            'comment' => '修正成功',
        ]);

        $updateResponse->assertStatus(302);
        $this->assertDatabaseHas('attendance_corrections', [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '19:00:00',
        ]);
        $this->assertDatabaseHas('rest_corrections', [
            'new_break_in' => '12:00:00',
            'new_break_out' => '13:00:00',
        ]);

        $correction = DB::table('attendance_corrections')
            ->where('attendance_id', $attendance->id)
            ->first();

        $userListResponse = $this->get('/stamp_correction_request/list');
        $userListResponse->assertStatus(200);

        $userShowResponse = $this->get("/application/{$correction->id}");
        $userShowResponse->assertStatus(302);

        $expectedRedirectUrl = '/attendance/detail/' . $correction->attendance_id . '?status=承認待ち';
        $userShowResponse->assertRedirect($expectedRedirectUrl);

        $redirectResponse = $this->get($expectedRedirectUrl);
        $redirectResponse->assertStatus(200);
        $redirectResponse->assertViewIs('user.user-detail');
        $redirectResponse->assertSee($user->name);
        $redirectResponse->assertSee('修正成功');
    }
}

