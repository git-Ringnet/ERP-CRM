<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

class SettingPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any settings.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_settings');
    }

    /**
     * Determine whether the user can view the setting.
     *
     * @param User $user
     * @param Setting $setting
     * @return bool
     */
    public function view(User $user, Setting $setting): bool
    {
        return $this->checkPermission($user, 'view_settings');
    }

    /**
     * Determine whether the user can update the setting.
     *
     * @param User $user
     * @param Setting $setting
     * @return bool
     */
    public function update(User $user, Setting $setting): bool
    {
        return $this->checkPermission($user, 'edit_settings');
    }
}
