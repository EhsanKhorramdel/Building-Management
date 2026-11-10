<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\ChargePayment;
use App\Models\Complex;
use App\Models\Group;
use App\Models\IncidentalCost;
use App\Models\MembershipRequest;
use App\Models\Message;
use App\Models\MonthlyCharge;
use App\Models\Poll;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class E2ESeeder extends Seeder
{
    // E2E only: admin, manager, resident, neighbor, other-manager and other-resident @example.test; password: password.
    public function run(): void
    {
        if (! app()->environment('e2e') || DB::getDriverName() !== 'sqlite'
            || realpath(DB::connection()->getDatabaseName()) !== realpath(database_path('e2e.sqlite'))) {
            throw new RuntimeException('Browser fixtures require the isolated e2e database.');
        }

        DB::transaction(function () {
            $password = Hash::make('password', ['rounds' => 4]);
            $admin = User::factory()->create(['name' => 'مدیر سامانه آزمون', 'email' => 'admin@example.test', 'password' => $password]);
            UserRole::factory()->admin()->for($admin)->create();

            $complex = Complex::create([
                'name' => 'مجتمع سرو', 'address' => 'تهران، خیابان سرو',
                'number_of_floors' => 2, 'number_of_units' => 6,
                'join_link' => 'e2e-sarv-invitation', 'latitude' => 35.6892, 'longitude' => 51.389,
            ]);
            $foreign = Complex::create([
                'name' => 'مجتمع کاج', 'address' => 'تهران، خیابان کاج',
                'number_of_floors' => 1, 'number_of_units' => 4, 'join_link' => 'e2e-kaj-invitation',
            ]);
            $manager = $this->member($complex, 'manager', 'مدیر سرو', 'manager', 1, $password);
            $resident = $this->member($complex, 'resident', 'ساکن سرو', 'resident', 2, $password);
            $neighbor = $this->member($complex, 'neighbor', 'همسایه سرو', 'resident', 3, $password);
            $this->member($foreign, 'other-manager', 'مدیر کاج', 'manager', 1, $password);
            $this->member($foreign, 'other-resident', 'ساکن کاج', 'resident', 2, $password);
            $group = Group::create(['complex_id' => $complex->id, 'group_name' => $complex->name]);
            Group::create(['complex_id' => $foreign->id, 'group_name' => $foreign->name]);

            $image = file_get_contents(public_path('assets/images/app-icon-192.png'));
            Storage::disk('local')->put('public/default.png', $image);
            Storage::disk('local')->put('public/notificationFiles/e2e-announcement.png', $image);
            Storage::disk('local')->put(Message::ATTACHMENT_DIRECTORY.'/e2e-attachment.png', $image);

            Announcement::create([
                'complex_id' => $complex->id, 'manager_id' => $manager->user_id,
                'text' => 'جلسه هفتگی مجتمع سرو', 'image_url' => 'e2e-announcement.png',
            ]);
            IncidentalCost::create([
                'complex_id' => $complex->id, 'title' => 'سرویس آسانسور',
                'total_amount' => 450000, 'share_amount' => 75000,
            ]);
            $paid = MonthlyCharge::factory()->for($complex)->create(['user_id' => $manager->user_id, 'amount' => 250000]);
            MonthlyCharge::factory()->for($complex)->create(['user_id' => $manager->user_id, 'amount' => 350000]);
            ChargePayment::factory()->create(['role_id' => $resident->id, 'monthly_charge_id' => $paid->id, 'amount' => 250000]);

            foreach (['نظرسنجی باز' => now()->addDays(5), 'نظرسنجی پایان‌یافته' => now()->subDay()] as $title => $end) {
                $poll = Poll::create([
                    'complex_id' => $complex->id, 'title' => $title, 'question' => 'روز جلسه کدام باشد؟',
                    'start_date' => now()->subDays(2), 'end_date' => $end,
                ]);
                $poll->pollOptions()->createMany([
                    ['option_number' => 1, 'option' => 'شنبه'],
                    ['option_number' => 2, 'option' => 'یکشنبه'],
                ]);
            }

            for ($number = 1; $number <= 36; $number++) {
                $message = new Message([
                    'group_id' => $group->id, 'user_id' => $number % 2 ? $manager->user_id : $resident->user_id,
                    'text' => 'پیام نمونه '.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                    'image_url' => $number === 35 ? 'e2e-attachment.png' : null,
                ]);
                $message->created_at = '2026-01-01 12:00:00';
                $message->save();
            }

            foreach (['pending' => 'در انتظار', 'accepted' => 'پذیرفته‌شده', 'rejected' => 'ردشده'] as $status => $label) {
                MembershipRequest::create([
                    'user_id' => $neighbor->user_id, 'name' => 'درخواست نمونه '.$label,
                    'address' => 'تهران، نشانی نمونه', 'number_of_floors' => 2,
                    'number_of_units' => 6, 'request_status' => $status,
                ]);
            }
        });
    }

    private function member(Complex $complex, string $account, string $name, string $role, int $number, string $password): UserRole
    {
        $user = User::factory()->create([
            'name' => $name, 'email' => $account.'@example.test', 'password' => $password,
            // Keep the verification prompt reachable in the signed-in sweep.
            'email_verified_at' => $account === 'manager' ? null : now(),
            'national_code' => '0012345678', 'phone_number' => '09123456789',
        ]);
        $unit = Unit::create(['complex_id' => $complex->id, 'unit_number' => $number, 'floor_number' => 1]);

        return UserRole::factory()->for($complex)->for($user)->for($unit)->create(['role' => $role]);
    }
}
