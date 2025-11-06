# Setup Guide: Polar Express Plugin with Docker

This guide will help you set up the Polar Express plugin on your WordPress container and enable real-time development.

## Fresh Start (Recommended for New Setup)

If you want to start fresh with a clean WordPress environment:

```bash
# 1. Clean up old containers
./cleanup-old-containers.sh

# 2. Set up fresh WordPress with both plugins
./setup-fresh-wordpress.sh
```

This will create:

- Fresh WordPress container
- Fresh MySQL database container
- Both Polar Express and EtherTech WOOTP plugins bound for development
- Everything configured and ready to use

## Prerequisites

- WordPress container running (`ssl-wireless-wordpress` on port 8080)
- Docker and Docker Compose installed
- Plugin directory at `/Users/atiqisrak/myspace/WebProjects/Wordpress-Plugins/pexpress`

## Step 0: Check WordPress Status

**IMPORTANT:** Always check if WordPress is running before binding plugins:

```bash
./check-wordpress.sh
```

This script will:

- Check if Docker is running
- Check if the WordPress container exists
- Start the container if it's stopped
- Show container status and mounts

**If WordPress is not running, the script will start it automatically.**

**Note:** If WordPress is running on a different port (not 8080), you'll see a warning. To fix the port, run:

```bash
./fix-wordpress-port.sh
```

**If WordPress is not accessible or having issues, you can safely recreate it:**

```bash
./recreate-wordpress-safe.sh
```

⚠️ **This is SAFE** - it preserves ALL your data:

- ✅ Themes
- ✅ Plugins (except bind-mounted development plugins)
- ✅ Settings and configurations
- ✅ Uploads and media files
- ✅ Database connections
- ✅ All WordPress data

The script only recreates the container, not your data. Your data is stored in Docker volumes which persist separately.

## Step 1: Bind the Plugins to WordPress Container

**⚠️ SAFE METHOD (Recommended):** Use the safe script that preserves all WordPress data:

```bash
./bind-plugins-safe.sh
```

This script:

- ✅ Preserves all your themes, plugins, and settings
- ✅ Preserves all data volumes
- ✅ Only adds the plugin bind mounts
- ✅ Asks for confirmation before making changes

**Alternative methods:**

```bash
# Standard bind script
./bind-plugin.sh

# Or dedicated script for both plugins
./bind-all-plugins.sh
```

**⚠️ IMPORTANT:** All bind scripts need to recreate the container to add mounts, but your WordPress data (themes, plugins, settings) is safe because it's stored in Docker volumes, not in the container itself.

Run the bind script to mount both Polar Express and EtherTech WOOTP plugins to the WordPress container:

```bash
./bind-plugin.sh
```

Or use the dedicated script for both plugins:

```bash
./bind-all-plugins.sh
```

This script will:

1. Stop the WordPress container
2. Create a snapshot
3. Restart the container with the plugin directory bound for real-time changes

**What happens:**

- Your local `pexpress/` directory is mounted to `/var/www/html/wp-content/plugins/polar-express/` in the container
- Your local `ethertech-wootp/` directory is mounted to `/var/www/html/wp-content/plugins/ethertech-wootp/` in the container
- Any changes you make locally to either plugin will immediately reflect in WordPress (no need to rebuild/restart)

## Step 2: Verify the Plugins are Mounted

Check if both plugins are properly mounted:

```bash
# Check Polar Express
docker exec ssl-wireless-wordpress ls -la /var/www/html/wp-content/plugins/polar-express

# Check EtherTech WOOTP
docker exec ssl-wireless-wordpress ls -la /var/www/html/wp-content/plugins/ethertech-wootp
```

You should see your plugin files listed for both plugins.

## Step 3: Activate the Plugin in WordPress

### Option A: Using WP-CLI (Recommended)

Start the WP-CLI container:

```bash
docker-compose up -d wp-cli
```

Then activate the plugin:

```bash
docker exec -it pexpress-wp-cli wp plugin activate polar-express
```

### Option B: Using WordPress Admin

1. Go to `http://localhost:8080/wp-admin`
2. Navigate to **Plugins** → **Installed Plugins**
3. Find **Polar Express** and click **Activate**

## Step 4: Verify Plugin Activation

Check plugin status:

```bash
docker exec -it pexpress-wp-cli wp plugin list | grep polar-express
```

You should see it as "active".

## Step 5: Create Dashboard Pages

Create pages in WordPress for each role dashboard:

### Using WP-CLI:

```bash
# HR Dashboard
docker exec -it pexpress-wp-cli wp post create --post_type=page --post_title="HR Dashboard" --post_content="[polar_hr]" --post_status=publish

# Delivery Dashboard
docker exec -it pexpress-wp-cli wp post create --post_type=page --post_title="My Deliveries" --post_content="[polar_delivery]" --post_status=publish

# Fridge Dashboard
docker exec -it pexpress-wp-cli wp post create --post_type=page --post_title="Fridge Tasks" --post_content="[polar_fridge]" --post_status=publish

# Distributor Dashboard
docker exec -it pexpress-wp-cli wp post create --post_type=page --post_title="Distribution Tasks" --post_content="[polar_distributor]" --post_status=publish

# Support Dashboard
docker exec -it pexpress-wp-cli wp post create --post_type=page --post_title="Support Dashboard" --post_content="[polar_support]" --post_status=publish
```

