POLAR EXPRESS PLUGIN – FINAL PROFESSIONAL DEVELOPMENT PLAN
(Ready to hand over to any WordPress developer – copy-paste ready)

1.  Project Snapshot
    Website: Polar ice-cream bulk pre-order + fridge rental service
    Core business flow:
    Customer → Pre-orders on website → HR assigns Delivery + Fridge + Ice-cream → Delivery team delivers WITH fridge → Event ends → Fridge collected back
    Current tools you already have
    WordPress 6.6+
    WooCommerce 10.3.4 (local) / 10.3.3 (live)
    EtherTech WOOTP (your own OTP login/registration)
    SSLCommerz SMS gateway (already sending OTPs)
    Goal: Build ONE plugin → “Polar Express” that turns the boring WooCommerce admin into a LIVE operations cockpit.
2.  What WooCommerce ALREADY gives you for FREE (we will use 100%)
    You need this
    WooCommerce gives it FOR FREE
    How we will use it
    Orders, customers, products
    REST API v3 + Webhooks
    All data
    Order status changes
    15+ built-in statuses + custom statuses
    Confirmed → Assigned → Out-for-Delivery → Delivered → Fridge-Collected
    Customer phone/email
    Stored in user meta (WOOTP already puts it)
    Pull for SMS
    Stock management
    Built-in stock
    Ice-cream stock
    Emails
    Built-in templates
    Confirmation / Cancellation
    Permissions
    Shop Manager, Customer, etc.
    We will clone & extend

3.  What we MUST build CUSTOM (only 30% of work)
    Feature
    Why custom
    Where it lives
    5 new roles + dashboards
    WordPress core roles can’t do assignments
    Custom roles + menu pages
    Assignment system (HR → Delivery/Fridge/Product)
    No native way
    Order meta + user meta
    Fridge rental + return cycle
    Fridge is not a normal product
    Custom order meta + extra status
    Live dashboards (no page refresh)
    Normal WC is static
    Heartbeat API + AJAX
    SMS via SSLCommerz
    WC emails ≠ SMS
    Call your existing SSLCommerz SMS endpoint
    Shortcodes for front-end admin
    So HR/Delivery can login and see only their tasks
    [polar_hr], [polar_delivery], etc.

4.  FINAL ROLE & PERMISSION MATRIX (copy-paste into plugin)
    php
    // File: polar-express/roles.php
    function polar_create_roles() {
    // 1. Customer Support
    add_role('polar_support', 'Polar Support', array(
    'read' => true,
    'edit_shop_orders' => true,
    'read_shop_order' => true,
    'publish_shop_orders' => true,
    ));

        // 2. HR (the boss)
        add_role('polar_hr', 'Polar HR', array(
            'read'                   => true,
            'manage_woocommerce'     => true,
            'edit_users'             => true,
            'edit_shop_orders'       => true,
        ));

        // 3. DELIVERY PERSON
        add_role('polar_delivery', 'Polar Delivery', array('read' => true));

        // 4. FRIDGE PROVIDER
        add_role('polar_fridge', 'Polar Fridge Provider', array('read' => true));

        // 5. PRODUCT DISTRIBUTOR
        add_role('polar_distributor', 'Polar Distributor', array('read' => true));

    }
    register_activation_hook(**FILE**, 'polar_create_roles');

5.  DATABASE – ZERO custom tables (only meta – safe & future-proof)
    php
    // On every order we store:
    update_post_meta($order_id, '_polar_delivery_user_id', 123);    // WP user ID
update_post_meta($order_id, '\_polar_fridge_user_id', 456);
    update_post_meta($order_id, '_polar_distributor_user_id', 789);
update_post_meta($order_id, '\_polar_fridge_return_date', '2025-11-10');
    update_post_meta($order_id, '\_polar_assignment_note', 'Urgent – VIP client');
6.  SHORTCODES (just paste on any page)
    php
    // HR Dashboard
    add_shortcode('polar_hr', function() {
    if (!current_user_can('polar_hr')) return 'Access denied';
    ob_start(); include POLAR_PATH . 'templates/hr-dashboard.php';
    return ob_get_clean();
    });

// Delivery Person Dashboard
add_shortcode('polar_delivery', function() {
if (!current_user_can('polar_delivery')) return 'No tasks';
$user_id = get_current_user_id();
    // fetch orders where _polar_delivery_user_id = $user_id
    ...
});
Pages you will create in WordPress:
/hr-dashboard → paste [polar_hr]
/my-deliveries → paste [polar_delivery]
/fridge-tasks → paste [polar_fridge] etc.
7. LIVE REAL-TIME (no page refresh)
php
// Every 15 seconds WordPress Heartbeat runs
add_action('wp_ajax_polar_heartbeat', 'polar_heartbeat_callback');
function polar_heartbeat_callback() {
    $user_id = get_current_user_id();
    $tasks   = polar_get_my_tasks($user_id); // returns new/changed orders
wp_send_json_success($tasks);
}
Frontend JS (already in plugin):
js
// Inside dashboard page
setInterval(function(){
wp.heartbeat.connectNow();
}, 15000);

$(document).on('heartbeat-tick', function(e, data) {
    if (data.polar_tasks) {
        // refresh task table without reload
    }
});
8. SSLCommerz SMS – 100% ready (you already have it)
php
function polar_send_sms($phone, $message) {
    $url = "https://sms.sslwireless.com/pushapi/dynamic/server.php";
    $data = [
        'user'     => 'YOUR_USER',
        'pass'     => 'YOUR_PASS',
        'sid'      => 'POLARICE',
        'msisdn'   => $phone,
        'sms'      => $message,
        'csmsid'   => time()
    ];
    wp_remote_post($url, ['body' => $data]);
}

// Example usage
polar_send_sms($customer_phone, "Your Polar ice-cream will be delivered tomorrow 4PM with fridge. Call 01xxxx if change needed."); 9. CUSTOM ORDER STATUSES (copy-paste)
php
add_action('init', function() {
register_post_status('wc-polar-assigned', array('label' => 'Assigned'));
register_post_status('wc-polar-out', array('label' => 'Out for Delivery'));
register_post_status('wc-polar-delivered', array('label' => 'Ice-cream Delivered'));
register_post_status('wc-polar-fridge-back',array('label' => 'Fridge Collected'));
}); 10. WEBHOOKS – 5 minutes setup (automatic everything)
Go to WooCommerce → Settings → Advanced → Webhooks → Add webhook
Name
Topic
Delivery URL
Polar New Order
Order created
https://yoursite.com/wp-admin/admin-ajax.php?action=polar_webhook
Polar Status Change
Order updated
same URL

php
// File: polar-express/webhook.php
add_action('wp_ajax_nopriv_polar_webhook', 'polar_handle_webhook');
function polar_handle_webhook() {
$payload = json_decode(file_get_contents('php://input'), true);
$order_id = $payload['id'];
$status = $payload['status'];

    if ($status === 'processing') {
        // HR will assign → we just mark as "needs assignment"
        update_post_meta($order_id, '_polar_needs_assignment', 'yes');
    }
    wp_die('ok');

} 11. FINAL PLUGIN FOLDER STRUCTURE (copy-paste)
text
polar-express/
├── polar-express.php (main file)
├── roles.php
├── shortcodes.php
├── webhook.php
├── heartbeat.php
├── sms.php
├── templates/
│ ├── hr-dashboard.php
│ ├── delivery-dashboard.php
│ ├── fridge-dashboard.php
│ └── distributor-dashboard.php
├── assets/
│ ├── css/polar.css
│ └── js/polar.js
└── uninstall.php
