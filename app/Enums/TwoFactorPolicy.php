<?php

namespace App\Enums;

enum TwoFactorPolicy: string
{
    case Off = 'off';
    case Optional = 'optional';
    case RequiredByRole = 'required_by_role';
    case RequiredAll = 'required_all';
}
