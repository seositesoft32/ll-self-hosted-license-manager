<?php

namespace LLSHLM;

if (! defined('ABSPATH')) {
    exit;
}

class Post_Types
{
    public const PRODUCT = 'llshlm_product';
    public const LICENSE = 'llshlm_license';

    public function activate(): void
    {
        $this->register();
        flush_rewrite_rules();
    }

    public function register(): void
    {
        register_post_type(self::PRODUCT, [
            'label'           => __('Plugin Products', 'll-self-hosted-license-manager'),
            'public'          => false,
            'show_ui'         => false,
            'show_in_menu'    => false,
            'supports'        => ['title'],
            'capability_type' => 'post',
        ]);

        register_post_type(self::LICENSE, [
            'label'           => __('Plugin Licenses', 'll-self-hosted-license-manager'),
            'public'          => false,
            'show_ui'         => false,
            'show_in_menu'    => false,
            'supports'        => ['title'],
            'capability_type' => 'post',
        ]);
    }

    public static function create_license_key(): string
    {
        $parts = [
            strtoupper(substr(wp_generate_password(8, false, false), 0, 8)),
            strtoupper(substr(wp_generate_password(8, false, false), 0, 8)),
            strtoupper(substr(wp_generate_password(8, false, false), 0, 8)),
            strtoupper(substr(wp_generate_password(8, false, false), 0, 8)),
        ];

        return implode('-', $parts);
    }
}
