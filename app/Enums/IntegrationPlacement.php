<?php

namespace App\Enums;

enum IntegrationPlacement: string
{
    case Head = 'head';
    case BodyStart = 'body_start';
    case BodyEnd = 'body_end';
}
