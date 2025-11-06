# Polar Express WordPress Plugin

A custom WordPress extension designed to enhance manual order processing and delivery workflows for Polar's bulk ice cream service. This service involves pre-orders for events, where customers receive ice cream products bundled with rented refrigerators. Delivery occurs on the event day, and refrigerators are retrieved post-event.

## 1. Introduction

### 1.1 Project Overview

The Polar Express plugin is a custom WordPress extension designed to enhance manual order processing and delivery workflows for Polar's bulk ice cream service. This service involves pre-orders for events, where customers receive ice cream products bundled with rented refrigerators. Delivery occurs on the event day, and refrigerators are retrieved post-event. The plugin will streamline coordination among stakeholders (e.g., customer support, HR, delivery personnel, fridge providers, product distributors) by providing role-based dashboards, real-time task assignments, order manipulations, and tracking.

**Key goals:**

- Integrate seamlessly with existing systems: WordPress user management, WooCommerce for orders/customers/products, and EtherTech WOOTP for secure customer authentication.
- Leverage WooCommerce's free REST API and webhooks for core e-commerce data handling to minimize custom code.
- Use WordPress core capabilities for role management to ensure security and maintainability.
- Focus on usability for non-technical users, with admin-panel shortcodes for easy frontend access.

### 1.2 Assumptions and Scope

- WordPress/WooCommerce setup is self-hosted (no external hosting limits).
- WooCommerce version: 8.x or later (assumed; confirm if different).
- No advanced extensions (e.g., subscriptions) beyond core WooCommerce.
- Phase 1 focuses on requirements; this plan outlines Phases 2–4 for development, testing, and deployment.
- Budget/timeline not specified; estimated 4–6 weeks for MVP with 1–2 developers.

### 1.3 Success Metrics

- 80% reduction in manual email/SMS coordination (measured via A/B testing).
- 95% order processing accuracy (via task completion rates).
- Real-time sync latency <5 seconds.

## 2. System Overview

### 2.1 High-Level Architecture

- **Frontend**: WordPress admin panel with custom shortcodes for role-specific dashboards (e.g., `[polar_orders]` for lists, `[polar_assign]` for HR tasks). Mobile-responsive via CSS/JS.
- **Backend**: PHP-based plugin using WordPress hooks, WooCommerce REST API for data ops, and AJAX/WebSockets (via WordPress Heartbeat API) for real-time updates.
- **Database**: Extend WooCommerce tables (`wp_woocommerce_order_items`, `wp_posts` for orders) with custom meta (e.g., `_delivery_assignment`, `_fridge_retrieval_date`) via WordPress post meta.
- **Notifications**: WooCommerce emails + custom SMS (via external service like Twilio; integrate via API).
- **Security**: Nonce verification, capability checks, API key auth for REST calls.

### 2.2 User Flows Recap (Refined)

- **Customer Support**: View/manipulate orders, check stock, notify customers.
- **HR**: Assign roles/tasks to users.
- **Delivery Person**: View pending deliveries/collections, update status.
- **Fridge Provider**: Manage fridge rentals/returns.
- **Product Provider**: Track stock/expiry, fulfill orders.

## 3. Prerequisites

- Docker and Docker Compose installed
- Existing WordPress container running on port 8080 (container name: `ssl-wireless-wordpress`)
- Existing MySQL database container (container name: `ssl-wireless-db`)
- WordPress network: `ssl-wireless-sms-notification_wordpress-network`
- WooCommerce 8.x or later installed and activated in WordPress

## 4. Development Setup

### 4.1 Clone the Repository

```bash
git clone https://github.com/atiqisrak/pexpress.git
cd pexpress
```

### 4.2 Bind Plugin to WordPress Container

To enable real-time changes during development, you need to bind mount the plugin directory to your existing WordPress container.

#### Option 1: Using the bind script (Recommended)

```bash
./bind-plugin.sh
```

This script will:

- Stop the existing WordPress container
- Create a snapshot
- Restart the container with the plugin directory bound

#### Option 2: Manual binding

If you prefer to manually bind the plugin, you can use Docker's `--mount` flag when starting your container:

