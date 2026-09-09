<?php
namespace App\Enums;

enum CorrectionStatus: string
{
    case PENDING = '申請中';
    case APPROVED = '承認済';
}