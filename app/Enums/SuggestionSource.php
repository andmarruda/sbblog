<?php

namespace App\Enums;

enum SuggestionSource: string
{
    case Human = 'human';
    case App = 'app';
    case Ai = 'ai';
}
