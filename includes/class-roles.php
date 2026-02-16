<?php

namespace LLSHLM;

if (! defined('ABSPATH')) {
    exit;
}

class Roles
{
    public function activate(): void
    {
        add_role('llshlm_customer', __('Plugin Customer', 'll-self-hosted-license-manager'), [
            'read'                => true,
            'llshlm_view_licenses' => true,
        ]);

        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('llshlm_manage_products');
            $admin->add_cap('llshlm_manage_licenses');
            $admin->add_cap('llshlm_view_licenses');
        }
    }

    public function deactivate(): void
    {
    }
}
