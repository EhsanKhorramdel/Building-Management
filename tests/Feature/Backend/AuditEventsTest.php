<?php

use App\Models\AuditEvent;
use App\Models\Complex;
use App\Models\MembershipRequest;
use App\Models\User;
use App\Models\UserRole;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\Support\BuildingContext;

dataset('audited actions', ['role.upgraded', 'role.downgraded', 'membership.approved', 'membership.rejected', 'join_link.rotated']);

function performAuditedAction(string $event): array
{
    $test = test();
    $test->from(route('dashboard'));

    if (str_starts_with($event, 'membership.')) {
        $admin = UserRole::factory()->admin()->create()->user;
        $request = MembershipRequest::factory()->create();
        $approve = $event === 'membership.approved';
        $route = route($approve ? 'adminPanel.accept' : 'adminPanel.reject', $request);
        $test->actingAs($admin)->post($route)->assertRedirect();

        expect($request->fresh()->request_status)->toBe($approve ? 'accepted' : 'rejected');
        $complex = Complex::where('name', $request->name)->first();
        expect($complex !== null)->toBe($approve);

        return [$admin, $complex?->id, $request->user, ['complex_name' => $request->name], []];
    }

    $building = new BuildingContext;
    $test->actingAs($building->manager);

    if ($event === 'join_link.rotated') {
        $oldLink = $building->complex->join_link;
        $test->post(route('building.regenerateJoinLink', $building->complex))->assertRedirect()->assertSessionHas('status', 'success');
        $link = $building->complex->fresh()->join_link;
        expect($link)->not->toBe($oldLink);

        return [$building->manager, $building->complex->id, $building->complex, null, [$oldLink, $link]];
    }

    $upgrade = $event === 'role.upgraded';
    $otherRole = $building->resident();
    if ($upgrade) {
        $test->post(route('building.upgradeRole', $otherRole))->assertRedirect()->assertSessionHas('status', 'success');
        $subjectRole = $otherRole;
    } else {
        $otherRole->update(['role' => 'manager']);
        $test->post(route('building.downgradeRole', $building->complex))->assertRedirect(route('login'));
        $subjectRole = $building->managerRole;
    }

    $from = $upgrade ? 'resident' : 'manager';
    $to = $upgrade ? 'manager' : 'resident';
    expect($subjectRole->fresh()->role)->toBe($to);

    return [$building->manager, $building->complex->id, $subjectRole->user, compact('from', 'to'), []];
}

it('records exactly one scoped event for the completed action', function (string $event) {
    [$actor, $complexId, $subject, $meta, $links] = performAuditedAction($event);

    $entry = AuditEvent::sole();
    expect($entry->event)->toBe($event)
        ->and($entry->actor_id)->toBe($actor->id)
        ->and($entry->complex_id)->toBe($complexId)
        ->and($entry->subject_type)->toBe($subject->getMorphClass())
        ->and($entry->subject_id)->toBe($subject->id)
        ->and($entry->meta)->toBe($meta)
        ->and($entry->created_at)->not->toBeNull();

    if ($links) {
        $row = json_encode(DB::table('audit_events')->sole(), JSON_THROW_ON_ERROR);
        foreach ($links as $link) {
            expect($row)->not->toContain($link);
        }
    }
})->with('audited actions');

it('keeps the main action successful when the audit writer throws', function (string $event) {
    $this->app->instance(AuditLogger::class, new class extends AuditLogger
    {
        protected function write(array $attributes): void
        {
            throw new RuntimeException('Audit storage unavailable');
        }
    });
    Log::spy();

    performAuditedAction($event);

    $this->assertDatabaseEmpty('audit_events');
    Log::shouldHaveReceived('warning')->once()->withArgs(fn ($message, $context) => $context['event'] === $event);
})->with('audited actions');

it('records no extra audit event on repeated membership decisions', function () {
    $admin = UserRole::factory()->admin()->create()->user;
    $approved = MembershipRequest::factory()->create();
    $rejected = MembershipRequest::factory()->create();

    $this->actingAs($admin);
    foreach ([$approved, $approved, $rejected, $rejected] as $request) {
        $route = $request->is($approved) ? 'adminPanel.accept' : 'adminPanel.reject';
        $this->post(route($route, $request))->assertRedirect();
    }

    expect(AuditEvent::orderBy('id')->pluck('event')->all())->toBe(['membership.approved', 'membership.rejected']);
});

it('shows the latest one hundred events globally to admins only', function () {
    $admin = UserRole::factory()->admin()->create()->user;
    $building = new BuildingContext;
    $foreign = new BuildingContext;
    $oldActor = User::factory()->create(['name' => 'Old Audit Actor']);
    $audit = app(AuditLogger::class);
    $audit->record('join_link.rotated', $oldActor->id, $building->complex->id, Complex::class, $building->complex->id);
    for ($i = 0; $i < 98; $i++) {
        $audit->record('join_link.rotated', $building->manager->id, $building->complex->id, Complex::class, $building->complex->id);
    }
    $audit->record('join_link.rotated', $foreign->manager->id, $foreign->complex->id, Complex::class, $foreign->complex->id);
    $audit->record('membership.rejected', $admin->id, null, User::class, $oldActor->id);

    $response = $this->actingAs($admin)->get(route('adminPanel.index'))->assertOk()
        ->assertSee('data-audit-events', false)
        ->assertSee('رد درخواست عضویت')
        ->assertSee('بروزرسانی لینک عضویت')
        ->assertSee($foreign->complex->name)
        ->assertSee($building->complex->name)
        ->assertSee($foreign->manager->name)
        ->assertSeeInOrder(['data-audit-event="membership.rejected"', 'data-audit-event="join_link.rotated"'], false)
        ->assertDontSee($oldActor->name);
    expect(substr_count($response->getContent(), 'data-audit-event="'))->toBe(100);

    $this->actingAs($building->manager)->get(route('adminPanel.index'))->assertForbidden();
    $this->actingAs($building->resident()->user)->get(route('adminPanel.index'))->assertForbidden();
});

it('shows only the local complex events to its manager', function () {
    $building = new BuildingContext;
    $foreign = new BuildingContext;
    $resident = $building->resident();
    $audit = app(AuditLogger::class);
    $audit->record('join_link.rotated', $building->manager->id, $building->complex->id, Complex::class, $building->complex->id);
    $audit->record('join_link.rotated', $foreign->manager->id, $foreign->complex->id, Complex::class, $foreign->complex->id);
    $url = route('building', $building->complex);

    $response = $this->actingAs($building->manager)->get($url)->assertOk()
        ->assertSee('data-audit-events', false)->assertSee('بروزرسانی لینک عضویت')->assertDontSee($foreign->manager->name);
    expect(substr_count($response->getContent(), 'data-audit-event="'))->toBe(1);

    $this->actingAs($foreign->manager)->get($url)->assertForbidden();
    $this->actingAs($resident->user)->get($url)->assertOk()->assertDontSee('data-audit-events', false);
    UserRole::factory()->for($building->complex)->for($foreign->manager)->create();
    $this->actingAs($foreign->manager)->get($url)->assertOk()->assertDontSee('data-audit-events', false);
});
