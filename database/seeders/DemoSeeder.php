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
use App\Models\PaymentAdditionalCost;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\SeenMessage;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Vote;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DemoSeeder extends Seeder
{
    // Local accounts only: admin, manager, resident1..3 @example.test; password: password.
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Demo accounts may only be seeded in local or testing environments.');
        }

        DB::transaction(function () {
            $password = Hash::make('password');
            $admin = User::factory()->create(['name' => 'مدیر سامانه', 'email' => 'admin@example.test', 'password' => $password]);
            UserRole::factory()->admin()->create(['user_id' => $admin->id]);

            $complex = Complex::factory()->create([
                'name' => 'مجتمع نمونه',
                'address' => 'تهران، خیابان نمونه',
                'number_of_floors' => 2,
                'number_of_units' => 4,
            ]);
            $manager = User::factory()->create(['name' => 'مدیر ساختمان', 'email' => 'manager@example.test', 'password' => $password]);
            $managerUnit = Unit::factory()->create(['complex_id' => $complex->id, 'unit_number' => 1, 'floor_number' => 1]);
            UserRole::factory()->manager()->create(['complex_id' => $complex->id, 'unit_id' => $managerUnit->id, 'user_id' => $manager->id]);

            $residents = collect();
            for ($number = 1; $number <= 3; $number++) {
                $user = User::factory()->create(['name' => 'ساکن '.$number, 'email' => 'resident'.$number.'@example.test', 'password' => $password]);
                $unit = Unit::factory()->create(['complex_id' => $complex->id, 'unit_number' => $number + 1, 'floor_number' => $number === 1 ? 1 : 2]);
                $residents->push(UserRole::factory()->create(['complex_id' => $complex->id, 'unit_id' => $unit->id, 'user_id' => $user->id]));
            }

            $paidCharge = MonthlyCharge::factory()->create(['complex_id' => $complex->id, 'user_id' => $manager->id, 'amount' => 250000]);
            MonthlyCharge::factory()->create(['complex_id' => $complex->id, 'user_id' => $manager->id, 'amount' => 350000]);
            ChargePayment::factory()->create(['role_id' => $residents[0]->id, 'monthly_charge_id' => $paidCharge->id]);

            $cost = IncidentalCost::factory()->create(['complex_id' => $complex->id, 'title' => 'سرویس آسانسور', 'cost_explanation' => 'سرویس دوره‌ای آسانسور', 'total_amount' => 400000, 'share_amount' => 100000]);
            PaymentAdditionalCost::factory()->create(['role_id' => $residents[1]->id, 'incidental_costs_id' => $cost->id]);
            Announcement::factory()->create(['complex_id' => $complex->id, 'manager_id' => $manager->id, 'text' => 'جلسه ساختمان روز جمعه برگزار می‌شود.']);
            Announcement::factory()->create(['complex_id' => $complex->id, 'manager_id' => $manager->id, 'text' => 'اعلان جلسه قبلی', 'archive' => true]);

            foreach ([false, true] as $expired) {
                $factory = Poll::factory();
                $poll = ($expired ? $factory->expired() : $factory)->create([
                    'complex_id' => $complex->id,
                    'title' => $expired ? 'نظرسنجی قبلی' : 'زمان جلسه',
                    'question' => 'کدام روز برای جلسه مناسب است؟',
                ]);
                $first = PollOption::factory()->create(['poll_id' => $poll->id, 'option_number' => 1, 'option' => 'پنجشنبه']);
                PollOption::factory()->create(['poll_id' => $poll->id, 'option_number' => 2, 'option' => 'جمعه']);
                Vote::factory()->create(['poll_id' => $poll->id, 'poll_option_id' => $first->id, 'role_id' => $residents[0]->id]);
            }

            $group = Group::factory()->create(['complex_id' => $complex->id, 'group_name' => $complex->name]);
            $welcome = Message::factory()->create(['group_id' => $group->id, 'user_id' => $manager->id, 'text' => 'به گروه ساختمان خوش آمدید.']);
            Message::factory()->create(['group_id' => $group->id, 'user_id' => $residents[0]->user_id, 'text' => 'سلام، وقت بخیر.']);
            Storage::disk('local')->put('public/chats/demo-message.png', file_get_contents(base_path('storage/app/public/default.png')));
            Message::factory()->create(['group_id' => $group->id, 'user_id' => $residents[1]->user_id, 'text' => 'تصویر نمونه', 'image_url' => 'demo-message.png']);
            SeenMessage::factory()->create(['message_id' => $welcome->id, 'group_id' => $group->id, 'user_id' => $residents[0]->user_id]);

            MembershipRequest::factory()->create(['user_id' => $residents[2]->user_id, 'name' => 'ساختمان در انتظار بررسی', 'address' => 'تهران، نشانی نمونه', 'number_of_floors' => 3, 'number_of_units' => 6]);
        });
    }
}
