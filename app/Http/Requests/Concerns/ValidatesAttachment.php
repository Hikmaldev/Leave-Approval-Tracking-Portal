<?php

namespace App\Http\Requests\Concerns;

trait ValidatesAttachment
{
    /**
     * Validation rules for an uploaded leave attachment.
     *
     * NOTE (PRD open question #3): the allowed file types and the size limit
     * are not decided yet. These values are the current proposal; move them
     * to a config file once HR confirms the policy.
     *
     * @return array<int, string>
     */
    protected function attachmentRules(bool $required): array
    {
        return array_merge(
            $required ? ['required'] : ['nullable'],
            ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        );
    }
}
