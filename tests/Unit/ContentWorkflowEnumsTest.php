<?php

namespace Tests\Unit;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\RevisionStatus;
use PHPUnit\Framework\TestCase;

class ContentWorkflowEnumsTest extends TestCase
{
    public function test_import_staging_is_not_a_content_status(): void
    {
        $values = array_map(
            static fn (ContentStatus $status): string => $status->value,
            ContentStatus::cases(),
        );

        $this->assertNotContains('staged', $values);
        $this->assertSame(ContentStatus::Draft, ContentStatus::from('draft'));
    }

    public function test_required_vertical_slice_content_types_are_allowlisted(): void
    {
        $this->assertContains(ContentType::Region, ContentType::cases());
        $this->assertContains(ContentType::Location, ContentType::cases());
        $this->assertContains(ContentType::Npc, ContentType::cases());
        $this->assertContains(ContentType::Mission, ContentType::cases());
        $this->assertContains(ContentType::ConversationScenario, ContentType::cases());
    }

    public function test_revision_workflow_includes_rejection_without_staging(): void
    {
        $values = array_map(
            static fn (RevisionStatus $status): string => $status->value,
            RevisionStatus::cases(),
        );

        $this->assertContains('rejected', $values);
        $this->assertNotContains('staged', $values);
    }
}
