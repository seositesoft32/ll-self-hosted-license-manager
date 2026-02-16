<?php

namespace LLSHLM;

if (! defined('ABSPATH')) {
    exit;
}

class Admin
{
    public function menu(): void
    {
        add_menu_page(
            __('License Manager', 'll-self-hosted-license-manager'),
            __('License Manager', 'll-self-hosted-license-manager'),
            'llshlm_manage_products',
            'llshlm-dashboard',
            [$this, 'dashboard_page'],
            'dashicons-shield',
            58
        );

        add_submenu_page(
            'llshlm-dashboard',
            __('Products', 'll-self-hosted-license-manager'),
            __('Products', 'll-self-hosted-license-manager'),
            'llshlm_manage_products',
            'llshlm-products',
            [$this, 'products_page']
        );

        add_submenu_page(
            'llshlm-dashboard',
            __('Licenses', 'll-self-hosted-license-manager'),
            __('Licenses', 'll-self-hosted-license-manager'),
            'llshlm_manage_licenses',
            'llshlm-licenses',
            [$this, 'licenses_page']
        );

        add_submenu_page(
            'llshlm-dashboard',
            __('My Licenses', 'll-self-hosted-license-manager'),
            __('My Licenses', 'll-self-hosted-license-manager'),
            'llshlm_view_licenses',
            'llshlm-my-licenses',
            [$this, 'my_licenses_page']
        );
    }

