<?php

namespace App\Enums;

enum PermissionScope: string
{
    case Workspace = 'workspace';
    case Blog = 'blog';
}
