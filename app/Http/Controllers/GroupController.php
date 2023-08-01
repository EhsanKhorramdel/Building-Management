<?php

namespace App\Http\Controllers;

use App\Events\DeleteChatMessage;
use App\Events\EditChatMessage;
use App\Events\NewChatMessage;
use Pusher\Pusher;
use App\Models\Complex;
use App\Models\Group;
use App\Models\Message;
use App\Models\SeenMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GroupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Complex $complex, int $lastReceivedMessageId)
    {
        $group = Group::where('complex_id', $complex->id)->first();

        $perPage = 10;

        $previousMessages = Message::where('group_id', $group->id)
            ->where('deleted', false)
            ->with('user')
            ->where('id', '<', $lastReceivedMessageId)
            ->orderByDesc('id')
            ->take($perPage)
            ->get()
            ->reverse();

        $currentMessage = Message::where('group_id', $group->id)
            ->where('deleted', false)
            ->with('user')
            ->where('id', $lastReceivedMessageId)
            ->first();

        $nextMessages = Message::where('group_id', $group->id)
            ->where('deleted', false)
            ->with('user')
            ->where('id', '>', $lastReceivedMessageId)
            ->orderBy('id')
            ->take($perPage)
            ->get();

        $loggedInUserId = auth()->id();

        $finalMessages = [];
        foreach ($previousMessages as $message) {
            $isOwnedByLoggedInUser = ($message->user_id == $loggedInUserId);

            $messageData = [
                'message_id' => $message->id,
                'user_name' => $message->user->name,
                'user_id' => $message->user_id,
                'text' => $message->text,
                'image_url' => $message->image_url,
                'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                'is_deleted' => $message->deleted,
                'is_edited' => $message->edited,
                'is_owned_by_logged_in_user' => $isOwnedByLoggedInUser,
            ];

            $finalMessages[] = $messageData;
        }

        $finalMessages[] = [
            'message_id' => $currentMessage->id,
            'user_name' => $currentMessage->user->name,
            'user_id' => $currentMessage->user_id,
            'text' => $currentMessage->text,
            'image_url' => $currentMessage->image_url,
            'created_at' => $currentMessage->created_at->format('Y-m-d H:i:s'),
            'is_deleted' => $currentMessage->deleted,
            'is_edited' => $currentMessage->edited,
            'is_owned_by_logged_in_user' => ($currentMessage->user_id == $loggedInUserId),
        ];

        foreach ($nextMessages as $message) {
            $isOwnedByLoggedInUser = ($message->user_id == $loggedInUserId);

            $messageData = [
                'message_id' => $message->id,
                'user_name' => $message->user->name,
                'user_id' => $message->user_id,
                'text' => $message->text,
                'image_url' => $message->image_url,
                'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                'is_deleted' => $message->deleted,
                'is_edited' => $message->edited,
                'is_owned_by_logged_in_user' => $isOwnedByLoggedInUser,
            ];

            $finalMessages[] = $messageData;
        }

        $firstMessageId = $previousMessages->isNotEmpty() ? $previousMessages->first()->id : $lastReceivedMessageId;
        $lastMessageId = $nextMessages->isNotEmpty() ? $nextMessages->last()->id : $lastReceivedMessageId;

        return response()->json([
            'messages' => $finalMessages,
            'first_message_id' => $firstMessageId,
            'last_message_id' => $lastMessageId,
        ]);
    }


    public function getMessageViewers(Request $request)
    {
        $messageId = $request->input('message_id');

        $senderUserId = Message::where('id', $messageId)->first()->user_id;

        $viewers = SeenMessage::where('message_id', $messageId)
            ->where('user_id', '!=', $senderUserId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $filteredViewers = $viewers->map(function ($viewer) {
            return [
                'user_name' => $viewer->user->name,
                'viewed_at' => $viewer->created_at,
            ];
        });

        return response()->json($filteredViewers);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $userId = auth()->id();
        $messageText = $request->input('messageText');
        $messageImage = $request->file('messageImage');
        $complexId = $request->input('complexId');

        $group = Group::where('complex_id', $complexId)->first();
        $newImageName = null;

        if ($messageImage) {
            $imageName = $messageImage->getClientOriginalName();
            $extension = $messageImage->extension();

            $timestamp = time();
            $randomNumber1 = rand(1000000000, 1999999999);
            $randomNumber2 = rand(1000000000, 1999999999);

            $newImageName = sha1($timestamp . '_' . $randomNumber1 . '_' . $randomNumber2 . '_' . $imageName);
            $newImageName = $newImageName . '.' . $extension;

            Storage::disk('local')->putFileAs(
                'public/chats',
                $messageImage,
                $newImageName
            );
        }
        if ($messageText || $newImageName) {
            $message = Message::create([
                'group_id' => $group->id,
                'user_id' => $userId,
                'image_url' => $newImageName,
                'text' => $messageText,
            ]);

            $messageData = [
                'message_id' => $message->id,
                'user_name' => $message->user->name,
                'user_id' => $message->user_id,
                'text' => $message->text,
                'image_url' => $message->image_url,
                'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                'is_deleted' => $message->deleted,
                'is_edited' => $message->edited,
                'is_owned_by_logged_in_user' => false,
            ];

            event(new NewChatMessage($messageData));
        }
    }

    public function seen(Request $request)
    {
        $userId = auth()->id();

        $message = Message::where('id', $request->messageId)->first();
        $groupId = $message->group_id;

        $seenMessage = SeenMessage::firstOrNew([
            'group_id' => $groupId,
            'user_id' => $userId,
            'message_id' => $request->messageId,
        ]);

        if (!$seenMessage->exists) {
            $seenMessage->save();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        $messageId = $request->messageId;
        $messageText = $request->messageText;
        $userId = auth()->id();

        $message = Message::where('id', $messageId)->first();
        if ($message->user_id == $userId) {
            $message->text = $messageText;
            $message->edited = true;
            $message->save();
            event(new EditChatMessage($message));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Message $Message)
    {
        $userId = auth()->id();

        if ($Message->user_id == $userId) {
            event(new DeleteChatMessage($Message));

            $Message->deleted = true;
            $Message->save();
            $status = '200';
            $message = 'پیام با موفقیت حذف شد.';
        } else {
            $status = '403';
            $message = 'نمی‌توان پیام کاربر دیگری را حذف کرد.';
        }

        return response()->json([
            'message' => $message,
            'status' => $status,
        ]);
    }

    public function lastMessageSeenId(Complex $complex)
    {
        $group = Group::where('complex_id', $complex->id)->first();
        $userId = auth()->id();

        $lastMessage = SeenMessage::where('group_id', $group->id)
            ->where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastMessage) {
            return response()->json(['last_message_seen_id' => $lastMessage->message_id]);
        }

        $lastMessageOfGroup = Message::where('group_id', $group->id)
            ->where('deleted', false)
            ->orderBy('id', 'desc')
            ->first();

        return response()->json(['last_message_seen_id' => $lastMessageOfGroup->id]);
    }

    public function getMessagesBeforeFrom(Complex $complex, int $fromFirstMessageId)
    {
        $group = Group::where('complex_id', $complex->id)->first();

        $perPage = 10;

        $previousMessages = Message::where('group_id', $group->id)
            ->where('deleted', false)
            ->with('user')
            ->where('id', '<', $fromFirstMessageId)
            ->orderByDesc('id')
            ->take($perPage)
            ->get()
            ->reverse();


        $loggedInUserId = auth()->id();

        $finalMessages = [];
        foreach ($previousMessages as $message) {
            $isOwnedByLoggedInUser = ($message->user_id == $loggedInUserId);

            $messageData = [
                'message_id' => $message->id,
                'user_name' => $message->user->name,
                'user_id' => $message->user_id,
                'text' => $message->text,
                'image_url' => $message->image_url,
                'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                'is_deleted' => $message->deleted,
                'is_edited' => $message->edited,
                'is_owned_by_logged_in_user' => $isOwnedByLoggedInUser,
            ];

            $finalMessages[] = $messageData;
        }

        $firstMessageId = $previousMessages->isNotEmpty() ? $previousMessages->first()->id : $fromFirstMessageId;

        return response()->json([
            'messages' => $finalMessages,
            'first_message_id' => $firstMessageId,
        ]);
    }

    public function getMessagesAfterFrom(Complex $complex, int $fromLastMessageId)
    {
        $group = Group::where('complex_id', $complex->id)->first();

        $perPage = 10;

        $nextMessages = Message::where('group_id', $group->id)
            ->where('deleted', false)
            ->with('user')
            ->where('id', '>', $fromLastMessageId)
            ->orderBy('id')
            ->take($perPage)
            ->get();

        $loggedInUserId = auth()->id();


        $finalMessages = [];
        foreach ($nextMessages as $message) {
            $isOwnedByLoggedInUser = ($message->user_id == $loggedInUserId);

            $messageData = [
                'message_id' => $message->id,
                'user_name' => $message->user->name,
                'user_id' => $message->user_id,
                'text' => $message->text,
                'image_url' => $message->image_url,
                'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                'is_deleted' => $message->deleted,
                'is_edited' => $message->edited,
                'is_owned_by_logged_in_user' => $isOwnedByLoggedInUser,
            ];

            $finalMessages[] = $messageData;
        }

        $lastMessageId = $nextMessages->isNotEmpty() ? $nextMessages->last()->id : $fromLastMessageId;

        return response()->json([
            'messages' => $finalMessages,
            'last_message_id' => $lastMessageId,
        ]);
    }
}
