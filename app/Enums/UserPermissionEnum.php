<?php

namespace App\Enums;

enum UserPermissionEnum: string
{
    case READ = 'read';
    case WRITE = 'write';
}
