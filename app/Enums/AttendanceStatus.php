<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case BEFORE_WORK = '勤務外';
    case WORKING = '出勤中';
    case BREAKING = '休憩中';
    case LEFT = '退勤済';
}