<?php
namespace App\Enums;

enum CorrectionStatus: string
{
    case PENDING = '承認待ち';
    case APPROVED = '承認済み';
}