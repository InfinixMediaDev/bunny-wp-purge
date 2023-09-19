<?php
/*
Plugin Name: Purge Cache Bunny CDN
Description: A custom WordPress plugin for purging Bunny CDN cache and displaying usage information.
Version: 1.0
Author: Your Name
*/

// Define Bunny CDN API endpoint
define('BUNNY_API_ENDPOINT', 'https://api.bunny.net/');

// Hook into the WordPress admin menu
add_action('admin_menu', 'purge_cache_bunny_cdn_menu');

// Create a menu item for Purge Cache Bunny CDN in the WordPress admin
function purge_cache_bunny_cdn_menu() {
    add_menu_page('Purge Cache Bunny CDN', 'Purge Cache Bunny CDN', 'manage_options', 'purge-cache-bunny-cdn', 'purge_cache_bunny_cdn_page');
}

// Callback function for the plugin page
function purge_cache_bunny_cdn_page() {
    ?>
    <div class="wrap">
        <h2>Purge Cache Bunny CDN</h2>
        
        <!-- Purge Cache Form -->
        <form method="post" action="">
            <label for="zone_id">Zone ID:</label>
            <input type="text" name="zone_id" id="zone_id" required />
            <input type="submit" name="purge_cache" class="button-primary" value="Purge Cache" />
        </form>
        
        <?php
        if (isset($_POST['purge_cache'])) {
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
        ?>
        
        <!-- Display Usage Information -->
        <?php
        $api_key = get_option('bunny_cdn_api_key');
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

// Function to get monthly bandwidth usage
function get_monthly_bandwidth_usage($api_key) {
    $url = BUNNY_API_ENDPOINT . '/statistics/bandwidth?apikey=' . $api_key;

    // Make the API request
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return 'Error fetching data from Bunny CDN API.';
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (is_array($data) && isset($data['value'])) {
        return 'Monthly Bandwidth Usage: ' . $data['value'] . ' GB';
    } else {
        return 'Unable to retrieve bandwidth usage data.';
    }
}

// Add "Purge Cache" button to admin bar
function add_purge_cache_button_to_admin_bar() {
    if (current_user_can('manage_options')) {
        global $wp_admin_bar;
        $wp_admin_bar->add_node(array(
            'id' => 'purge-cache-bunny-cdn',
            'title' => 'Purge Cache',
            'href' => admin_url('admin.php?page=purge-cache-bunny-cdn'),
        ));
    }
}
add_action('admin_bar_menu', 'add_purge_cache_button_to_admin_bar', 999);
?>