```bash
docker stop ssl-wireless-wordpress
docker rm ssl-wireless-wordpress
docker run -d \
    --name ssl-wireless-wordpress \
    --network ssl-wireless-sms-notification_wordpress-network \
    --mount type=bind,source="$(pwd)/pexpress",target="/var/www/html/wp-content/plugins/polar-express" \
    -p 8080:80 \
    wordpress:latest
```

### 4.3 Start Development Environment

Start the WP-CLI container for development tools:

```bash
docker-compose up -d wp-cli
```

### 4.4 Access WP-CLI

To use WP-CLI commands, exec into the container:

```bash
docker exec -it pexpress-wp-cli wp --info
```

Example commands:

```bash
# Activate the plugin
docker exec -it pexpress-wp-cli wp plugin activate polar-express

# Check plugin status
docker exec -it pexpress-wp-cli wp plugin list

# Flush rewrite rules
docker exec -it pexpress-wp-cli wp rewrite flush
```

## 5. Plugin Structure

```
pexpress/
├── polar-express.php     # Main plugin file
├── uninstall.php         # Uninstall cleanup
├── includes/             # Core functionality
│   └── class-pexpress-core.php
├── admin/                # Admin panel code
│   └── class-pexpress-admin.php
├── public/               # Frontend code
│   └── class-pexpress-public.php
├── assets/               # CSS, JS, images
│   ├── css/
│   ├── js/
│   └── images/
└── languages/            # Translation files
```

## 6. Development Workflow

### 6.1 Making Changes

1. Edit files in the `pexpress/` directory
2. Changes are reflected immediately in WordPress (if bind mount is active)
3. Refresh your WordPress admin or frontend to see changes

### 6.2 Testing

- Use WP-CLI container for command-line testing
- Access WordPress at `http://localhost:8080`
- Check WordPress debug logs if `WORDPRESS_DEBUG` is enabled

### 6.3 Code Standards

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Use proper nonce verification for all forms
- Implement capability checks for admin functions
- Sanitize and validate all user inputs

## 7. Building a Release

To create a release zip file for distribution:

```bash
./build-release.sh [version]
```

Example:

```bash
./build-release.sh 1.0.0
```

This will create a zip file in the `release/` directory: `polar-express-1.0.0.zip`

The zip file will contain a `polar-express/` directory (the WordPress plugin name) even though your local development directory is `pexpress/`.

The release script excludes:

- Development files (`.git`, `docker-compose.yml`, etc.)
- Dependencies (`node_modules`, `vendor`)
- Environment files (`.env`)
- Build artifacts

## 8. Environment Variables

Copy `.env.example` to `.env` and configure:

```env
WORDPRESS_DB_HOST=ssl-wireless-db
WORDPRESS_DB_USER=wordpress
WORDPRESS_DB_PASSWORD=wordpress
WORDPRESS_DB_NAME=wordpress
WORDPRESS_DEBUG=1
WORDPRESS_NETWORK=ssl-wireless-sms-notification_wordpress-network
WORDPRESS_CONTAINER=ssl-wireless-wordpress
```

## 9. Troubleshooting

### Plugin not appearing in WordPress

1. Check if the plugin directory is properly mounted:

   ```bash
   docker exec ssl-wireless-wordpress ls -la /var/www/html/wp-content/plugins/polar-express
   ```

2. Verify file permissions:
   ```bash
   docker exec ssl-wireless-wordpress chown -R www-data:www-data /var/www/html/wp-content/plugins/polar-express
   ```

### WP-CLI connection issues

1. Verify network connectivity:

   ```bash
   docker exec pexpress-wp-cli ping -c 3 ssl-wireless-db
   ```

2. Check database credentials match your existing setup

### Changes not reflecting

1. Ensure bind mount is active:

   ```bash
   docker inspect ssl-wireless-wordpress | grep -A 10 Mounts
   ```

2. Clear WordPress cache if using caching plugins
3. Check WordPress debug logs

## 10. Contributing

1. Create a feature branch
2. Make your changes
3. Test thoroughly
4. Submit a pull request

## 11. License

This project is licensed under the GPL v3 or later - see the [LICENSE](LICENSE) file for details.

## 12. Support

For issues and questions, please open an issue on GitHub.
