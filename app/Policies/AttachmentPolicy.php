<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

class AttachmentPolicy
{
    /**
     * Secured download (PRD 9.2): delegated to the parent request policy so
     * the ownership/role rule lives in exactly one place.
     */
    public function download(User $user, Attachment $attachment): bool
    {
        return $user->can('downloadAttachment', $attachment->leaveRequest);
    }
}
