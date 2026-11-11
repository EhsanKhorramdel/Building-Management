<?php

use App\Jobs\SendSmsJob;
use App\Models\AuditEvent;
use App\Models\Complex;
use App\Models\IncidentalCost;
use App\Models\MonthlyCharge;
use App\Models\Poll;
use App\Models\User;
use App\Models\UserRole;
use App\Services\AccountNotifications;
use App\Services\AuditLogger;
use App\Services\JoinLinks;
use App\Services\PollService;
use App\Services\SmsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Morilog\Jalali\Jalalian;
use Tests\Support\BuildingContext;

it('crops avatars to a square on the private disk and replaces and removes their files', function () {
    Storage::fake('local');
    $building = new BuildingContext;
    $user = $building->resident()->user;
    $this->actingAs($user)->from(route('dashboard'))->post(route('profile.avatar.update'), [
        'avatar' => UploadedFile::fake()->image('avatar.jpg', 320, 170),
    ])->assertRedirect()->assertSessionHasNoErrors();

    $path = $user->fresh()->avatar_path;
    $bytes = Storage::disk('local')->get($path);
    expect(getimagesizefromstring($bytes)[0])->toBe(192)->and(getimagesizefromstring($bytes)[1])->toBe(192);
    Storage::disk('public')->assertMissing($path);

    $this->post(route('profile.avatar.update'), [
        'avatar' => UploadedFile::fake()->image('replacement.png', 170, 320),
    ])->assertRedirect()->assertSessionHasNoErrors();
    $replacement = $user->fresh()->avatar_path;
    expect($replacement)->not->toBe($path);
    Storage::disk('local')->assertMissing($path)->assertExists($replacement);

    $this->get(route('building', $building->complex))->assertSee($user->fresh()->avatarUrl());
    $this->delete(route('profile.avatar.destroy'))->assertRedirect();
    Storage::disk('local')->assertMissing($replacement);
    expect($user->fresh()->avatarUrl())->toBeNull();
    $this->get(route('building', $building->complex))->assertDontSee('/users/'.$user->id.'/avatar');
});

it('limits avatar bytes to self, active shared members and global admins', function () {
    Storage::fake('local');
    $building = new BuildingContext;
    $target = $building->resident()->user;
    $peer = $building->resident()->user;
    $inactive = $building->resident();
    $inactive->update(['active' => false]);
    $foreign = (new BuildingContext)->manager;
    $admin = UserRole::factory()->admin()->create()->user;
    $target->forceFill(['avatar_path' => 'avatars/sample.png'])->save();
    Storage::disk('local')->put('avatars/sample.png', 'private PNG bytes');
    $url = route('profile.avatar.show', $target);

    $this->get($url)->assertRedirect(route('login'))->assertDontSee('private PNG bytes');
    foreach ([$foreign, $inactive->user, User::factory()->create()] as $denied) {
        $this->actingAs($denied)->get($url)->assertForbidden()->assertDontSee('private PNG bytes');
    }
    foreach ([$target, $peer, $building->manager, $admin] as $allowed) {
        $response = $this->actingAs($allowed)->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
        expect($response->streamedContent())->toBe('private PNG bytes');
    }
});

it('defaults all channels on and stores a separate choice for every event and channel', function () {
    $user = User::factory()->create();
    $input = [];
    foreach (array_keys(AccountNotifications::LABELS) as $event) {
        expect(AccountNotifications::allows($user, $event, 'in_app'))->toBeTrue()
            ->and(AccountNotifications::allows($user, $event, 'sms'))->toBeTrue();
        $input[$event] = ['in_app' => '0', 'sms' => '1'];
    }
    $this->actingAs($user)->from(route('dashboard'))->patch(route('profile.notifications.update'), $input)->assertRedirect();
    foreach (array_keys($input) as $event) {
        expect(AccountNotifications::allows($user->fresh(), $event, 'in_app'))->toBeFalse()
            ->and(AccountNotifications::allows($user->fresh(), $event, 'sms'))->toBeTrue();
    }
    $this->get(route('profile.notifications.index'))->assertOk()->assertSee('اعلانی ثبت نشده است.');
});

