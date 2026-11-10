<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AccessMiddleware;
use App\Http\Requests\AnnouncementRequest;
use App\Models\Announcement;
use App\Models\Complex;
use App\Services\FileUploadService;
use App\Services\SmsService;

class AnnouncementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', AccessMiddleware::class.':manager']);
    }

    public function NotificationRegistration(AnnouncementRequest $request, Complex $complex, FileUploadService $uploads, SmsService $sms)
    {
        $data = $request->validated();

        $userId = auth()->id();
        $notifText = $data['notifText'] ?? null;

        $announcement = [
            'complex_id' => $complex->id,
            'manager_id' => $userId,
            'text' => $notifText,
            'image_url' => null,
        ];

        if ($data['notifPicture'] ?? null) {
            $announcement['image_url'] = $uploads->store($data['notifPicture'], 'public/notificationFiles');
        }

        $created = Announcement::create($announcement);
        $sms->queue('announcement', $created->id);

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
}
