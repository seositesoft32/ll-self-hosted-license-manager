# LL Self Hosted License Manager

Self-hosted WordPress plugin for:

- Managing plugin products with version-based metadata.
- Creating and validating customer licenses.
- Serving plugin update information and download links for licensed customers.

## Admin Features

- **License Manager → Products**
  - Product slug
  - Product name
  - Latest version
  - ZIP package URL
  - Requires WP / PHP / Tested up to
  - Changelog/info text

- **License Manager → Licenses**
  - Create license for customer email
  - Set validation years
  - Set max activation sites
  - View all licenses, expiry, product mapping, status

- **License Manager → My Licenses**
  - Customer role can view own licenses only

## User Role

- Role: `llshlm_customer` (Plugin Customer)
- Capability: `llshlm_view_licenses`

Admins can create licenses, and customer users can log in and see their own license list.

## REST API

Base URL:

`/wp-json/llshlm/v1`

### 1) Validate license

`POST /validate`

Fields:

- `license_code`
- `product_slug`
- `domain`
- `action` (`activate`, `check`, `deactivate`)

Response:

```json
{
  "success": true,
  "message": "License valid.",
  "data": {
    "source": "direct",
    "license_key": "XXXX-XXXX-XXXX-XXXX",
    "customer": "customer@example.com",
    "valid_until": "2027-02-16"
  }
}
```

### 2) Plugin info

`GET /plugin-info?product_slug=your-plugin&license_code=KEY`

Response `data` includes:

- `name`, `slug`, `version`
- `requires`, `requires_php`, `tested`
- `last_updated`
- `download_url`
- `sections.description`, `sections.changelog`

### 3) Download URL

`GET /download?product_slug=your-plugin&license_code=KEY`

Valid licenses are redirected to the configured product ZIP URL.

## Notes

- Use HTTPS for all endpoints.
- Keep ZIP URLs private/protected when possible.
- Client plugin updater should call `/plugin-info` and use returned `download_url`.