it('records notices by user choice and respects the SMS global flag even with defaults on', function () {
    Bus::fake();
    config(['sms.enabled' => false, 'sms.driver' => 'log']);
    $building = new BuildingContext;
    $allowed = $building->resident()->user;
    $allowed->update(['phone_number' => '09120001111']);
    $muted = $building->resident()->user;
    $muted->forceFill(['notification_preferences' => ['poll_opened' => ['in_app' => false, 'sms' => false]]])->save();
    $muted->update(['phone_number' => '09120002222']);

    app(PollService::class)->create($building->complex->id, ['question' => 'New poll', 'option1' => 'A', 'option2' => 'B', 'endHours' => 1]);
    expect($allowed->notifications()->sole()->data['detail'])->toBe('New poll');
    expect($muted->notifications()->count())->toBe(0);
    Bus::assertNotDispatched(SendSmsJob::class);
    $job = new SendSmsJob('poll_opened', Poll::sole()->id);
    $job->handle(app(SmsService::class));
    $this->assertDatabaseEmpty('sms_logs');

    config(['sms.enabled' => true]);
    $job->handle(app(SmsService::class));
    $this->assertDatabaseHas('sms_logs', ['recipient' => $allowed->phone_number, 'template' => 'poll_opened']);
    $this->assertDatabaseMissing('sms_logs', ['recipient' => $muted->phone_number]);
    $this->actingAs($allowed)->get(route('profile.notifications.index'))->assertSee('New poll');
    $this->actingAs($muted)->get(route('profile.notifications.index'))->assertDontSee('New poll');
});

it('remembers preferences by account and complex and falls back after a role change', function () {
    $first = new BuildingContext;
    $second = new BuildingContext;
    $other = $first->resident();
    $secondRole = UserRole::factory()->create(['complex_id' => $second->complex->id, 'user_id' => $first->manager->id]);
    $url = fn ($complex) => route('building.preferences', $complex);
    $this->actingAs($first->manager)->patchJson($url($first->complex), ['panel' => 'setting', 'layout' => 'listView'])->assertNoContent();
    $this->patchJson($url($second->complex), ['panel' => 'poll'])->assertNoContent();
    $this->patchJson($url($second->complex), ['panel' => 'setting'])->assertUnprocessable();

    $this->get(route('building', $first->complex))->assertOk()->assertSee('"initialPanel":"setting"', false)->assertSee('"initialLayout":"listView"', false);
    $this->get(route('building', $second->complex))->assertOk()->assertSee('"initialPanel":"poll"', false)->assertSee('"initialLayout":"gridView"', false);
    $this->actingAs($other->user)->get(route('building', $first->complex))->assertSee('"initialPanel":"home"', false)->assertSee('"initialLayout":"gridView"', false);
    $this->patchJson($url($second->complex), ['panel' => 'poll'])->assertForbidden();

    $first->managerRole->update(['role' => 'resident']);
    $this->actingAs($secondRole->user)->get(route('building', $first->complex))->assertSee('"initialPanel":"home"', false)->assertSee('"initialLayout":"listView"', false);
});

it('shows role subjects locally and filtered membership detail only to global admins, with redacted links', function () {
    $building = new BuildingContext;
    $foreign = new BuildingContext;
    $resident = $building->resident()->user;
    $admin = UserRole::factory()->admin()->create()->user;
    $audit = app(AuditLogger::class);
    $audit->record('role.upgraded', $building->manager->id, $building->complex->id, User::class, $resident->id, ['from' => 'resident', 'to' => 'manager']);
    $audit->record('role.upgraded', $foreign->manager->id, $foreign->complex->id, User::class, $foreign->manager->id);
    $audit->record('membership.approved', $admin->id, $building->complex->id, User::class, $resident->id, ['complex_name' => 'Original submitted building name']);
    $oldLink = $building->complex->join_link;
    app(JoinLinks::class)->rotate($building->complex, $building->manager, 'single', null);
    $newLink = $building->complex->fresh()->join_link;
    $audit->record('join_link.rotated', $building->manager->id, $building->complex->id, Complex::class, $building->complex->id, [
        'from' => 'single', 'to' => 'expiring', 'previous' => $oldLink, 'next' => $newLink,
    ]);
    expect(AuditEvent::latest('id')->first()->meta)->toBe(['from' => 'single', 'to' => 'expiring']);

    $building->complex->update(['name' => '<script>buildingName()</script>']);
    $managerUrl = route('building.audit', $building->complex);
    $adminUrl = route('adminPanel.audit');
    $this->actingAs($building->manager)->get($managerUrl)->assertOk()
        ->assertSee($resident->name)->assertSee('ساکن ← مدیر')->assertSee('لینک دعوت تعویض شد')
        ->assertSee('قابل استفاده تا تعویض ← یک‌بار مصرف')
        ->assertDontSee('Original submitted building name')->assertDontSee($foreign->manager->name)
        ->assertDontSee('<script>buildingName()</script>', false)
        ->assertDontSee($oldLink)->assertDontSee($newLink);
    $this->get($adminUrl)->assertForbidden();
    $this->actingAs($resident)->get($managerUrl)->assertForbidden();
    $this->actingAs($foreign->manager)->get($managerUrl)->assertForbidden();
    $this->actingAs($admin)->get($adminUrl.'?complex='.$building->complex->id.'&event=membership.approved')->assertOk()
        ->assertSee('Original submitted building name')->assertSee($resident->name)
        ->assertDontSee('data-audit-event="role.upgraded"', false)->assertDontSee($oldLink)->assertDontSee($newLink);

    for ($index = 0; $index < 100; $index++) {
        $audit->record('join_link.rotated', $admin->id, $building->complex->id, Complex::class, $building->complex->id);
    }
    $this->get($adminUrl.'?page=2')->assertOk()->assertSee('Original submitted building name')
        ->assertDontSee($oldLink)->assertDontSee($newLink);
});

