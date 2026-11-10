<?php

namespace App\Http\Controllers;

use App\Events\ChatPinChanged;
use App\Events\ChatReactionsChanged;
use App\Events\DeleteChatMessage;
use App\Events\EditChatMessage;
use App\Events\NewChatMessage;
use App\Http\Middleware\AccessMiddleware;
use App\Http\Requests\ChatEditRequest;
use App\Http\Requests\ChatPinRequest;
use App\Http\Requests\ChatReactionRequest;
use App\Http\Requests\ChatSeenRequest;
use App\Http\Requests\ChatStoreRequest;
use App\Http\Requests\MessageViewersRequest;
use App\Models\Complex;
use App\Models\Group;
use App\Models\Message;
use App\Services\ChatService;
use App\Services\ChatSocialService;
use App\Services\MessagePresenter;
use Illuminate\Http\JsonResponse;

class GroupController extends Controller
{
    public function __construct(private ChatService $chat)
    {
        $this->middleware(['auth', AccessMiddleware::class.':member']);
        $this->middleware(AccessMiddleware::class.':manager')->only('pin');
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

    public function react(ChatReactionRequest $request, Message $Message, ChatSocialService $social): JsonResponse
    {
        $update = $social->toggleReaction($Message, $request->user()->id, $request->validated('emoji'));
        event(new ChatReactionsChanged($Message->group->complex_id, $Message->id));

        return response()->json($update);
    }

    public function pins(Complex $complex, ChatSocialService $social): JsonResponse
    {
        return response()->json($social->pins($this->group($complex)));
    }

    public function pin(ChatPinRequest $request, Message $Message, ChatSocialService $social): JsonResponse
    {
        $update = $social->setPin($Message, $request->user()->id, (bool) $request->validated('pinned'));
        event(new ChatPinChanged($Message->group->complex_id, $Message->id));

        return response()->json($update);
    }

    public function seen(ChatSeenRequest $request)
    {
        $this->chat->seen($request->validated()['messageId'], auth()->id());
    }

    public function edit(ChatEditRequest $request)
    {
        $data = $request->validated();
        $message = Message::findOrFail($data['messageId']);
        if ($message->user_id == auth()->id() && Message::whereKey($message->id)->where('deleted', false)
            ->update(['text' => $data['messageText'] ?? null, 'edited' => true])) {
            event(new EditChatMessage($message->refresh()));
        }
    }

    /**
     * @return JsonResponse Body is array{message: string, status: '200'|'403', deletedMessage?: array{id: int, deletion_kind: string}}.
     */
    public function destroy(Message $Message, ChatSocialService $social, MessagePresenter $presenter)
    {
        if ($Message->user_id == auth()->id()) {
            $deleted = $social->delete($Message, auth()->id());
            if ($deleted) {
                event(new DeleteChatMessage($deleted));
            }

            return response()->json([
                'status' => '200',
                'message' => 'پیام با موفقیت حذف شد.',
                'deletedMessage' => $presenter->deletion($deleted ?? $Message->refresh()),
            ]);
        }

        return response()->json(['status' => '403', 'message' => 'نمی‌توان پیام کاربر دیگری را حذف کرد.']);
    }

    private function group(Complex $complex): Group
    {
        return Group::where('complex_id', $complex->id)->orderBy('id')->firstOrFail();
    }
}