### Using WordPress Admin:

1. Go to **Pages** → **Add New**
2. Set the title (e.g., "HR Dashboard")
3. Add the shortcode in the content: `[polar_hr]`
4. Publish the page

## Step 6: Create Test Users

Create users with different roles for testing:

```bash
# Create HR user
docker exec -it pexpress-wp-cli wp user create hr_user hr@example.com --role=polar_hr --user_pass=password123

# Create Delivery user
docker exec -it pexpress-wp-cli wp user create delivery_user delivery@example.com --role=polar_delivery --user_pass=password123

# Create Fridge Provider user
docker exec -it pexpress-wp-cli wp user create fridge_user fridge@example.com --role=polar_fridge --user_pass=password123

# Create Distributor user
docker exec -it pexpress-wp-cli wp user create distributor_user distributor@example.com --role=polar_distributor --user_pass=password123

# Create Support user
docker exec -it pexpress-wp-cli wp user create support_user support@example.com --role=polar_support --user_pass=password123
```

## Step 7: Configure SMS (Optional)

If you want to use SMS notifications, set the credentials:

```bash
docker exec -it pexpress-wp-cli wp option update polar_sms_user "YOUR_SSLCOMMERZ_USER"
docker exec -it pexpress-wp-cli wp option update polar_sms_pass "YOUR_SSLCOMMERZ_PASS"
docker exec -it pexpress-wp-cli wp option update polar_sms_sid "POLARICE"
```

Or set them in WordPress admin (if you add an admin settings page later).

## Step 8: Real-Time Development Workflow

### Making Changes

1. **Edit files locally** in the `pexpress/` directory
2. **Changes are immediately visible** - no restart needed!
3. **Refresh WordPress** to see changes

### Example Workflow

```bash
# Edit a template file
code pexpress/templates/hr-dashboard.php

# Make changes and save
# Changes are immediately live in the container!

# Check logs if needed
docker logs ssl-wireless-wordpress --tail 50
```

### Clear WordPress Cache (if using caching)

```bash
docker exec -it pexpress-wp-cli wp cache flush
```

## Troubleshooting

### Plugin not appearing

1. **Check mount:**

   ```bash
   docker exec ssl-wireless-wordpress ls -la /var/www/html/wp-content/plugins/polar-express
   ```

2. **Check file permissions:**

   ```bash
   docker exec ssl-wireless-wordpress chown -R www-data:www-data /var/www/html/wp-content/plugins/polar-express
   ```

3. **Check WordPress debug logs:**
   ```bash
   docker exec ssl-wireless-wordpress tail -f /var/www/html/wp-content/debug.log
   ```

### Changes not reflecting

1. **Verify mount is active:**

   ```bash
   docker inspect ssl-wireless-wordpress | grep -A 10 Mounts
   ```

2. **Clear WordPress cache:**

   ```bash
   docker exec -it pexpress-wp-cli wp cache flush
   ```

3. **Check file ownership:**
   ```bash
   docker exec ssl-wireless-wordpress chown -R www-data:www-data /var/www/html/wp-content/plugins/polar-express
   ```

### WP-CLI connection issues

1. **Check network connectivity:**

   ```bash
   docker exec pexpress-wp-cli ping -c 3 ssl-wireless-db
   ```

2. **Verify database credentials** match your setup

## Quick Reference Commands

```bash
# Fresh start (clean setup)
./cleanup-old-containers.sh          # Remove old containers
./setup-fresh-wordpress.sh            # Create fresh WordPress with plugins

# Check WordPress status and start if needed
./check-wordpress.sh

# Fix WordPress port to 8080 (if running on different port)
./fix-wordpress-port.sh

# Recreate WordPress (SAFE - preserves all data if having issues)
./recreate-wordpress-safe.sh

# Bind plugins (SAFE - preserves all data)
./bind-plugins-safe.sh

# Bind plugin to container (standard)
./bind-plugin.sh

# Start WP-CLI container
docker-compose up -d wp-cli

# Activate plugin
docker exec -it pexpress-wp-cli wp plugin activate polar-express

# View plugin status
docker exec -it pexpress-wp-cli wp plugin list

# Check plugin files in container
docker exec ssl-wireless-wordpress ls -la /var/www/html/wp-content/plugins/polar-express

# View WordPress logs
docker logs ssl-wireless-wordpress --tail 50

# Access WordPress admin
# http://localhost:8080/wp-admin
```

## Next Steps

1. **Create test WooCommerce orders** to test the assignment workflow
2. **Test each role dashboard** by logging in with different users
3. **Configure webhooks** in WooCommerce Settings → Advanced → Webhooks
4. **Test SMS notifications** (if configured)

## Building for Release

When you're ready to create a release:

```bash
./build-release.sh 1.0.0
```

This creates `release/polar-express-1.0.0.zip` ready for distribution.
