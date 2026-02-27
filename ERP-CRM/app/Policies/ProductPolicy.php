<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any products.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_products');
    }

    /**
     * Determine whether the user can view the product.
     *
     * @param User $user
     * @param Product $product
     * @return bool
     */
    public function view(User $user, Product $product): bool
    {
        return $this->checkPermission($user, 'view_products');
    }

    /**
     * Determine whether the user can create products.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_products');
    }

    /**
     * Determine whether the user can update the product.
     *
     * @param User $user
     * @param Product $product
     * @return bool
     */
    public function update(User $user, Product $product): bool
    {
        return $this->checkPermission($user, 'edit_products');
    }

    /**
     * Determine whether the user can delete the product.
     *
     * @param User $user
     * @param Product $product
     * @return bool
     */
    public function delete(User $user, Product $product): bool
    {
        return $this->checkPermission($user, 'delete_products');
    }
}
