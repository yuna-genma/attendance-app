<?php

namespace Tests\Feature\Api\V1;

use App\Models\Attendance;
use App\Models\Rest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceRecordControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_can_get_attendance_records(): void
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-01',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        Rest::factory()->create([
            'attendance_id' => $attendance->id,
        ]);

        $response = $this->getJson('/api/v1/attendance-records');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'user_id',
                    'user_name',
                    'date',
                    'clock_in',
                    'clock_out',
                    'total_time',
                    'total_break_time',
                    'comment',
                ],
            ],
            'links',
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'per_page',
                'to',
                'total',
            ],
        ]);

        $response->assertJsonFragment([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'date' => '2026-10-01',
        ]);
    }

    /** @test */
    public function test_can_get_attendance_records_detail(): void
    {

        $user = User::factory()->create(['name' => '山田太郎']);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $rest = Rest::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $correction = $attendance->attendanceCorrections()->create([
            'user_id' => $user->id,
            'new_clock_in' => '09:15:00',
            'new_clock_out' => '18:15:00',
            'approval_status' => '承認待ち',
            'comment' => 'これはテストです',
        ]);

        $response = $this->getJson("/api/v1/attendance-records/{$attendance->id}");
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'id' => $attendance->id,
                'user' => [
                    'id' => $user->id,
                    'name' => '山田太郎',
                ],
                'date' => '2026-10-01',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'breaks' => [
                    [
                        'id' => $rest->id,
                        'break_in' => '12:00:00',
                        'break_out' => '13:00:00',
                    ],
                ],
                'applications' => [
                    [
                        'id' => $correction->id,
                        'new_clock_in' => '09:15:00',
                        'new_clock_out' => '18:15:00',
                    ],
                ],
                'comment' => 'これはテストです',
            ],
        ]);
    }

    /** @test */
    public function test_get_attendance_record_returns_404_if_not_found(): void
    {
        $response = $this->getJson('/api/v1/attendance-records/99999', [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(404);

        $response->assertJson([
            'error' => '勤怠情報が見つかりませんでした。',
        ]);
    }

    /** @test */
    public function test_can_store_attendance_record(): void
    {

        $user = User::factory()->create();

        $attendanceRecord = [
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'これはテストです',
        ];

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/attendance-records', $attendanceRecord);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'date',
                'clock_in',
                'clock_out',
                'comment',
            ],
        ]);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $this->assertDatabaseHas('attendance_corrections', [
            'user_id' => $user->id,
            'comment' => 'これはテストです',
        ]);
    }

    /** @test */
    public function test_store_date_is_required(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = [
            'date' => ' ',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'これはテストです',
        ];

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/attendance-records', $attendanceRecord);

        $response->assertStatus(422);

        $response->assertJson([
            'message' => '勤怠日は必須です。',
            'errors' => [
                'date' => [
                    '勤怠日は必須です。',
                ],
            ],
        ]);
    }

    /** @test */
    public function test_store_clock_in_is_required(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = [
            'date' => '2026-10-01',
            'clock_in' => ' ',
            'clock_out' => '18:00:00',
            'comment' => 'これはテストです',
        ];

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/attendance-records', $attendanceRecord);

        $response->assertStatus(422);

        $response->assertJson([
            'message' => '出勤時刻は必須です。',
            'errors' => [
                'clock_in' => [
                    '出勤時刻は必須です。',
                ],
            ],
        ]);
    }

    /** @test */
    public function test_can_update_attendance_record(): void
    {

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-01',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $updateAttendance = [
            'date' => '2026-10-01',
            'clock_in' => '09:15:00',
            'clock_out' => '18:15:00',
            'comment' => 'これはテストです',
        ];

        Sanctum::actingAs($user, ['*']);
        $response = $this->putJson("/api/v1/attendance-records/{$attendance->id}", $updateAttendance);

        $response->assertStatus(200);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => '2026-10-01',
            'clock_in' => '09:15:00',
            'clock_out' => '18:15:00',
        ]);

        $this->assertDatabaseHas('attendance_corrections', [
            'user_id' => $user->id,
            'comment' => 'これはテストです',
        ]);
    }

    /** @test */
    public function test_update_attendance_record_returns_404_if_not_found(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson('/api/v1/attendance-records/99999', [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(404);

        $response->assertJson([
            'error' => '勤怠情報が見つかりませんでした。',
        ]);
    }

    /** @test */
    public function test_can_delete_attendance_resord(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-01',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        Sanctum::actingAs($user, ['*']);
        $response = $this->deleteJson("/api/v1/attendance-records/{$attendance->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('attendances', [
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function test_destroy_attendance_record_returns_404_if_not_found(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->deleteJson('/api/v1/attendance-records/99999', [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(404);

        $response->assertJson([
            'error' => '勤怠情報が見つかりませんでした。',
        ]);
    }
}
