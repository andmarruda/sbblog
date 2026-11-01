<?php

namespace App\Enums;

enum CommentPolicy: string
{
    case Disabled = 'disabled';
    case Moderated = 'moderated';
    case Open = 'open';
}
