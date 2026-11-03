<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;

class MediaPolicy
{
    /**
     * Uploaders edit their own files; media managers edit everything.
     */
    public function update(User $user, Media $media): bool
    {
        $blog = $media->blog;

        return $blog !== null && (
            $user->can('media.manage', $blog)
            || ($user->can('media.upload', $blog) && $media->uploaded_by === $user->id)
        );
    }

    public function delete(User $user, Media $media): bool
    {
        return $this->update($user, $media);
    }
}
