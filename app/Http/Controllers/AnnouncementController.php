<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AccessMiddleware;
use App\Http\Requests\AnnouncementPinRequest;
use App\Http\Requests\AnnouncementRequest;
use App\Http\Requests\AnnouncementSearchRequest;
use App\Models\Announcement;
use App\Models\Complex;
use App\Services\AnnouncementService;
use App\Services\FileUploadService;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;

class AnnouncementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', AccessMiddleware::class.':member']);
        $this->middleware(AccessMiddleware::class.':manager')->except('markRead');
    }

    public function search(AnnouncementSearchRequest $request, Complex $complex, AnnouncementService $announcements): JsonResponse
    {
        $data = $request->validated();
        $rows = $announcements->search($complex, $request->user()->id, $data['state'], $data['q'] ?? null)->get();

        return response()->json(['html' => view('building.announcement-cards', ['announcements' => $rows, 'isManager' => true])->render()]);
    }

    public function pin(AnnouncementPinRequest $request, Announcement $announcement, AnnouncementService $announcements)
    {
        $announcements->pin($announcement, $request->user()->id, (bool) $request->validated('pinned'));

        return back()->with(['status' => 'success', 'message' => 'سنجاق اعلان بروزرسانی شد.', 'page' => 'notification']);
    }

    public function restore(Announcement $announcement)
    {
        $announcement->update(['archive' => false]);

        return back()->with(['status' => 'success', 'message' => 'اعلان بازگردانده شد.', 'page' => 'notification']);
    }

    public function markRead(Announcement $announcement): JsonResponse
    {
        abort_if($announcement->archive, 404);
        $announcement->reads()->firstOrCreate(['user_id' => auth()->id()]);

        return response()->json(auth()->user()->can('manage', $announcement->complex)
            ? ['read' => true, 'count' => $announcement->reads()->count()] : ['read' => true]);
    }

    public function readers(Announcement $announcement): JsonResponse
    {
        return response()->json(['readers' => $announcement->reads()->with('user')->orderBy('created_at')->orderBy('id')->get()
            ->map(fn ($read) => ['name' => $read->user->name, 'read_at' => $read->created_at->toISOString()])->all()]);
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
            $announcement['image_url'] = $uploads->store($data['notifPicture'], Announcement::ATTACHMENT_DIRECTORY);
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
