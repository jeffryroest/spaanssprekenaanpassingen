<?php

namespace App\Enums;

enum ContentRole: string
{
    case Administrator = 'beheerder';
    case EditorInChief = 'hoofdredacteur';
    case Editor = 'redacteur';
    case LanguageReviewer = 'taalreviewer';
    case ImportManager = 'importbeheerder';
    case Auditor = 'auditor';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Beheerder',
            self::EditorInChief => 'Hoofdredacteur',
            self::Editor => 'Redacteur',
            self::LanguageReviewer => 'Taalreviewer',
            self::ImportManager => 'Importbeheerder',
            self::Auditor => 'Auditor',
        };
    }

    public function allows(ContentPermission $permission): bool
    {
        return match ($this) {
            self::Administrator => true,
            self::EditorInChief => $permission !== ContentPermission::ManageRoles,
            self::Editor => in_array($permission, [
                ContentPermission::View,
                ContentPermission::Edit,
            ], true),
            self::LanguageReviewer => in_array($permission, [
                ContentPermission::View,
                ContentPermission::Review,
                ContentPermission::Approve,
            ], true),
            self::ImportManager => in_array($permission, [
                ContentPermission::View,
                ContentPermission::Import,
            ], true),
            self::Auditor => $permission === ContentPermission::View,
        };
    }
}
