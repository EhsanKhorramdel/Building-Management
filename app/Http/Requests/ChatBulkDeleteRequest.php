<?php

namespace App\Http\Requests;

use App\Services\ChatSocialService;
use Illuminate\Foundation\Http\FormRequest;

class ChatBulkDeleteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'message_ids' => 'required|array|min:1|max:'.ChatSocialService::BATCH_LIMIT,
            'message_ids.*' => 'required|integer|min:1|distinct',
            'reason' => 'nullable|string|max:240',
        ];
    }

    public function messages(): array
    {
        return [
            'message_ids.*' => 'حداکثر ۵۰ پیام متفاوت را انتخاب کنید.',
            'reason.*' => 'دلیل حذف پیام را حداکثر در ۲۴۰ نویسه وارد کنید.',
        ];
    }
}
