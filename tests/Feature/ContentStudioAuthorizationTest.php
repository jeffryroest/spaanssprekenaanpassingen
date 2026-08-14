<?php

namespace Tests\Feature;

use App\Actions\ContentStudio\AssignContentRole;
use App\Enums\ContentRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ContentStudioAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('content-studio.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_without_content_role_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('content-studio.dashboard'))
            ->assertForbidden();
    }

    public function test_every_defined_content_role_can_view_the_dashboard(): void
    {
        foreach (ContentRole::cases() as $role) {
            $user = User::factory()->create()->forceFill(['content_role' => $role]);
            $user->save();

            $this->actingAs($user)
                ->get(route('content-studio.dashboard'))
                ->assertOk()
                ->assertSee($role->label());
        }
    }

    public function test_only_administrator_and_editor_in_chief_can_publish(): void
    {
        foreach (ContentRole::cases() as $role) {
            $user = User::factory()->create()->forceFill(['content_role' => $role]);
            $user->save();

            $expected = in_array($role, [
                ContentRole::Administrator,
                ContentRole::EditorInChief,
            ], true);

            $this->assertSame(
                $expected,
                Gate::forUser($user)->allows('content-studio.publish'),
                "Onjuiste publicatiebevoegdheid voor {$role->value}.",
            );
        }
    }

    public function test_role_assignment_is_audited(): void
    {
        $actor = User::factory()->create()->forceFill([
            'content_role' => ContentRole::Administrator,
        ]);
        $actor->save();

        $user = User::factory()->create();

        app(AssignContentRole::class)->handle($user, ContentRole::Editor, $actor);

        $this->assertDatabaseHas('content_role_audits', [
            'user_id' => $user->getKey(),
            'actor_id' => $actor->getKey(),
            'from_role' => null,
            'to_role' => ContentRole::Editor->value,
        ]);
    }
}
