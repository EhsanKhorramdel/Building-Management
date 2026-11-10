<?php

namespace App\Http\Controllers;

use App\Events\DeleteChatMessage;
use App\Events\EditChatMessage;
use App\Events\NewChatMessage;
use App\Http\Middleware\AccessMiddleware;
use App\Http\Requests\ChatEditRequest;
use App\Http\Requests\ChatSeenRequest;
use App\Http\Requests\ChatStoreRequest;
use App\Http\Requests\MessageViewersRequest;
use App\Models\Complex;
use App\Models\Group;
use App\Models\Message;
use App\Services\ChatService;
use App\Services\MessagePresenter;
use Illuminate\Http\JsonResponse;

class GroupController extends Controller
{
    public function __construct(private ChatService $chat)
    {
        $this->middleware(['auth', AccessMiddleware::class.':member']);
    }

    /**
     * @return JsonResponse Body is array{messages: list of MessagePresenter::present() payloads, first_message_id: int|null, last_message_id: int|null}.
     */
    public function index(Complex $complex, int $lastReceivedMessageId)
    {
        return response()->json($this->chat->fetch($this->group($complex), 'around', $lastReceivedMessageId, 10, auth()->id()));
    }

    /**
     * @return JsonResponse Body is array{messages: list of MessagePresenter::present() payloads, first_message_id: int}.
     */
    public function getMessagesBeforeFrom(Complex $complex, int $fromFirstMessageId)
    {
        return response()->json($this->chat->fetch($this->group($complex), 'before', $fromFirstMessageId, 10, auth()->id()));
    }

    /**
     * @return JsonResponse Body is array{messages: list of MessagePresenter::present() payloads, last_message_id: int}.
     */
    public function getMessagesAfterFrom(Complex $complex, int $fromLastMessageId)
    {
        return response()->json($this->chat->fetch($this->group($complex), 'after', $fromLastMessageId, 10, auth()->id()));
    }

    /**
     * @return JsonResponse Body is array{last_message_seen_id: int|null}.
     */
    public function lastMessageSeenId(Complex $complex)
    {
        return response()->json(['last_message_seen_id' => $this->chat->lastSeen($this->group($complex), auth()->id())]);
    }

    /**
     * @return JsonResponse Body is list<array{user_name: string, viewed_at: string|null}>.
     */
    public function getMessageViewers(MessageViewersRequest $request)
    {
        $message = Message::findOrFail($request->validated()['message_id']);
        $user = $request->user();

        abort_unless($message->user_id === $user->id || $user->can('manage', $message->group->complex), 403);

        return response()->json($this->chat->viewers($message->id));
    }

    public function store(ChatStoreRequest $request, MessagePresenter $presenter)
    {
        $data = $request->validated();
        if (($data['messageText'] ?? null) || isset($data['messageImage'])) {
            $message = $this->chat->store($data, auth()->id());
            event(new NewChatMessage($presenter->present($message)));
        }
    }

    public function seen(ChatSeenRequest $request)
    {
        $this->chat->seen($request->validated()['messageId'], auth()->id());
    }

    public function edit(ChatEditRequest $request)
    {
        $data = $request->validated();
        $message = Message::findOrFail($data['messageId']);
        if ($message->user_id == auth()->id()) {
            $message->text = $data['messageText'] ?? null;
            $message->edited = true;
            $message->save();
            event(new EditChatMessage($message));
        }
    }

    /**
     * @return JsonResponse Body is array{message: string, status: '200'|'403'}.
     */
    public function destroy(Message $Message)
    {
        if ($Message->user_id == auth()->id()) {
            event(new DeleteChatMessage($Message));
            $Message->deleted = true;
            $Message->save();
            $status = '200';
            $message = 'پیام با موفقیت حذف شد.';
        } else {
            $status = '403';
            $message = 'نمی‌توان پیام کاربر دیگری را حذف کرد.';
        }

        return response()->json(compact('message', 'status'));
    }

    private function group(Complex $complex): Group
    {
        return Group::where('complex_id', $complex->id)->firstOrFail();
    }
}
