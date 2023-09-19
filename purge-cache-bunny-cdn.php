<?php
/*
Plugin Name: Purge Cache Bunny CDN
Description: A custom WordPress plugin for purging Bunny CDN cache and displaying usage information.
Version: 1.0
Author: Your Name
*/

// Define Bunny CDN API endpoint
define('BUNNY_API_ENDPOINT', 'https://bunnycdn.com/api');

// Hook into the WordPress admin menu
add_action('admin_menu', 'purge_cache_bunny_cdn_menu');

// Create a menu item for Purge Cache Bunny CDN in the WordPress admin
function purge_cache_bunny_cdn_menu() {
    add_menu_page('Purge Cache Bunny CDN', 'Purge Cache Bunny CDN', 'manage_options', 'purge-cache-bunny-cdn', 'purge_cache_bunny_cdn_page');
}

// Callback function for the plugin page
function purge_cache_bunny_cdn_page() {
    $api_key = get_option('bunny_cdn_api_key');
    $api_status = check_bunny_cdn_api_connection($api_key);

    ?>
    <div class="wrap">
        <h2>Purge Cache Bunny CDN</h2>

        <!-- Display API Connection Status -->
        <?php if ($api_status['connected']) : ?>
            <div class="updated"><p><?php echo esc_html($api_status['message']); ?></p></div>
        <?php else : ?>
            <div class="error"><p><?php echo esc_html($api_status['message']); ?></p></div>
        <?php endif; ?>

        <!-- Purge Cache Form -->
        <form method="post" action="">
            <label for="zone_id">Zone ID:</label>
            <input type="text" name="zone_id" id="zone_id" required />
            <input type="submit" name="purge_cache" class="button-primary" value="Purge Cache" />
        </form>

        <!-- Check API Connection Button -->
        <form method="post" action="">
            <input type="submit" name="check_connection" class="button-secondary" value="Check API Connection" />
        </form>

        <?php
        if (isset($_POST['purge_cache'])) {
            // Handle cache purge when the button is clicked
            $zone_id = sanitize_text_field($_POST['zone_id']);
            $api_key = get_option('bunny_cdn_api_key');
            
            // Check if API key is set
            if (empty($api_key)) {
                echo '<div class="error"><p>API key is not set. Please configure it on the settings page.</p></div>';
            } else {
                $result = purge_bunny_cdn_cache($api_key, $zone_id);
                if ($result === true) {
                    echo '<div class="updated"><p>Cache purge request sent successfully.</p></div>';
                } else {
                    echo '<div class="error"><p>Cache purge request failed: ' . esc_html($result) . '</p></div>';
                }
            }
        }

        if (isset($_POST['check_connection'])) {
            // Check API Connection when the button is clicked
            $api_status = check_bunny_cdn_api_connection($api_key);
        }
        ?>

        <!-- Display Usage Information -->
        <?php
        if (!empty($api_key)) {
            $bandwidth_usage = get_monthly_bandwidth_usage($api_key);
            echo '<p>' . esc_html($bandwidth_usage) . '</p>';
        }
        ?>
    </div>
    <?php
}

// Function to purge cache using Bunny CDN API
function purge_bunny_cdn_cache($api_key, $zone_id) {
    $url = BUNNY_API_ENDPOINT . '/pullzone/' . $zone_id . '/purgeCache?apikey=' . $api_key;

    // Make the API request
    $response = wp_remote_post($url);

    if (is_wp_error($response)) {
        return 'Error sending cache purge request.';
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code === 200) {
        return true;
    } else {
        return 'Cache purge request failed with status code ' . $code;
    }
}

// Function to check the API connection
function check_bunny_cdn_api_connection($api_key) {
    $url = BUNNY_API_ENDPOINT . '/ping?apikey=' . $api_key;

    // Make the API request
    $response = wp_remote_get($url);

    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        return array(
            'connected' => true,
            'message' => 'API Connection Successful.'
        );
    } else {
        return array(
            'connected' => false,
            'message' => 'API Connection Failed. Please check your API key and try again.'
        );
    }
}

// Enqueue the CSS file
function enqueue_purge_cache_bunny_cdn_styles() {
    wp_enqueue_style('purge-cache-bunny-cdn-styles', plugins_url('assets/style.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'enqueue_purge_cache_bunny_cdn_styles');
?>
