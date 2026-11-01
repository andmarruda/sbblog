<?php

namespace App\Enums;

enum ConsentCategory: string
{
    case Analytics = 'analytics';
    case Ads = 'ads';
    case Functional = 'functional';
}
