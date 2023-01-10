<?php
/*
Plugin Name: Purge Cache
Description: A plugin that allows you to purge the cache of a specific CDN pull zone.
*/

// Register the administration menu option
add_action('admin_menu', 'purge_cache_menu');
function purge_cache_menu() {
    add_options_page('Purge CDN Cache', 'Purge CDN Cache', 'manage_options', 'purge-cache', 'purge_cache_options');
}

// Display the options page
function purge_cache_options() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    ?>
    <div class="wrap">
        <h2>Purge CDN Cache</h2>
        <form method="post" action="options.php">
            <?php settings_fields('purge_cache_options'); ?>
            <?php do_settings_sections('purge-cache'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Pull Zone ID</th>
                    <td>
                        <input type="text" name="pull_zone_id" value="<?php echo get_option('pull_zone_id'); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">API Key</th>
                    <td>
                        <input type="text" name="api_key" value="<?php echo get_option('api_key'); ?>" />
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Register the plugin's options
add_action('admin_init', 'purge_cache_admin_init');
function purge_cache_admin_init() {
    register_setting('purge_cache_options', 'pull_zone_id');
    register_setting('purge_cache_options', 'api_key');
}

// Function to purge the cache
function purge_cache() {
    // Get the pull zone ID and API key
    $pull_zone_id = get_option('pull_zone_id');
    $api_key = get_option('api_key');

    // Build the API endpoint URL
    $api_url = "https://api.bunny.net/pullzone/$pull_zone_id/purgeCache";

    // Build the headers for the API request
    $headers = array(
        "AccessKey: $api_key",
        "Content-Type: application/json"
    );

    // Build the options for the API request
    $options = array(
        'http' => array(
            'header'  => $headers,
            'method'  => 'POST',
        ),
    );
    $context  = stream_context_create($options);

    // Send the API request to purge
    $result = file_get_contents($api_url, false, $context);

    // Check if the request was successful
    if ($result === false) {
        $error = error_get_last();
        wp_die("An error occurred while purging the cache: " . $error['message']);
    } else {
        add_action( 'admin_notices', 'purge_cache_success_notice' );
    }
}

// Display a success notice after the cache has been purged
function purge_cache_success_notice() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p>The cache has been successfully purged.</p>
    </div>
    <?php
}

// Add the "Purge Cache" button to the admin bar
add_action( 'wp_before_admin_bar_render', 'purge_cache_admin_bar_button' );
function purge_cache_admin_bar_button() {
    global $wp_admin_bar;

    $wp_admin_bar->add_node( array(
        'id' => 'purge-cache',
        'title' => 'Purge CDN Cache',
        'href' => '#',
        'meta' => array( 'onclick' => 'purgeCache()' )
    ) );
}

// Add the JavaScript function to handle the button click
add_action( 'admin_footer', 'purge_cache_javascript' );
function purge_cache_javascript() {
    ?>
    <script>
        function purgeCache() {
            // Get the pull zone ID and API key
            var pull_zone_id = '<?php echo get_option( 'pull_zone_id' ); ?>';
            var api_key = '<?php echo get_option( 'api_key' ); ?>';

            // Build the API endpoint URL
            var api_url = "https://api.bunny.net/pullzone/" + pull_zone_id + "/purgeCache";

            // Build the headers for the API request
            var headers = {
                "AccessKey": api_key,
                "Content-Type": "application/json"
            };

            // Send the API request to purge the cache
            fetch(api_url, {
                method: 'POST',
                headers: headers
            })
            .then(response => {
                if (response.ok) {
                    alert('Cache purged successfully!');
                } else {
                    alert('An error occurred while purging the cache: ' + response.statusText);
                }
            })
            .catch(error => alert('An error occurred while purging the cache: ' + error));
        }
    </script>
    <?php
}
