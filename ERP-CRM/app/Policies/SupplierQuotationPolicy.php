<?php

namespace App\Policies;

use App\Models\SupplierQuotation;
use App\Models\User;

class SupplierQuotationPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_supplier_quotations');
    }

    public function view(User $user, SupplierQuotation $supplierQuotation): bool
    {
        return $this->checkPermission($user, 'view_supplier_quotations');
    }

    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_supplier_quotations');
    }

    public function update(User $user, SupplierQuotation $supplierQuotation): bool
    {
        return $this->checkPermission($user, 'edit_supplier_quotations');
    }

    public function delete(User $user, SupplierQuotation $supplierQuotation): bool
    {
        return $this->checkPermission($user, 'delete_supplier_quotations');
    }

    public function export(User $user): bool
    {
        return $this->checkPermission($user, 'export_supplier_quotations');
    }
}
