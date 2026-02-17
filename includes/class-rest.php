<?php

namespace LLSHLM;

if (! defined('ABSPATH')) {
    exit;
}

class Rest
{
    public function register_routes(): void
    {
        register_rest_route('llshlm/v1', '/validate', [
            'methods'             => 'POST',
            'callback'            => [$this, 'validate_license'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('llshlm/v1', '/plugin-info', [
            'methods'             => 'GET',
            'callback'            => [$this, 'plugin_info'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('llshlm/v1', '/download', [
            'methods'             => 'GET',
            'callback'            => [$this, 'download_info'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function validate_license(\WP_REST_Request $request): \WP_REST_Response
    {
        $license_code = sanitize_text_field((string) $request->get_param('license_code'));
        $product_slug = sanitize_title((string) $request->get_param('product_slug'));
        $domain       = sanitize_text_field((string) $request->get_param('domain'));
        $action       = sanitize_text_field((string) $request->get_param('action'));

        if ('' === $license_code || '' === $product_slug || '' === $action) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Missing required fields.', 'll-self-hosted-license-manager'),
                'data'    => [],
            ], 400);
        }

        $license = $this->find_license($license_code);
        if (! $license) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('License not found.', 'll-self-hosted-license-manager'),
                'data'    => [],
            ], 404);
        }

        $product_id = (int) get_post_meta($license->ID, '_llshlm_product_id', true);
        $product    = get_post($product_id);
        $slug       = (string) get_post_meta($product_id, '_llshlm_slug', true);

        if (! $product || $slug !== $product_slug) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('License does not match this product.', 'll-self-hosted-license-manager'),
                'data'    => [],
            ], 403);
        }

        $status    = (string) get_post_meta($license->ID, '_llshlm_status', true);
        $expires   = (int) get_post_meta($license->ID, '_llshlm_expires_at', true);
        $max_sites = (int) get_post_meta($license->ID, '_llshlm_max_sites', true);
        $activation_data = get_post_meta($license->ID, '_llshlm_activations', true);
        $activations = is_array($activation_data) ? $activation_data : [];

        if ('active' !== $status) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('License is inactive.', 'll-self-hosted-license-manager'),
                'data'    => [],
            ], 403);
        }

        if ($expires > 0 && $expires < time()) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('License expired.', 'll-self-hosted-license-manager'),
                'data'    => [],
            ], 403);
        }

        if ('deactivate' === $action && '' !== $domain) {
            $activations = array_values(array_filter($activations, static function ($item) use ($domain) {
                return is_array($item) && ($item['domain'] ?? '') !== $domain;
            }));
            update_post_meta($license->ID, '_llshlm_activations', $activations);
        }

        if (in_array($action, ['activate', 'check'], true) && '' !== $domain) {
            $known_domains = wp_list_pluck($activations, 'domain');
            if (! in_array($domain, $known_domains, true)) {
                if (count($activations) >= $max_sites) {
                    return new \WP_REST_Response([
                        'success' => false,
                        'message' => __('Activation limit reached.', 'll-self-hosted-license-manager'),
                        'data'    => [],
                    ], 403);
                }

                $activations[] = [
                    'domain'      => $domain,
                    'activated_at'=> time(),
                ];
                update_post_meta($license->ID, '_llshlm_activations', $activations);
            }
        }

        return new \WP_REST_Response([
            'success' => true,
            'message' => __('License valid.', 'll-self-hosted-license-manager'),
            'data'    => [
                'source'      => 'direct',
                'license_key' => (string) $license->post_title,
                'customer'    => $this->customer_email((int) get_post_meta($license->ID, '_llshlm_customer_id', true)),
                'valid_until' => $expires > 0 ? gmdate('Y-m-d', $expires) : '',
            ],
        ]);
    }

    public function plugin_info(\WP_REST_Request $request): \WP_REST_Response
    {
        $slug         = sanitize_title((string) $request->get_param('product_slug'));
        $license_code = sanitize_text_field((string) $request->get_param('license_code'));

        if ('' === $slug || '' === $license_code) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Missing product slug or license code.', 'll-self-hosted-license-manager'),
                'data'    => [],
            ], 400);
        }

        $license = $this->find_license($license_code);
        if (! $license) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Invalid license.', 'll-self-hosted-license-manager'),
                'data'    => [],
            ], 403);
        }

        $product = $this->find_product_by_slug($slug);
        if (! $product) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Product not found.', 'll-self-hosted-license-manager'),
                'data'    => [],
            ], 404);
        }

        if ((int) get_post_meta($license->ID, '_llshlm_product_id', true) !== (int) $product->ID) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('License does not match this product.', 'll-self-hosted-license-manager'),
                'data'    => [],
            ], 403);
        }

        $version      = (string) get_post_meta($product->ID, '_llshlm_version', true);
        $requires     = (string) get_post_meta($product->ID, '_llshlm_requires', true);
        $requires_php = (string) get_post_meta($product->ID, '_llshlm_requires_php', true);
        $tested       = (string) get_post_meta($product->ID, '_llshlm_tested', true);
        $sections     = (string) get_post_meta($product->ID, '_llshlm_sections', true);
        $package_url  = add_query_arg([
            'product_slug' => $slug,
            'license_code' => rawurlencode($license_code),
        ], rest_url('llshlm/v1/download'));

        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Plugin info loaded.', 'll-self-hosted-license-manager'),
            'data'    => [
                'name'         => (string) $product->post_title,
                'slug'         => $slug,
                'version'      => $version,
                'tested'       => $tested,
                'requires'     => $requires,
                'requires_php' => $requires_php,
                'last_updated' => gmdate('Y-m-d H:i:s', strtotime((string) $product->post_modified_gmt ?: 'now')),
                'download_url' => esc_url_raw($package_url),
                'sections'     => [
                    'description' => $sections,
                    'changelog'   => $sections,
                ],
            ],
        ]);
    }

    public function download_info(\WP_REST_Request $request)
    {
        $slug         = sanitize_title((string) $request->get_param('product_slug'));
        $license_code = sanitize_text_field((string) $request->get_param('license_code'));

        if ('' === $slug || '' === $license_code) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Missing product slug or license code.', 'll-self-hosted-license-manager'),
                'data'    => [],
            ], 400);
        }

        $license = $this->find_license($license_code);
        $product = $this->find_product_by_slug($slug);
        if (! $license || ! $product) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Invalid license or product.', 'll-self-hosted-license-manager'),
                'data'    => [],
            ], 403);
        }

        if ((int) get_post_meta($license->ID, '_llshlm_product_id', true) !== (int) $product->ID) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('License does not match this product.', 'll-self-hosted-license-manager'),
                'data'    => [],
            ], 403);
        }

        $expires = (int) get_post_meta($license->ID, '_llshlm_expires_at', true);
        if ($expires > 0 && $expires < time()) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('License expired.', 'll-self-hosted-license-manager'),
                'data'    => [],
            ], 403);
        }

        $package = (string) get_post_meta($product->ID, '_llshlm_package_url', true);
        if ('' === $package) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Package URL not configured.', 'll-self-hosted-license-manager'),
                'data'    => [],
            ], 500);
        }

        $local_file = $this->local_file_from_url($package);
        if ('' !== $local_file) {
            $served = $this->serve_zip_file($local_file);
            if ($served) {
                exit;
            }
        }

        wp_safe_redirect(esc_url_raw($package), 302);
        exit;
    }

    private function local_file_from_url(string $url): string
    {
        $url = esc_url_raw($url);
        if ('' === $url) {
            return '';
        }

        $uploads = wp_upload_dir();
        $baseurl = (string) ($uploads['baseurl'] ?? '');
        $basedir = (string) ($uploads['basedir'] ?? '');

        if ('' !== $baseurl && '' !== $basedir && 0 === strpos($url, $baseurl)) {
            $relative = ltrim(substr($url, strlen($baseurl)), '/\\');
            $path     = wp_normalize_path(trailingslashit($basedir) . $relative);

            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return '';
    }

    private function serve_zip_file(string $path): bool
    {
        $path = wp_normalize_path($path);
        if ('' === $path || ! is_file($path) || ! is_readable($path)) {
            return false;
        }

        if (function_exists('nocache_headers')) {
            nocache_headers();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Length: ' . (string) filesize($path));

        $handle = fopen($path, 'rb');
        if (false === $handle) {
            return false;
        }

        while (! feof($handle)) {
            $buffer = fread($handle, 8192);
            if (false === $buffer) {
                break;
            }
            echo $buffer;
        }

        fclose($handle);
        return true;
    }

    private function find_license(string $code): ?\WP_Post
    {
        $licenses = get_posts([
            'post_type'      => Post_Types::LICENSE,
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'title'          => $code,
        ]);

        if (! $licenses) {
            return null;
        }

        return $licenses[0] instanceof \WP_Post ? $licenses[0] : null;
    }

    private function find_product_by_slug(string $slug): ?\WP_Post
    {
        $products = get_posts([
            'post_type'      => Post_Types::PRODUCT,
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_key'       => '_llshlm_slug',
            'meta_value'     => $slug,
        ]);

        if (! $products) {
            return null;
        }

        return $products[0] instanceof \WP_Post ? $products[0] : null;
    }

    private function customer_email(int $user_id): string
    {
        $user = get_user_by('ID', $user_id);
        return $user ? (string) $user->user_email : '';
    }
}
