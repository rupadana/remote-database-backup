<?php

namespace App\Policies;

use App\Models\Checkpoint;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CheckpointPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_checkpoint');
    }

    public function view(User $user, Checkpoint $checkpoint): bool
    {
        return $user->can('view_checkpoint');
    }

    public function create(User $user): bool
    {
        return $user->can('create_checkpoint');
    }

    public function update(User $user, Checkpoint $checkpoint): bool
    {
        return $user->can('update_checkpoint');
    }

    public function delete(User $user, Checkpoint $checkpoint): bool
    {
        return $user->can('delete_checkpoint');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_checkpoint');
    }
}
