<?php

namespace App\Enums;

enum ContentPermission: string
{
    case View = 'view';
    case Edit = 'edit';
    case Import = 'import';
    case Review = 'review';
    case Approve = 'approve';
    case Publish = 'publish';
    case ManageRoles = 'manage_roles';
}
