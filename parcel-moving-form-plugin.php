<?php
/**
 * Plugin Name: Parcel Moving Form
 * Description: A plugin for handling parcel moving form submission and saving data to the database.
 * Version: 1.0
 * Author: Rahim-Badsa
 */

// Create the database table on plugin activation
function parcel_moving_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'parcel_moving_data';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        from_location varchar(255) NOT NULL,
        to_location varchar(255) NOT NULL,
        date date NOT NULL,
        first_name varchar(255) NOT NULL,
        last_name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        extra_data text,
        time timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'parcel_moving_create_table');

// Enqueue JavaScript for AJAX submission
function parcel_moving_enqueue_scripts() {
    wp_enqueue_script('jquery'); // Ensure jQuery is loaded
    wp_enqueue_script(
        'parcel-moving-script',
        plugins_url('parcel-moving.js', __FILE__),
        array('jquery'),
        '1.0',
        true
    );

    wp_localize_script('parcel-moving-script', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('parcel_moving_nonce_action')
    ));
}
add_action('wp_enqueue_scripts', 'parcel_moving_enqueue_scripts');

// Handle form submission
add_action('wp_ajax_nopriv_parcel_moving_form_submit', 'parcel_moving_form_submit');
add_action('wp_ajax_parcel_moving_form_submit', 'parcel_moving_form_submit');

function parcel_moving_form_submit() {
    // Check nonce for security
    if (!isset($_POST['parcel_moving_nonce']) || !wp_verify_nonce($_POST['parcel_moving_nonce'], 'parcel_moving_nonce_action')) {
        wp_send_json_error('Invalid nonce.');
    }

    // Check if it is a POST request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Sanitize form inputs
        $from_location = sanitize_text_field($_POST['from_location']);
        $to_location = sanitize_text_field($_POST['to_location']);
        $date = sanitize_text_field($_POST['date']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $extra_data = sanitize_textarea_field($_POST['extra_data']);

        // Insert into the database
        global $wpdb;
        $table_name = $wpdb->prefix . 'parcel_moving_data';

        // Insert the data
        $result = $wpdb->insert($table_name, array(
            'from_location' => $from_location,
            'to_location' => $to_location,
            'date' => $date,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'extra_data' => $extra_data
        ));

        // Check for errors
        if ($result === false) {
            wp_send_json_error('Error saving data to the database.');
        } else {
            wp_send_json_success('Form submitted successfully and data saved.');
        }
    } else {
        wp_send_json_error('Invalid request method.');
    }
    wp_die(); // This is required to properly terminate AJAX requests in WordPress
}

// Shortcode to display the form
function parcel_moving_form_shortcode() {
    ob_start(); // Start output buffering to return form HTML
    ?>
    <form id="parcel-moving-form">
        <?php wp_nonce_field('parcel_moving_nonce_action', 'parcel_moving_nonce'); ?>
        <label>From Location: <input type="text" id="from_location" name="from_location" required></label><br>
        <label>To Location: <input type="text" id="to_location" name="to_location" required></label><br>
        <label>Date: <input type="date" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" required></label><br>
        <button type="button" id="goto-button">Go to Additional Data</button> <!-- Button to open modal -->

        <!-- Modal for Extra Data -->
        <div id="extra-data-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999;">
            <div style="background: white; margin: auto; padding: 20px; width: 300px; position: relative; top: 50%; transform: translateY(-50%);">
                <h2>Additional Data</h2>
                <label>First Name: <input type="text" id="first_name" name="first_name" required></label><br>
                <label>Last Name: <input type="text" id="last_name" name="last_name" required></label><br>
                <label>Email: <input type="email" id="email" name="email" required></label><br>
                <label>Extra Data: <textarea id="extra_data" name="extra_data" required></textarea><br></label>
                <button id="submit-extra-data" type="submit">Submit</button>
                <button id="cancel-modal">Cancel</button>
            </div>
        </div>
    </form>
    <?php
    return ob_get_clean(); // Return the form HTML
}
add_shortcode('parcel_moving_form', 'parcel_moving_form_shortcode');

// Admin page to display form submissions
function parcel_moving_add_admin_menu() {
    add_menu_page(
        'Parcel Moving Submissions',
        'Parcel Submissions',
        'manage_options',
        'parcel-moving-submissions',
        'parcel_moving_display_submissions',
        'dashicons-list-view',
        20
    );
}
add_action('admin_menu', 'parcel_moving_add_admin_menu');

function parcel_moving_display_submissions() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'parcel_moving_data';
    $results = $wpdb->get_results("SELECT * FROM $table_name");

    echo '<div class="wrap"><h1>Parcel Moving Submissions</h1>';
    if ($results) {
        echo '<table class="widefat fixed" cellspacing="0">';
        echo '<thead><tr><th>ID</th><th>From</th><th>To</th><th>Date</th><th>First Name</th><th>Last Name</th><th>Email</th><th>Extra Data</th><th>Time</th></tr></thead>';
        echo '<tbody>';
        foreach ($results as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row->id) . '</td>';
            echo '<td>' . esc_html($row->from_location) . '</td>';
            echo '<td>' . esc_html($row->to_location) . '</td>';
            echo '<td>' . esc_html($row->date) . '</td>';
            echo '<td>' . esc_html($row->first_name) . '</td>';
            echo '<td>' . esc_html($row->last_name) . '</td>';
            echo '<td>' . esc_html($row->email) . '</td>';
            echo '<td>' . esc_html($row->extra_data) . '</td>';
            echo '<td>' . esc_html($row->time) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No submissions found.</p>';
    }
    echo '</div>';
}

