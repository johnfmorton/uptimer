<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Monitor;
use App\Models\User;

class MonitorPolicy
{
    /**
     * Determine whether the user can view any models.
     *
     * @param  User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  User  $user
     * @param  Monitor  $monitor
     * @return bool
     */
    public function view(User $user, Monitor $monitor): bool
    {
        return $user->id === $monitor->user_id;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  User  $user
     * @param  Monitor  $monitor
     * @return bool
     */
    public function update(User $user, Monitor $monitor): bool
    {
        return $user->id === $monitor->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  User  $user
     * @param  Monitor  $monitor
     * @return bool
     */
    public function delete(User $user, Monitor $monitor): bool
    {
        return $user->id === $monitor->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  User  $user
     * @param  Monitor  $monitor
     * @return bool
     */
    public function restore(User $user, Monitor $monitor): bool
    {
        return $user->id === $monitor->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  User  $user
     * @param  Monitor  $monitor
     * @return bool
     */
    public function forceDelete(User $user, Monitor $monitor): bool
    {
        return $user->id === $monitor->user_id;
    }
}
