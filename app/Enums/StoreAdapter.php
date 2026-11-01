<?php

namespace App\Enums;

enum StoreAdapter: string
{
    case Sqlite = 'sqlite';
    case Sqlcipher = 'sqlcipher';
    case D1 = 'd1';
    case R2 = 'r2';
    case Custom = 'custom';
}
