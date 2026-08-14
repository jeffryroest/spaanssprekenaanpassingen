<?php

namespace Tests\Unit;

use App\Enums\ContentPermission;
use App\Enums\ContentRole;
use PHPUnit\Framework\TestCase;

class ContentRoleTest extends TestCase
{
    public function test_administrator_has_every_permission(): void
    {
        foreach (ContentPermission::cases() as $permission) {
            $this->assertTrue(ContentRole::Administrator->allows($permission));
        }
    }

    public function test_auditor_can_only_view_content(): void
    {
        foreach (ContentPermission::cases() as $permission) {
            $this->assertSame(
                $permission === ContentPermission::View,
                ContentRole::Auditor->allows($permission),
            );
        }
    }
}