    public function dashboard_page(): void
    {
        if (! current_user_can('llshlm_manage_products')) {
            wp_die(esc_html__('Unauthorized request.', 'll-self-hosted-license-manager'));
        }

        $products = wp_count_posts(Post_Types::PRODUCT);
        $licenses = wp_count_posts(Post_Types::LICENSE);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Self Hosted License Manager', 'll-self-hosted-license-manager'); ?></h1>
            <p><?php esc_html_e('Manage products, license keys, and self-hosted plugin updates.', 'll-self-hosted-license-manager'); ?></p>

            <table class="widefat striped" style="max-width:720px;">
                <tbody>
                    <tr>
                        <th><?php esc_html_e('Products', 'll-self-hosted-license-manager'); ?></th>
                        <td><?php echo esc_html((string) ((int) ($products->publish ?? 0))); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Licenses', 'll-self-hosted-license-manager'); ?></th>
                        <td><?php echo esc_html((string) ((int) ($licenses->publish ?? 0))); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('REST Base', 'll-self-hosted-license-manager'); ?></th>
                        <td><?php echo esc_html((string) rest_url('llshlm/v1')); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function products_page(): void
    {
        if (! current_user_can('llshlm_manage_products')) {
            wp_die(esc_html__('Unauthorized request.', 'll-self-hosted-license-manager'));
        }

        $products = get_posts([
            'post_type'      => Post_Types::PRODUCT,
            'post_status'    => 'publish',
            'posts_per_page' => 200,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Products', 'll-self-hosted-license-manager'); ?></h1>

            <?php $this->print_notice(); ?>

            <h2><?php esc_html_e('Add / Update Product', 'll-self-hosted-license-manager'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field('llshlm_save_product'); ?>
                <input type="hidden" name="action" value="llshlm_save_product" />

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="llshlm_slug"><?php esc_html_e('Product Slug', 'll-self-hosted-license-manager'); ?></label></th>
                        <td><input id="llshlm_slug" class="regular-text" name="slug" required pattern="[a-z0-9\-]+" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="llshlm_name"><?php esc_html_e('Product Name', 'll-self-hosted-license-manager'); ?></label></th>
                        <td><input id="llshlm_name" class="regular-text" name="name" required /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="llshlm_version"><?php esc_html_e('Latest Version', 'll-self-hosted-license-manager'); ?></label></th>
                        <td><input id="llshlm_version" class="regular-text" name="version" placeholder="1.0.0" required /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="llshlm_package_zip"><?php esc_html_e('ZIP Package File', 'll-self-hosted-license-manager'); ?></label></th>
                        <td>
                            <input id="llshlm_package_zip" type="file" name="package_zip" accept=".zip,application/zip" />
                            <p class="description"><?php esc_html_e('Upload the plugin ZIP file. It will be stored in WordPress uploads and used by updater clients.', 'll-self-hosted-license-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="llshlm_requires"><?php esc_html_e('Requires WP', 'll-self-hosted-license-manager'); ?></label></th>
                        <td><input id="llshlm_requires" class="regular-text" name="requires" placeholder="6.0" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="llshlm_requires_php"><?php esc_html_e('Requires PHP', 'll-self-hosted-license-manager'); ?></label></th>
                        <td><input id="llshlm_requires_php" class="regular-text" name="requires_php" placeholder="7.4" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="llshlm_tested"><?php esc_html_e('Tested Up To', 'll-self-hosted-license-manager'); ?></label></th>
                        <td><input id="llshlm_tested" class="regular-text" name="tested" placeholder="6.8" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="llshlm_sections"><?php esc_html_e('Info / Changelog', 'll-self-hosted-license-manager'); ?></label></th>
                        <td><textarea id="llshlm_sections" name="sections" rows="6" class="large-text"></textarea></td>
                    </tr>
                </table>

                <?php submit_button(__('Save Product', 'll-self-hosted-license-manager')); ?>
            </form>

            <h2><?php esc_html_e('Product List (Version Based)', 'll-self-hosted-license-manager'); ?></h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Name', 'll-self-hosted-license-manager'); ?></th>
                        <th><?php esc_html_e('Slug', 'll-self-hosted-license-manager'); ?></th>
                        <th><?php esc_html_e('Version', 'll-self-hosted-license-manager'); ?></th>
                        <th><?php esc_html_e('ZIP URL', 'll-self-hosted-license-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products) : ?>
                        <?php foreach ($products as $product) : ?>
                            <?php $slug = (string) get_post_meta($product->ID, '_llshlm_slug', true); ?>
                            <?php $version = (string) get_post_meta($product->ID, '_llshlm_version', true); ?>
                            <?php $package = (string) get_post_meta($product->ID, '_llshlm_package_url', true); ?>
                            <tr>
                                <td><?php echo esc_html((string) $product->post_title); ?></td>
                                <td><?php echo esc_html($slug); ?></td>
                                <td><?php echo esc_html($version); ?></td>
                                <td><a href="<?php echo esc_url($package); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($package); ?></a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4"><?php esc_html_e('No products found.', 'll-self-hosted-license-manager'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function licenses_page(): void
    {
        if (! current_user_can('llshlm_manage_licenses')) {
            wp_die(esc_html__('Unauthorized request.', 'll-self-hosted-license-manager'));
        }

        $products = get_posts([
            'post_type'      => Post_Types::PRODUCT,
            'post_status'    => 'publish',
            'posts_per_page' => 200,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);

        $licenses = get_posts([
            'post_type'      => Post_Types::LICENSE,
            'post_status'    => 'publish',
            'posts_per_page' => 300,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Licenses', 'll-self-hosted-license-manager'); ?></h1>

            <?php $this->print_notice(); ?>

            <h2><?php esc_html_e('Create License for Customer', 'll-self-hosted-license-manager'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('llshlm_create_license'); ?>
                <input type="hidden" name="action" value="llshlm_create_license" />

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="llshlm_customer_email"><?php esc_html_e('Customer Email', 'll-self-hosted-license-manager'); ?></label></th>
                        <td><input id="llshlm_customer_email" class="regular-text" type="email" name="customer_email" required /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="llshlm_product"><?php esc_html_e('Product', 'll-self-hosted-license-manager'); ?></label></th>
                        <td>
                            <select id="llshlm_product" name="product_id" required>
                                <option value=""><?php esc_html_e('Select product', 'll-self-hosted-license-manager'); ?></option>
                                <?php foreach ($products as $product) : ?>
                                    <?php $slug = (string) get_post_meta($product->ID, '_llshlm_slug', true); ?>
                                    <option value="<?php echo esc_attr((string) $product->ID); ?>"><?php echo esc_html((string) $product->post_title . ' (' . $slug . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="llshlm_validation_years"><?php esc_html_e('Validation Years', 'll-self-hosted-license-manager'); ?></label></th>
                        <td><input id="llshlm_validation_years" type="number" min="1" max="20" name="validation_years" value="1" required /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="llshlm_max_sites"><?php esc_html_e('Max Sites', 'll-self-hosted-license-manager'); ?></label></th>
                        <td><input id="llshlm_max_sites" type="number" min="1" max="1000" name="max_sites" value="1" required /></td>
                    </tr>
                </table>

                <?php submit_button(__('Create License', 'll-self-hosted-license-manager')); ?>
            </form>

            <h2><?php esc_html_e('All Licenses', 'll-self-hosted-license-manager'); ?></h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('License Key', 'll-self-hosted-license-manager'); ?></th>
                        <th><?php esc_html_e('Customer', 'll-self-hosted-license-manager'); ?></th>
                        <th><?php esc_html_e('Product', 'll-self-hosted-license-manager'); ?></th>
                        <th><?php esc_html_e('Validation Year', 'll-self-hosted-license-manager'); ?></th>
                        <th><?php esc_html_e('Expires', 'll-self-hosted-license-manager'); ?></th>
                        <th><?php esc_html_e('Status', 'll-self-hosted-license-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($licenses) : ?>
                        <?php foreach ($licenses as $license) : ?>
                            <?php
                            $customer_id = (int) get_post_meta($license->ID, '_llshlm_customer_id', true);
                            $product_id  = (int) get_post_meta($license->ID, '_llshlm_product_id', true);
                            $years       = (int) get_post_meta($license->ID, '_llshlm_validation_years', true);
                            $expires_at  = (int) get_post_meta($license->ID, '_llshlm_expires_at', true);
                            $status      = (string) get_post_meta($license->ID, '_llshlm_status', true);

                            $customer = get_user_by('ID', $customer_id);
                            $product  = get_post($product_id);
                            ?>
                            <tr>
                                <td><?php echo esc_html((string) $license->post_title); ?></td>
                                <td><?php echo esc_html((string) ($customer ? $customer->user_email : '-')); ?></td>
                                <td><?php echo esc_html((string) ($product ? $product->post_title : '-')); ?></td>
                                <td><?php echo esc_html((string) $years); ?></td>
                                <td><?php echo esc_html($expires_at > 0 ? wp_date('Y-m-d', $expires_at) : '-'); ?></td>
                                <td><?php echo esc_html($status ?: 'active'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="6"><?php esc_html_e('No licenses found.', 'll-self-hosted-license-manager'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function my_licenses_page(): void
    {
        if (! current_user_can('llshlm_view_licenses')) {
            wp_die(esc_html__('Unauthorized request.', 'll-self-hosted-license-manager'));
        }

        $current_user_id = get_current_user_id();
        $query_args      = [
            'post_type'      => Post_Types::LICENSE,
            'post_status'    => 'publish',
            'posts_per_page' => 200,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        if (! current_user_can('llshlm_manage_licenses')) {
            $query_args['meta_query'] = [
                [
                    'key'   => '_llshlm_customer_id',
                    'value' => $current_user_id,
                ],
            ];
        }

        $licenses = get_posts($query_args);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('My Licenses', 'll-self-hosted-license-manager'); ?></h1>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('License Key', 'll-self-hosted-license-manager'); ?></th>
                        <th><?php esc_html_e('Product', 'll-self-hosted-license-manager'); ?></th>
                        <th><?php esc_html_e('Validation Year', 'll-self-hosted-license-manager'); ?></th>
                        <th><?php esc_html_e('Expires', 'll-self-hosted-license-manager'); ?></th>
                        <th><?php esc_html_e('Status', 'll-self-hosted-license-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($licenses) : ?>
                        <?php foreach ($licenses as $license) : ?>
                            <?php
                            $product_id = (int) get_post_meta($license->ID, '_llshlm_product_id', true);
                            $years      = (int) get_post_meta($license->ID, '_llshlm_validation_years', true);
                            $expires_at = (int) get_post_meta($license->ID, '_llshlm_expires_at', true);
                            $status     = (string) get_post_meta($license->ID, '_llshlm_status', true);
                            $product    = get_post($product_id);
                            ?>
                            <tr>
                                <td><?php echo esc_html((string) $license->post_title); ?></td>
                                <td><?php echo esc_html((string) ($product ? $product->post_title : '-')); ?></td>
                                <td><?php echo esc_html((string) $years); ?></td>
                                <td><?php echo esc_html($expires_at > 0 ? wp_date('Y-m-d', $expires_at) : '-'); ?></td>
                                <td><?php echo esc_html($status ?: 'active'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5"><?php esc_html_e('No licenses found.', 'll-self-hosted-license-manager'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function save_product(): void
    {
        if (! current_user_can('llshlm_manage_products')) {
            wp_die(esc_html__('Unauthorized request.', 'll-self-hosted-license-manager'));
        }

        check_admin_referer('llshlm_save_product');

        $slug         = sanitize_title((string) wp_unslash($_POST['slug'] ?? ''));
        $name         = sanitize_text_field((string) wp_unslash($_POST['name'] ?? ''));
        $version      = sanitize_text_field((string) wp_unslash($_POST['version'] ?? ''));
        $package_url  = '';
        $requires     = sanitize_text_field((string) wp_unslash($_POST['requires'] ?? ''));
        $requires_php = sanitize_text_field((string) wp_unslash($_POST['requires_php'] ?? ''));
        $tested       = sanitize_text_field((string) wp_unslash($_POST['tested'] ?? ''));
        $sections     = wp_kses_post((string) wp_unslash($_POST['sections'] ?? ''));

        if ('' === $slug || '' === $name || '' === $version) {
            $this->redirect('llshlm-products', __('Please fill all required product fields.', 'll-self-hosted-license-manager'), false);
        }

        $has_upload = isset($_FILES['package_zip']) && is_array($_FILES['package_zip']) && ! empty($_FILES['package_zip']['name']);
        if ($has_upload) {
            if (! function_exists('wp_handle_upload')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }

            $uploaded = wp_handle_upload(
                $_FILES['package_zip'],
                [
                    'test_form' => false,
                    'mimes'     => [
                        'zip' => 'application/zip',
                    ],
                ]
            );

            if (isset($uploaded['error'])) {
                $this->redirect('llshlm-products', __('ZIP upload failed: ', 'll-self-hosted-license-manager') . sanitize_text_field((string) $uploaded['error']), false);
            }

            $package_url = esc_url_raw((string) ($uploaded['url'] ?? ''));
        }

        $existing = get_posts([
            'post_type'      => Post_Types::PRODUCT,
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_key'       => '_llshlm_slug',
            'meta_value'     => $slug,
        ]);

        if ($existing) {
            $product_id = (int) $existing[0]->ID;
            if ('' === $package_url) {
                $package_url = (string) get_post_meta($product_id, '_llshlm_package_url', true);
            }
            wp_update_post([
                'ID'         => $product_id,
                'post_title' => $name,
            ]);
        } else {
            if ('' === $package_url) {
                $this->redirect('llshlm-products', __('Please upload a ZIP package file.', 'll-self-hosted-license-manager'), false);
            }

            $product_id = wp_insert_post([
                'post_type'   => Post_Types::PRODUCT,
                'post_title'  => $name,
                'post_status' => 'publish',
            ]);
        }

        if (! $product_id || is_wp_error($product_id)) {
            $this->redirect('llshlm-products', __('Unable to save product.', 'll-self-hosted-license-manager'), false);
        }

        update_post_meta($product_id, '_llshlm_slug', $slug);
        update_post_meta($product_id, '_llshlm_version', $version);
        update_post_meta($product_id, '_llshlm_package_url', $package_url);
        update_post_meta($product_id, '_llshlm_requires', $requires);
        update_post_meta($product_id, '_llshlm_requires_php', $requires_php);
        update_post_meta($product_id, '_llshlm_tested', $tested);
        update_post_meta($product_id, '_llshlm_sections', $sections);

        $this->redirect('llshlm-products', __('Product saved.', 'll-self-hosted-license-manager'), true);
    }

    public function create_license(): void
    {
        if (! current_user_can('llshlm_manage_licenses')) {
            wp_die(esc_html__('Unauthorized request.', 'll-self-hosted-license-manager'));
        }

        check_admin_referer('llshlm_create_license');

        $email           = sanitize_email((string) wp_unslash($_POST['customer_email'] ?? ''));
        $product_id      = (int) wp_unslash($_POST['product_id'] ?? 0);
        $validation_years = (int) wp_unslash($_POST['validation_years'] ?? 1);
        $max_sites       = (int) wp_unslash($_POST['max_sites'] ?? 1);

        if ('' === $email || $product_id <= 0 || $validation_years <= 0 || $max_sites <= 0) {
            $this->redirect('llshlm-licenses', __('Please provide valid license data.', 'll-self-hosted-license-manager'), false);
        }

        $user = get_user_by('email', $email);
        if (! $user) {
            $password = wp_generate_password(16, true, true);
            $user_id  = wp_create_user($email, $password, $email);
            if (is_wp_error($user_id)) {
                $this->redirect('llshlm-licenses', __('Unable to create customer user.', 'll-self-hosted-license-manager'), false);
            }

            $user = get_user_by('ID', (int) $user_id);
            if ($user) {
                $user->set_role('llshlm_customer');
            }
        }

        if (! $user) {
            $this->redirect('llshlm-licenses', __('Customer account not found.', 'll-self-hosted-license-manager'), false);
        }

        $key       = Post_Types::create_license_key();
        $created   = time();
        $expires   = strtotime('+' . $validation_years . ' year', $created);

        $license_id = wp_insert_post([
            'post_type'   => Post_Types::LICENSE,
            'post_title'  => $key,
            'post_status' => 'publish',
        ]);

        if (! $license_id || is_wp_error($license_id)) {
            $this->redirect('llshlm-licenses', __('Unable to create license.', 'll-self-hosted-license-manager'), false);
        }

        update_post_meta($license_id, '_llshlm_customer_id', (int) $user->ID);
        update_post_meta($license_id, '_llshlm_product_id', $product_id);
        update_post_meta($license_id, '_llshlm_validation_years', $validation_years);
        update_post_meta($license_id, '_llshlm_created_at', $created);
        update_post_meta($license_id, '_llshlm_expires_at', (int) $expires);
        update_post_meta($license_id, '_llshlm_max_sites', $max_sites);
        update_post_meta($license_id, '_llshlm_status', 'active');
        update_post_meta($license_id, '_llshlm_activations', []);

        $this->redirect('llshlm-licenses', __('License created successfully.', 'll-self-hosted-license-manager'), true);
    }

    private function print_notice(): void
    {
        if (! isset($_GET['llshlm_notice'])) {
            return;
        }

        $notice = sanitize_text_field((string) wp_unslash($_GET['llshlm_notice']));
        $class  = isset($_GET['llshlm_success']) ? 'notice-success' : 'notice-error';
        ?>
        <div class="notice <?php echo esc_attr($class); ?> is-dismissible"><p><?php echo esc_html($notice); ?></p></div>
        <?php
    }

    private function redirect(string $page, string $notice, bool $success): void
    {
        $url = add_query_arg([
            'page'           => $page,
            'llshlm_notice'  => $notice,
            'llshlm_success' => $success ? '1' : null,
        ], admin_url('admin.php'));

        wp_safe_redirect($url);
        exit;
    }
}
