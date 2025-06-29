<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserPermissionEnum: string implements HasLabel
{
    // User management permissions
    case VIEW_USERS = 'view_users';
    case CREATE_USERS = 'create_users';
    case EDIT_USERS = 'edit_users';
    case DELETE_USERS = 'delete_users';

    // Manager management permissions
    case VIEW_MANAGERS = 'view_managers';
    case CREATE_MANAGERS = 'create_managers';
    case EDIT_MANAGERS = 'edit_managers';
    case DELETE_MANAGERS = 'delete_managers';

    // Role management permissions
    case VIEW_ROLES = 'view_roles';
    case CREATE_ROLES = 'create_roles';
    case EDIT_ROLES = 'edit_roles';
    case DELETE_ROLES = 'delete_roles';

    // General admin access
    case ADMIN_PANEL_ACCESS = 'admin_panel_access';

    // Special permissions
    case ASSIGN_ROLES = 'assign_roles';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ADMIN_PANEL_ACCESS => 'Admin Panel Access',
            self::VIEW_USERS => 'View Users',
            self::CREATE_USERS => 'Create Users',
            self::EDIT_USERS => 'Edit Users',
            self::DELETE_USERS => 'Delete Users',
            self::VIEW_MANAGERS => 'View Managers',
            self::CREATE_MANAGERS => 'Create Managers',
            self::EDIT_MANAGERS => 'Edit Managers',
            self::DELETE_MANAGERS => 'Delete Managers',
            self::VIEW_ROLES => 'View Roles',
            self::CREATE_ROLES => 'Create Roles',
            self::EDIT_ROLES => 'Edit Roles',
            self::DELETE_ROLES => 'Delete Roles',
            self::ASSIGN_ROLES => 'Assign Roles',
        };
    }
}