it('rejects a spent single-use link and records expiry and redemption without link values', function () {
    $building = new BuildingContext;
    $first = User::factory()->create();
    $second = User::factory()->create();
    $complex = $building->complex;
    $issue = route('building.regenerateJoinLink', $complex);
    $join = route('dashboard.store');
    $settings = route('building', [$complex, 'tab' => 'setting']);
    $this->actingAs($building->manager)->from($settings)->post($issue, ['lifecycle' => 'single'])->assertSessionHas('status', 'success');
    $link = $complex->fresh()->join_link;
    $this->get($settings)->assertSee('تعداد استفاده باقی‌مانده: 1');
    $this->actingAs($first)->post($join, ['link' => $link])->assertSessionHas('status', 'success');
    $this->actingAs($second)->post($join, ['link' => $link])->assertSessionHas('message', 'این لینک دعوت قبلاً استفاده شده است.');
    $this->actingAs($building->manager)->get($settings)->assertSee('تعداد استفاده باقی‌مانده: 0');

    $this->post($issue, ['lifecycle' => 'expiring', 'days' => '2'])->assertSessionHas('status', 'success');
    $complex->refresh();
    $expiryLink = $complex->join_link;
    expect($complex->join_link_expires_at->isFuture())->toBeTrue();
    $this->get($settings)->assertSee('مهلت استفاده تا');
    $this->travel(3)->days();
    app(JoinLinks::class)->recordExpiries();
    $this->get($settings)->assertSee('منقضی شده');
    $this->actingAs($second)->post($join, ['link' => $expiryLink])->assertSessionHas('message', 'مهلت استفاده از لینک دعوت به پایان رسیده است.');
    expect(AuditEvent::where('event', 'join_link.redeemed')->sole()->subject_id)->toBe($first->id)
        ->and(AuditEvent::where('event', 'join_link.expired')->sole()->actor_id)->toBe($building->manager->id);
    expect(AuditEvent::all()->toJson())->not->toContain($link)->not->toContain($expiryLink);
});

it('aggregates only the complex records overlapping a Jalali month and links to their panels', function () {
    $building = new BuildingContext;
    $foreign = new BuildingContext;
    MonthlyCharge::factory()->for($building->complex)->create(['start_date' => '1405-01-30', 'end_date' => '1405-02-03', 'amount' => 4500]);
    Poll::factory()->for($building->complex)->create(['title' => 'Local deadline', 'end_date' => (new Jalalian(1405, 2, 3))->toCarbon()]);
    IncidentalCost::factory()->for($building->complex)->create(['title' => 'Local cost', 'created_at' => (new Jalalian(1405, 2, 1))->toCarbon()]);
    IncidentalCost::factory()->for($foreign->complex)->create(['title' => 'Foreign cost', 'created_at' => (new Jalalian(1405, 2, 1))->toCarbon()]);
    Poll::factory()->for($building->complex)->create(['title' => 'Next month', 'end_date' => (new Jalalian(1405, 3, 1))->toCarbon()->startOfDay()]);
    $building->complex->update(['name' => '<script>buildingName()</script>']);
    $url = route('building.calendar', [$building->complex, 'year' => 1405, 'month' => 2]);
    $page = $this->actingAs($building->resident()->user)->get($url)->assertOk()
        ->assertSee('Local deadline')->assertSee('Local cost')->assertDontSee('Foreign cost')->assertDontSee('Next month')
        ->assertSee('tab=charge')->assertSee('tab=cost')->assertSee('tab=poll')
        ->assertDontSee('<script>buildingName()</script>', false);
    expect(substr_count($page->getContent(), 'شارژ: 4500'))->toBe(3);
    $this->actingAs($foreign->manager)->get($url)->assertForbidden();
});

it('keeps a pending form flash message during preference autosave and chat loading', function () {
    $building = new BuildingContext;
    $this->actingAs($building->manager)
        ->withSession(['status' => 'success', '_flash' => ['old' => ['status'], 'new' => []]])
        ->patchJson(route('building.preferences', $building->complex), ['panel' => 'chat'])
        ->assertNoContent()->assertSessionHas('status', 'success');
    $this->getJson('/dashboard/building/'.$building->complex->id.'/group/0')
        ->assertOk()->assertSessionHas('status', 'success');
});
