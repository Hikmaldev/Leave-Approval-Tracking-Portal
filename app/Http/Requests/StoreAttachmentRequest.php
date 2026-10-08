<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAttachment;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    use ValidatesAttachment;

    /**
     * ACT-03: only the employee who owns the request may upload an attachment,
     * and only while the request is still pending (before a final decision).
     */
    public function authorize(): bool
    {
        $leaveRequest = $this->route('leaveRequest');

        return $this->user() !== null
            && $leaveRequest !== null
            && $leaveRequest->user_id === $this->user()->id
            && $leaveRequest->isPending();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'attachment' => $this->attachmentRules(required: true),
        ];
    }
}
