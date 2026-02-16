<?php

namespace LLSHLM;

if (! defined('ABSPATH')) {
    exit;
}

class Plugin
{
    /** @var Roles */
    private $roles;

    /** @var Post_Types */
    private $post_types;

    /** @var Admin */
    private $admin;

    /** @var Rest */
    private $rest;

    public static function boot(): void
    {
        $instance = new self();
        $instance->hooks();
    }

    private function __construct()
    {
        $this->roles      = new Roles();
        $this->post_types = new Post_Types();
        $this->admin      = new Admin();
        $this->rest       = new Rest();
    }

    private function hooks(): void
    {
        register_activation_hook(LLSHLM_FILE, [$this->roles, 'activate']);
        register_activation_hook(LLSHLM_FILE, [$this->post_types, 'activate']);
        register_deactivation_hook(LLSHLM_FILE, [$this->roles, 'deactivate']);

        add_action('init', [$this->post_types, 'register']);
        add_action('admin_menu', [$this->admin, 'menu']);
        add_action('admin_post_llshlm_create_license', [$this->admin, 'create_license']);
        add_action('admin_post_llshlm_save_product', [$this->admin, 'save_product']);
        add_action('rest_api_init', [$this->rest, 'register_routes']);
    }
}
