<?php

namespace App\Enums;

enum MediaDisk: string
{
    case Local = 'local';
    case S3 = 's3';
    case R2 = 'r2';
}
