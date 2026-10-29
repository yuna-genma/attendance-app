<?php

namespace Tests\Feature\Api\V1;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SanctumAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_post_should_return_404_when_unauthenticated(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = [
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'これはテストです',
        ];

        $response = $this->postJson('/api/v1/attendance-records', $attendanceRecord);

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    /** @test */
    public function test_put_should_return_404_when_unauthenticated(): void
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

        $response = $this->putJson("/api/v1/attendance-records/{$attendance->id}", $updateAttendance);

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    /** @test */
    public function test_delete_should_return_404_when_unauthenticated(): void
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

        $response = $this->deleteJson("/api/v1/attendance-records/{$attendance->id}");

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    /** @test */
    public function test_put_should_return_200_when_authenticated(): void
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

        Sanctum::actingAs($user);
        $response = $this->putJson("/api/v1/attendance-records/{$attendance->id}", $updateAttendance);

        $response->assertStatus(200);
    }

    /** @test */
    public function test_delete_should_return_204_when_authenticated(): void
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

        Sanctum::actingAs($user);
        $response = $this->deleteJson("/api/v1/attendance-records/{$attendance->id}");

        $response->assertStatus(204);
    }

    /** @test */
    public function test_should_return_403_when_another_user_attendance_updated(): void
    {

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $anotherUser = User::factory()->create([
            'admin_status' => false,
        ]);

        $anotherUserAttendance = Attendance::factory()->create([
            'user_id' => $anotherUser->id,
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

        Sanctum::actingAs($user);
        $response = $this->putJson("/api/v1/attendance-records/{$anotherUserAttendance->id}", $updateAttendance);

        $response->assertStatus(403);
        $response->assertJson([
            'error' => 'この操作を実行する権限がありません。',
        ]);
    }

    /** @test */
    public function test_should_return_403_when_another_user_attendance_deleted(): void
    {

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $anotherUser = User::factory()->create([
            'admin_status' => false,
        ]);

        $anotherUserAttendance = Attendance::factory()->create([
            'user_id' => $anotherUser->id,
            'date' => '2026-10-01',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        Sanctum::actingAs($user);
        $response = $this->deleteJson("/api/v1/attendance-records/{$anotherUserAttendance->id}");

        $response->assertStatus(403);
        $response->assertJson([
            'error' => 'この操作を実行する権限がありません。',
        ]);
    }
}
