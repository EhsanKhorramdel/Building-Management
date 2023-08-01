<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AccessMiddleware;
use Illuminate\Http\Request;
use App\Models\UserRole;
use App\Models\User;
use App\Models\Unit;
use App\Models\Complex;
use App\Models\Announcement;
use App\Models\IncidentalCost;
use App\Models\PaymentAdditionalCost;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Vote;
use App\Models\MonthlyCharge;
use App\Models\ChargePayment;
use App\Models\Group;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Morilog\Jalali\Jalalian;

class BuildingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(AccessMiddleware::class . ':manager')->only(['upgradeRole', 'downgradeRole', 'NotificationRegistration', 'cancelNotification', 'incidentalCost', 'buildingCharge']);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Complex $complex)
    {
        $userRoles = UserRole::where('complex_id', $complex->id)
            ->where('user_id', auth()->id())
            ->get();

        $hasNullUnit = $userRoles->contains(function ($userRole) {
            return $userRole->unit_id === null;
        });

        if ($hasNullUnit) {
            $message = 'کاربر محترم، لطفاً ابتدا تمامی واحدهای خود در این ساختمان را ثبت کنید.';
            $status = 'error';
            $page = 'home';

            return redirect()->back()->with(compact('message', 'status', 'page'));
        }

        $userRole = $complex->userRoles;
        $complex->units;

        $userIds = $userRole->pluck('user_id')->unique();
        $users = User::whereIn('id', $userIds)->get();

        $complex->users = $users;
        $complex->user_roles = $userRole;

        $managerCount = $userRole->where('role', 'manager')->pluck('user_id')->unique()->count();
        $residentCount = $userRole->where('role', 'resident')->pluck('user_id')->unique()->count();

        $userLogInId = auth()->id();
        $isManager =  UserRole::where('complex_id', $complex->id)
            ->where('user_id', $userLogInId)
            ->where('active', '1')
            ->where('role', 'manager')
            ->exists();

        $announcements = $complex->announcements;
        $incidentalCosts = $complex->incidentalCosts;

        foreach ($announcements as $announcement) {
            $managerId = $announcement['manager_id'];
            $managerName = $users->Where('id', $managerId)->first()['name'];
            $announcement->managerName = $managerName;
        }

        $polls = $complex->polls;
        $polls = $polls->sortByDesc('end_date');
        $currentDate = Carbon::now();
        $expiredCount = 0;
        $notExpiredCount = 0;

        $monthlyCharges = MonthlyCharge::where('complex_id', $complex->id)
            ->where('end_date', '>', Jalalian::fromDateTime($currentDate)->format('Y-m-d'))
            ->get();

        foreach ($polls as $poll) {
            $endDate = Carbon::parse($poll['end_date']);

            if ($endDate->isPast()) {
                $expiredCount++;
            } else {
                $notExpiredCount++;
            }
        }

        foreach ($polls as $poll) {
            $poll->poll_options = $poll->pollOptions;
        }

        $group = Group::where('complex_id', $complex->id)->first();

        $residents = [];

        foreach ($complex['users'] as $user) {
            $userUnits = collect($complex['user_roles'])
                ->where('user_id', $user['id'])
                ->map(function ($userRole) use ($complex) {
                    return [
                        'role' => $userRole['role'] ?? 'N/A',
                        'roleId' => $userRole['id'],
                        'name' => $complex['users']->where('id', $userRole['user_id'])->first()->name,
                        'unitNumber' => $complex['units']->where('id', $userRole['unit_id'])->first()['unit_number'] ?? 'N/A',

                    ];
                });

            $residents = array_merge($residents, $userUnits->all());
        }


        return view('building.building', [
            'complex' => $complex,
            'managerCount' => $managerCount,
            'residentCount' => $residentCount,
            'isManager' => $isManager,
            'announcements' => $announcements,
            'expiredCount' => $expiredCount,
            'notExpiredCount' => $notExpiredCount,
            'polls' => $polls,
            'incidentalCosts' => $incidentalCosts,
            'monthlyCharges' => $monthlyCharges,
            'group' => $group,
            'residents' => $residents,
            'userLogInId' => $userLogInId,
        ]);
    }

    public function upgradeRole(UserRole $userRole)
    {
        $complexId = $userRole->complex_id;
        $userId = $userRole->user_id;
        $complex = Complex::where('id', $complexId)->first();

        if ($userRole->role == 'manager') {
            $message = 'خطا! کاربر انتخابی مدیر ساختمان است.';
            $status = 'error';
            $page = 'home';
        } else if ($userRole->role == 'resident') {

            $complex->userRoles()
                ->where('role', 'resident')
                ->where('user_id', $userId)
                ->where('complex_id', $complexId)
                ->update(['role' => 'manager']);


            $message = 'نقش کاربر به مدیر تغییر یافت.';
            $status = 'success';
            $page = 'home';
        }
        return redirect()->back()->with(compact('message', 'status', 'page'));
    }

    public function downgradeRole(Complex $complex)
    {
        $userId = auth()->id();
        $complexId = $complex->id;

        $managers = $complex->userRoles->where('role', 'manager')->pluck('user_id')->unique();
        $managersNum = $managers->count();

        if (--$managersNum) {
            $complex->userRoles()
                ->where('role', 'manager')
                ->where('user_id', $userId)
                ->where('complex_id', $complexId)
                ->update(['role' => 'resident']);


            Auth::guard('web')->logout();
            return redirect()->route('login');
        }

        $message = 'خطا! کاربر محترم شما تنها مدیر ساختمان می باشید .';
        $status = 'error';
        $page = 'setting';


        return redirect()->back()->with(compact('message', 'status', 'page'));
    }

    public function NotificationRegistration(Request $request, Complex $complex)
    {
        $rules = [
            'notifPicture' => 'nullable|file|mimes:gif,jpg,webp,png|max:5120',
            'notifText' => 'nullable',
            'required_at_least_one' => 'required_without_all:notifPicture,notifText',
        ];

        $messages = [
            'notifPicture.required' => 'وارد کردن تصویر اعلان الزامی است.',
            'notifPicture.file' => 'فایل تصویر اعلان باید باشد.',
            'notifPicture.mimes' => 'فرمت تصویر اعلان باید یکی از gif، jpg، webp، png باشد.',
            'notifPicture.max' => 'حجم تصویر اعلان نباید بیشتر از 5 مگابایت باشد.',
            'notifText.required' => 'وارد کردن متن اعلان الزامی است.',
            'required_at_least_one.required_without_all' => 'حداقل یکی از فیلدها باید پر شود.',
        ];

        $validated = $request->validateWithBag('notifRegister', $rules, $messages);

        $userId = auth()->id();
        $notifText = $request->notifText;

        $announcement = [
            'complex_id' => $complex->id,
            'manager_id' => $userId,
            'text' => $notifText,
            'image_url' => null,
        ];

        if ($request->hasFile('notifPicture')) {
            $notifPicture = $request->file('notifPicture');
            $notifPictureName = $notifPicture->getClientOriginalName();
            $extension = $notifPicture->extension();

            $timestamp = time();
            $randomNumber1 = rand(1000000000, 1999999999);
            $randomNumber2 = rand(1000000000, 1999999999);

            $newNotifPictureName = sha1($timestamp . '_' . $randomNumber1 . '_' . $randomNumber2 . '_' . $notifPictureName);
            $newNotifPictureName = $newNotifPictureName . '.' . $extension;

            Storage::disk('local')->putFileAs(
                'public/notificationFiles',
                $notifPicture,
                $newNotifPictureName
            );

            $announcement['image_url'] = $newNotifPictureName;
        }

        Announcement::create($announcement);

        $status = 'success';
        $message = 'اعلان جدید با موفقیت ثبت شد.';
        $page = 'notification';

        return redirect()->back()->with(compact('message', 'status', 'page'));
    }

    public function cancelNotification(Announcement $announcement)
    {
        $announcement->archive = 1;
        $announcement->save();

        $status = 'success';
        $message = 'اعلان با موفقیت لغو شد';
        $page = 'notification';


        return redirect()->back()->with(compact('message', 'status', 'page'));
    }

    public function pollCreate(Request $request, int $complexId)
    {
        $rules = [
            'title' => 'nullable|string',
            'question' => 'required|string',
            'endMinutes' => 'nullable|integer|min:0',
            'endHours' => 'nullable|integer|min:0',
            'endDays' => 'nullable|integer|min:0',
            'required_at_least_one' => 'required_without_all:endMinutes,endHours,endDays',
        ];


        $messages = [
            'title.string' => 'فیلد عنوان باید یک رشته باشد.',
            'question.required' => 'فیلد سوال اجباری است.',
            'question.string' => 'فیلد سوال باید یک رشته باشد.',
            'endMinutes.integer' => 'فیلد دقیقه پایان باید یک عدد صحیح باشد.',
            'endMinutes.min' => 'فیلد دقیقه پایان باید بزرگتر یا مساوی ۰ باشد.',
            'endHours.integer' => 'فیلد ساعت پایان باید یک عدد صحیح باشد.',
            'endHours.min' => 'فیلد ساعت پایان باید بزرگتر یا مساوی ۰ باشد.',
            'endDays.integer' => 'فیلد روز پایان باید یک عدد صحیح باشد.',
            'endDays.min' => 'فیلد روز پایان باید بزرگتر یا مساوی ۰ باشد.',
            'required_at_least_one' => 'حداقل یکی از فیلدهای زمان پایان باید پر شود.',
        ];


        $validated = $request->validateWithBag('pollCreate', $rules, $messages);

        $optionsCount = 0;
        $nullOptionsCount = 0;

        while ($request->has("option" . ($optionsCount + 1))) {
            $optionsCount++;
        }

        for ($i = 1; $i <= $optionsCount; $i++) {
            if ($request->input("option" . $i) === null) {
                $nullOptionsCount++;
            }
        }

        $currentDateTime = Carbon::now();
        $startDate = $currentDateTime;
        $endDate = $currentDateTime->copy()->addMinutes($request->endMinutes)
            ->addHours($request->endHours)
            ->addDays($request->endDays);



        if ($optionsCount - $nullOptionsCount >= 2) {
            $poll = [
                'complex_id' => $complexId,
                'title' => $request->title,
                'question' => $request->question,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];

            $createdPoll = Poll::create($poll);
            $pollId = $createdPoll->id;

            for ($i = 1; $i <= $optionsCount - $nullOptionsCount; $i++) {
                $pollOption = [
                    'poll_id' => $pollId,
                    'option_number' => $i,
                    'option' => $request->input("option" . $i)
                ];
                PollOption::create($pollOption);
            }

            $status = 'success';
            $message = 'رای گیری با موفقیت ایجاد شد';
            $page = 'poll';
        } else {
            $status = 'error';
            $message = 'کاربر محترم حداقل دو گزینه برای ایجاد رای گیری ضروری است.';
            $page = 'poll';
        }

        return redirect()->back()->with(compact('message', 'status', 'page'));
    }

    public function vote(Request $request)
    {
        $pollOptionId = $request->voteRadio;

        if ($pollOptionId) {
            $pollOption = PollOption::find($pollOptionId);

            $poll = $pollOption->poll;

            $complexId = $poll->complex_id;
            $userId = auth()->id();

            $userRoles = UserRole::where('complex_id', $complexId)->where("user_id", $userId)->get();

            foreach ($userRoles as $userRole) {
                $vote = [
                    'poll_id' => $poll->id,
                    'role_id' => $userRole->id,
                ];

                $userVote = Vote::updateOrCreate($vote, ['poll_option_id' => $pollOptionId]);

                if ($userVote->wasRecentlyCreated) {
                    $status = 'success';
                    $message = 'رای شما ثبت شد';
                    $page = 'poll';
                } else {
                    $status = 'success';
                    $message = 'رای شما بروزرسانی شد';
                    $page = 'poll';
                }
            }
        } else {
            $status = 'error';
            $message = 'کاربر محترم، شما گزینه ای انتخاب نکرده اید.';
            $page = 'poll';
        }

        return redirect()->back()->with(compact('message', 'status', 'page'));
    }

    public function voteResult(Request $request)
    {
        $pollId = $request->poll_id;
        $votes = Vote::where('poll_id', $pollId)->get();
        $options = PollOption::where('poll_id', $pollId)->get();

        $voteCounts = [];
        foreach ($votes as $vote) {
            $optionId = $vote['poll_option_id'];
            if (isset($voteCounts[$optionId])) {
                $voteCounts[$optionId]++;
            } else {
                $voteCounts[$optionId] = 1;
            }
        }

        $results = [];
        foreach ($options as $option) {
            $optionId = $option['id'];
            $optionText = $option['option'];
            $voteCount = isset($voteCounts[$optionId]) ? $voteCounts[$optionId] : 0;

            $results[] = [
                'option' => $optionText,
                'voteCount' => $voteCount
            ];
        }

        return response()->json($results);
    }

    public function incidentalCost(Request $request, Complex $complex)
    {
        $rules = [
            'cost_invoice' => 'nullable|file|mimes:gif,jpg,webp,png|max:5120',
            'cost_explanation' => 'nullable|string',
            'title' => 'required|string',
            'total_amount' => 'required|numeric',
            'share_amount' => 'required|numeric',
        ];

        $messages = [
            'cost_invoice.file' => 'فایل فاکتور هزینه باید باشد.',
            'cost_invoice.mimes' => 'فرمت فاکتور هزینه باید یکی از gif، jpg، webp، png باشد.',
            'cost_invoice.max' => 'حجم تصویر فاکتور هزینه نباید بیشتر از 5 مگابایت باشد.',
            'cost_explanation.string' => 'فیلد توضیح هزینه باید یک رشته باشد.',
            'title.required' => 'فیلد عنوان اجباری است.',
            'title.string' => 'فیلد عنوان باید یک رشته باشد.',
            'total_amount.required' => 'فیلد مبلغ کل اجباری است.',
            'share_amount.required' => 'فیلد مبلغ سهم افراد اجباری است.',
            'total_amount.numeric' => 'فیلد مبلغ کل باید عددی باشد.',
            'share_amount.numeric' => 'فیلد مبلغ سهم باید عددی باشد.',
        ];

        $validated = $request->validateWithBag('incidentalCost', $rules, $messages);

        $incidentalCost = [
            'complex_id' => $complex->id,
            'cost_invoice' =>  null,
            'cost_explanation' => $request->cost_explanation,
            'title' => $request->title,
            'total_amount' => $request->total_amount,
            'share_amount' => $request->share_amount,
        ];

        if ($request->hasFile('cost_invoice')) {
            $costInvoice = $request->file('cost_invoice');
            $costInvoiceName = $costInvoice->getClientOriginalName();
            $extension = $costInvoice->extension();

            $timestamp = time();
            $randomNumber1 = rand(1000000000, 1999999999);
            $randomNumber2 = rand(1000000000, 1999999999);

            $newCostInvoiceName = sha1($timestamp . '_' . $randomNumber1 . '_' . $randomNumber2 . '_' . $costInvoiceName);
            $newCostInvoiceName = $newCostInvoiceName . '.' . $extension;

            Storage::disk('local')->putFileAs(
                'public/incidentalCostFiles',
                $costInvoice,
                $newCostInvoiceName
            );

            $incidentalCost['cost_invoice'] = $newCostInvoiceName;
        }

        IncidentalCost::create($incidentalCost);

        $status = 'success';
        $message = 'هزینه جدید با موفقیت ثبت شد.';
        $page = 'cost';

        return redirect()->back()->with(compact('message', 'status', 'page'));
    }

    public function incidentalCostPayment(Request $request)
    {
        $rules = [
            'unitNumber' => 'required|integer|min:1',
            'Amount' => 'required|numeric|min:1',
        ];

        $messages = [
            'unitNumber.required' => 'فیلد شماره واحد اجباری است.',
            'unitNumber.integer' => 'فیلد شماره واحد باید عدد صحیح باشد.',
            'unitNumber.min' => 'فیلد شماره واحد باید حداقل :min باشد.',
            'Amount.required' => 'فیلد مبلغ اجباری است.',
            'Amount.numeric' => 'فیلد مبلغ باید عددی باشد.',
            'Amount.min' => 'فیلد مبلغ باید حداقل :min باشد.',
        ];

        $validated = $request->validateWithBag('incidentalPayCost', $rules, $messages);

        $userId = auth()->id();
        $complexId = $request->complexId;

        $unit = Unit::where('complex_id', $complexId)
            ->where('unit_number', $request->unitNumber)
            ->first();

        $role = null;

        if ($unit) {
            $role = UserRole::where('unit_id', $unit->id)
                ->where('complex_id', $complexId)
                ->where('user_id', $userId)
                ->first();
        } else {
            $status = 'error';
            $message = 'واحد وارد شده ثبت نشده است.';
            $page = 'cost';

            return redirect()->back()->with(compact('message', 'status', 'page'));
        }

        if ($role) {
            $paymentExists = PaymentAdditionalCost::where('role_id', $role->id)
                ->where('incidental_costs_id', $request->incidentalCostId)
                ->exists();

            if (!$paymentExists) {
                $PaymentAdditionalCost = [
                    'role_id' => $role->id,
                    'incidental_costs_id' => $request->incidentalCostId,
                    'amount' => $request->Amount,
                ];

                PaymentAdditionalCost::create($PaymentAdditionalCost);

                $status = 'success';
                $message = 'پرداخت با موفقیت انجام شد.';
                $page = 'cost';
            } else {
                $status = 'error';
                $message = 'امکان پرداخت مجدد هزینه وجود ندارد.';
                $page = 'cost';
            }
        } else {
            $status = 'error';
            $message = 'واحد وارد شده متعلق به شما نیست.';
            $page = 'cost';
        }

        return redirect()->back()->with(compact('message', 'status', 'page'));
    }

    public function buildingCharge(Request $request, Complex $complex)
    {
        $rules = [
            'chargeAmount' => 'required|numeric|min:0',
            'dayStart' => 'required|integer|min:1|max:31',
            'monthStart' => 'required|integer|min:1|max:12',
            'yearStart' => 'required|integer|min:1300',
            'dayEnd' => 'required|integer|min:1|max:31',
            'monthEnd' => 'required|integer|min:1|max:12',
            'yearEnd' => 'required|integer|min:1300',
        ];

        $messages = [
            'chargeAmount.required' => 'مقدار شارژ الزامی است.',
            'chargeAmount.numeric' => 'مقدار شارژ باید عددی باشد.',
            'chargeAmount.min' => 'مقدار شارژ باید حداقل :min باشد.',
            'dayStart.required' => 'روز شروع الزامی است.',
            'dayStart.integer' => 'روز شروع باید یک عدد صحیح باشد.',
            'dayStart.min' => 'روز شروع باید حداقل :min باشد.',
            'dayStart.max' => 'روز شروع باید حداکثر :max باشد.',
            'monthStart.required' => 'ماه شروع الزامی است.',
            'monthStart.integer' => 'ماه شروع باید یک عدد صحیح باشد.',
            'monthStart.min' => 'ماه شروع باید حداقل :min باشد.',
            'monthStart.max' => 'ماه شروع باید حداکثر :max باشد.',
            'yearStart.required' => 'سال شروع الزامی است.',
            'yearStart.integer' => 'سال شروع باید یک عدد صحیح باشد.',
            'yearStart.min' => 'سال شروع باید حداقل :min باشد.',
            'dayEnd.required' => 'روز پایان الزامی است.',
            'dayEnd.integer' => 'روز پایان باید یک عدد صحیح باشد.',
            'dayEnd.min' => 'روز پایان باید حداقل :min باشد.',
            'dayEnd.max' => 'روز پایان باید حداکثر :max باشد.',
            'monthEnd.required' => 'ماه پایان الزامی است.',
            'monthEnd.integer' => 'ماه پایان باید یک عدد صحیح باشد.',
            'monthEnd.min' => 'ماه پایان باید حداقل :min باشد.',
            'monthEnd.max' => 'ماه پایان باید حداکثر :max باشد.',
            'yearEnd.required' => 'سال پایان الزامی است.',
            'yearEnd.integer' => 'سال پایان باید یک عدد صحیح باشد.',
            'yearEnd.min' => 'سال پایان باید حداقل :min باشد.',
        ];

        $validated = $request->validateWithBag('buildingCharge', $rules, $messages);

        $startDate = $request->yearStart . '-' . $request->monthStart . '-' . $request->dayStart;
        $endDate = $request->yearEnd . '-' . $request->monthEnd . '-' . $request->dayEnd;

        if ($endDate < $startDate) {
            $status = 'error';
            $message = 'تاریخ شروع نمی تواند از تاریخ پایان بزرگتر باشد.';
            $page = 'charge';
            return redirect()->back()->with(compact('message', 'status', 'page'));
        }

        $monthlyCharges = [
            'complex_id' => $complex->id,
            'user_id' => auth()->id(),
            'amount' =>  $request->chargeAmount,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        MonthlyCharge::create($monthlyCharges);

        $status = 'success';
        $message = 'شارژ جدید با موفقیت ثبت شد.';
        $page = 'charge';

        return redirect()->back()->with(compact('message', 'status', 'page'));
    }

    public function buildingChargePayment(Request $request)
    {
        $rules = [
            'unitNumber' => 'required|integer|min:1',
            'Amount' => 'required|numeric|min:1',
        ];

        $messages = [
            'unitNumber.required' => 'فیلد شماره واحد اجباری است.',
            'unitNumber.integer' => 'فیلد شماره واحد باید عدد صحیح باشد.',
            'unitNumber.min' => 'فیلد شماره واحد باید حداقل :min باشد.',
            'Amount.required' => 'فیلد مبلغ اجباری است.',
            'Amount.numeric' => 'فیلد مبلغ باید عددی باشد.',
            'Amount.min' => 'فیلد مبلغ باید حداقل :min باشد.',
        ];

        $validated = $request->validateWithBag('buildingPayCharge', $rules, $messages);

        $currentDate = Carbon::now();
        $currentJalaliDate = Jalalian::fromCarbon($currentDate);

        $monthlyChargeStartDate = MonthlyCharge::where('id', $request->monthlyChargeId)->first()->start_date;

        if ($monthlyChargeStartDate > $currentJalaliDate) {
            $status = 'error';
            $message = 'امکان پرداخت شارژ قبل از فرا رسیدن تاریخ شروع وجود ندارد.';
            $page = 'charge';
            return redirect()->back()->with(compact('message', 'status', 'page'));
        }

        $userId = auth()->id();
        $complexId = $request->complexId;
        $unit = Unit::where('complex_id', $complexId)
            ->where('unit_number', $request->unitNumber)
            ->first();

        $role = null;

        if ($unit) {
            $role = UserRole::where('unit_id', $unit->id)
                ->where('complex_id', $complexId)
                ->where('user_id', $userId)
                ->first();
        } else {
            $status = 'error';
            $message = 'واحد وارد شده ثبت نشده است.';
            $page = 'charge';

            return redirect()->back()->with(compact('message', 'status', 'page'));
        }

        if ($role) {

            $payments = ChargePayment::where('role_id', $role->id)
                ->where('monthly_charge_id', $request->monthlyChargeId)
                ->get();

            $paymentExists = $payments->contains(function ($payment) use ($currentJalaliDate) {
                $paymentDate = Jalalian::fromCarbon($payment->created_at);
                return $paymentDate->getYear() === $currentJalaliDate->getYear()
                    && $paymentDate->getMonth() === $currentJalaliDate->getMonth();
            });

            if (!$paymentExists) {
                $ChargePayment = [
                    'role_id' => $role->id,
                    'monthly_charge_id' => $request->monthlyChargeId,
                    'amount' => $request->Amount,
                ];

                ChargePayment::create($ChargePayment);

                $status = 'success';
                $message = 'پرداخت با موفقیت انجام شد.';
                $page = 'charge';
            } else {
                $status = 'error';
                $message = 'کاربر گرامی، شما شارژ خود را در ماه جاری پرداخت کرده اید.';
                $page = 'charge';
            }
        } else {
            $status = 'error';
            $message = 'واحد وارد شده متعلق به شما نیست.';
            $page = 'charge';
        }

        return redirect()->back()->with(compact('message', 'status', 'page'));
    }

    public function payments(Request $request)
    {
        $complexId = $request->complex_id;

        $complex = Complex::findOrFail($complexId);

        $units = Unit::where('complex_id', $complex->id)->get();

        $data = [];

        foreach ($units as $unit) {
            $userRole = UserRole::where('unit_id', $unit->id)->first();

            if ($userRole) {
                $chargePayments = ChargePayment::where('role_id', $userRole->id)->get();

                foreach ($chargePayments as $payment) {
                    $user = User::findOrFail($userRole->user_id);

                    $shamsiDate = Jalalian::fromDateTime($payment->created_at)->format('d-m-Y');

                    $paymentData = [
                        'unit_number' => $unit->unit_number,
                        'type' => 'شارژ',
                        'amount' => $payment->amount,
                        'date' => $shamsiDate,
                        'user_name' => $user->name,
                    ];

                    $data[] = (object)$paymentData;
                }

                $additionalCosts = PaymentAdditionalCost::where('role_id', $userRole->id)->get();

                foreach ($additionalCosts as $additionalCost) {
                    $user = User::findOrFail($userRole->user_id);

                    $shamsiDate = Jalalian::fromDateTime($additionalCost->created_at)->format('d-m-Y');

                    $additionalCostData = [
                        'unit_number' => $unit->unit_number,
                        'type' => 'جانبی',
                        'amount' => $additionalCost->amount,
                        'date' => $shamsiDate,
                        'user_name' => $user->name,
                    ];

                    $data[] = (object)$additionalCostData;
                }
            }
        }

        return response()->json($data);
    }
}
