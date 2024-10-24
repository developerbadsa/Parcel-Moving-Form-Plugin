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
    $table_name = $wpdb->prefix . 'parcel_moving_data1';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        from_location varchar(255) NOT NULL,
        to_location varchar(255) NOT NULL,
        date date NOT NULL,
        full_name varchar(255) NOT NULL,
        phone varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        area text,
        time timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Execute the query
    dbDelta($sql);

    // Log any errors from dbDelta
    if ($wpdb->last_error) {
        error_log('Database error: ' . $wpdb->last_error);
    }
}


register_activation_hook(__FILE__, 'parcel_moving_create_table');


// Enqueue JavaScript for AJAX submission
function parcel_moving_enqueue_scripts()
{
    wp_enqueue_script('jquery'); // Ensure jQuery is loaded

    wp_enqueue_script(
        'parcel-moving-script',
        plugins_url('parcel-moving.js', __FILE__),
        array('jquery'),
        '1.0',
        true
    );


    // Localize script to make ajax_object available in JS
    wp_localize_script('parcel-moving-script', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'), // The URL for AJAX requests
    ));

    // Enqueue the auto-fill-field.js script
    wp_enqueue_script(
        'parcel-moving-auto-fill', // Handle for the script
        plugins_url('js/auto-fill-field.js', __FILE__), // Path to your auto-fill script
        array(), // No dependencies
        '1.0', // Version
        true // Load in the footer
    );


    // Enqueue the CSS file
    wp_enqueue_style(
        'parcel-moving-style', // Handle for the style
        plugins_url('style.css', __FILE__), // Path to the CSS file
        array(), // Dependencies (optional)
        '1.0' // Version
    );
}
add_action('wp_enqueue_scripts', 'parcel_moving_enqueue_scripts');



// Handle form submission
add_action('wp_ajax_nopriv_parcel_moving_form_submit', 'parcel_moving_form_submit');
add_action('wp_ajax_parcel_moving_form_submit', 'parcel_moving_form_submit');



function parcel_moving_form_submit()
{
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
        $full_name = sanitize_text_field($_POST['full_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $extra_data = sanitize_textarea_field($_POST['extra_data']);

        // Insert into the database
        global $wpdb;
        $table_name = $wpdb->prefix . 'parcel_moving_data1';

        // Insert the data
        $result = $wpdb->insert($table_name, array(
            'from_location' => $from_location,
            'to_location' => $to_location,
            'date' => $date,
            'full_name' => $full_name,
            'phone' => $last_name,
            'email' => $email,
            'area' => $extra_data
        ));

        // Check for errors
        if ($result === false) {
            wp_send_json_error('Error saving data to the database.');
        } else {

            // Send Email to User
            $subject_user = 'Your Parcel Moving Request';
            $message_user = "Hello $full_name $last_name,\n\n";
            $message_user .= "Thank you for submitting your parcel moving request. Here are the details of your submission:\n\n";
            $message_user .= "From: $from_location\n";
            $message_user .= "To: $to_location\n";
            $message_user .= "Date: $date\n";
            $message_user .= "Extra Data: $extra_data\n\n";
            $message_user .= "We will contact you shortly regarding the next steps.\n\n";
            $message_user .= "Thank you,\nYour Parcel Moving Service Team";
            $headers = array('Content-Type: text/plain; charset=UTF-8');

            // Send the email to the user
            wp_mail($email, $subject_user, $message_user, $headers);

            // Send Email to Admin
            $admin_email = get_option('admin_email'); // Get the admin email from WordPress settings
            $subject_admin = 'New Parcel Moving Request Submission';
            $message_admin = "A new parcel moving request has been submitted. Here are the details:\n\n";
            $message_admin .= "From: $from_location\n";
            $message_admin .= "To: $to_location\n";
            $message_admin .= "Date: $date\n";
            $message_admin .= "First Name: $full_name\n";
            $message_admin .= "Last Name: $last_name\n";
            $message_admin .= "Email: $email\n";
            $message_admin .= "Extra Data: $extra_data\n\n";
            $message_admin .= "Please log in to the admin panel to view more details.";


            // Send the email to the user
            $user_email_sent = wp_mail($email, $subject_user, $message_user, $headers);
            if (!$user_email_sent) {
                error_log('Email to user failed to send.');
            }

            // Send the email to the admin
            $admin_email_sent = wp_mail($admin_email, $subject_admin, $message_admin, $headers);
            if (!$admin_email_sent) {
                error_log('Email to admin failed to send.');
            }

            // Check if both emails were sent successfully
            if ($user_email_sent && $admin_email_sent) {
                wp_send_json_success('Form submitted successfully, and an email has been sent to both the user and the admin.');
            } else {
                wp_send_json_error('Form submitted successfully, but there was an issue sending the email(s).');
            }




        }
    } else {
        wp_send_json_error('Invalid request method.');
    }
    wp_die(); // This is required to properly terminate AJAX requests in WordPress
}



// Shortcode to display the form
function parcel_moving_form_shortcode()
{
    ob_start(); // Start output buffering to return form HTML
    ?>
    <form id="parcel-moving-form">
        <?php wp_nonce_field('parcel_moving_nonce_action', 'parcel_moving_nonce'); ?>

        <!-- Frontend form -->
        <div class="parcel-moving-form-inputs">
            <!-- From Location -->
            <div>
                <label class="parcel-moving-form-input">
                    <input type="text" id="from_location" placeholder="From Location" name="from_location" required>
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000"
                            version="1.1" id="Capa_1" width="800px" height="800px" viewBox="0 0 395.71 395.71"
                            xml:space="preserve">
                            <g>
                                <path
                                    d="M197.849,0C122.131,0,60.531,61.609,60.531,137.329c0,72.887,124.591,243.177,129.896,250.388l4.951,6.738   c0.579,0.792,1.501,1.255,2.471,1.255c0.985,0,1.901-0.463,2.486-1.255l4.948-6.738c5.308-7.211,129.896-177.501,129.896-250.388   C335.179,61.609,273.569,0,197.849,0z M197.849,88.138c27.13,0,49.191,22.062,49.191,49.191c0,27.115-22.062,49.191-49.191,49.191   c-27.114,0-49.191-22.076-49.191-49.191C148.658,110.2,170.734,88.138,197.849,88.138z" />
                            </g>
                        </svg>
                    </span>
                </label>
                <ul id="from_location_suggestions" class="suggestions-list"></ul>
            </div>
            <!-- To Location -->
            <div>
                <label class="parcel-moving-form-input">
                    <input type="text" id="to_location" placeholder="To Location" name="to_location" required>
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000"
                            version="1.1" id="Capa_1" width="800px" height="800px" viewBox="0 0 395.71 395.71"
                            xml:space="preserve">
                            <g>
                                <path
                                    d="M197.849,0C122.131,0,60.531,61.609,60.531,137.329c0,72.887,124.591,243.177,129.896,250.388l4.951,6.738   c0.579,0.792,1.501,1.255,2.471,1.255c0.985,0,1.901-0.463,2.486-1.255l4.948-6.738c5.308-7.211,129.896-177.501,129.896-250.388   C335.179,61.609,273.569,0,197.849,0z M197.849,88.138c27.13,0,49.191,22.062,49.191,49.191c0,27.115-22.062,49.191-49.191,49.191   c-27.114,0-49.191-22.076-49.191-49.191C148.658,110.2,170.734,88.138,197.849,88.138z" />
                            </g>
                        </svg>
                    </span>
                </label>
                <ul id="to_location_suggestions" class="suggestions-list"></ul>
            </div>
            <!-- Date -->
            <div>
                <label class="parcel-moving-form-input">
                    <input type="date" style="font-size:18px" id="date" name="date" 
                        required>
                    <span class="parcel-moving-form-input-date">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                            <g id="SVGRepo_iconCarrier">
                                <path
                                    d="M3 9H21M7 3V5M17 3V5M6 12H10V16H6V12ZM6.2 21H17.8C18.9201 21 19.4802 21 19.908 20.782C20.2843 20.5903 20.5903 20.2843 20.782 19.908C21 19.4802 21 18.9201 21 17.8V8.2C21 7.07989 21 6.51984 20.782 6.09202C20.5903 5.71569 20.2843 5.40973 19.908 5.21799C19.4802 5 18.9201 5 17.8 5H6.2C5.0799 5 4.51984 5 4.09202 5.21799C3.71569 5.40973 3.40973 5.71569 3.21799 6.09202C3 6.51984 3 7.07989 3 8.2V17.8C3 18.9201 3 19.4802 3.21799 19.908C3.40973 20.2843 3.71569 20.5903 4.09202 20.782C4.51984 21 5.07989 21 6.2 21Z"
                                    stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </g>
                        </svg>
                    </span>
                </label>
                <ul id="to_location_suggestions" class="suggestions-list"></ul>
            </div>
            <button type="button" id="goto-button">Get a Free Qoute</button>
        </div>

        <!-- Modal for Extra Data -->
        <div id="extra-data-modal"
            style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999;">

            <!-- Main container of popup -->
            <div class="parcel-moving-form-popup"
                style="background: white; margin: auto; padding: 20px; position: relative; top: 50%; transform: translateY(-50%);">
                <button id="close-modal-button"
                    style="position: absolute; top: 10px; right: 10px; border: none; background: none; font-size: 18px; cursor: pointer;">&times;</button>




                <div class="parcel-moving-form-popup-content">
                    <h2 class="parcel-moving-form-popup-title">My move</h2>
                    <p>Request your free and non-binding offer here</p>
<!-- svg -->
                </div>

                <!-- Input Fields -->
                <div class="parcel-moving-form-popup-fields">
                    <label> <input placeholder="Full Name" type="text" id="full_name" name="full_name" required></label>
                    <label> <input placeholder="Email" type="email" id="email" name="email" required></label>
                    <label> <input placeholder="Mobile Number" type="phone" id="last_name" name="last_name" required></label>
                    <label> <textarea placeholder="Area" id="extra_data" name="extra_data" required></textarea></label>
                </div>


                <!-- Checkboxs with agreements -->
                <div class="parcel-moving-form-popup-checkboxs">
                    <div class="parcel-moving-form-popup-checkbox-item">
                        <input type="checkbox" name="" id="checkbox1" required>
                        <span>
                            I agree to the privacy policy, cancellation policy and general terms and conditions. By
                            agreeing, you allow us to also provide you with our offer by telephone.
                        </span>
                    </div>
                    <div class="parcel-moving-form-popup-checkbox-item">
                        <input type="checkbox" name="" id="checkbox1" required>
                        <span>
                            I agree to receive offers for advertising purposes from Movinga or selected Movinga partners.
                            For this purpose, Movinga may use my data and, if necessary, pass it on to selected Movinga
                            partners.
                        </span>
                    </div>
                </div>


                <!-- Input Fields -->
                <!-- <div class="parcel-moving-form-popup-fields">
                    <label>First Name: <input type="text" id="full_name" name="full_name" required></label><br>
                    <label>Last Name: <input type="text" id="last_name" name="last_name" required></label><br>
                    <label>Email: <input type="email" id="email" name="email" required></label><br>
                    <label>Extra Data: <textarea id="extra_data" name="extra_data" required></textarea><br></label>
                </div> -->


                <button id="submit-extra-data" type="submit">Get a free quote</button>
            </div>
        </div>

    </form>
    <?php
    return ob_get_clean(); // Return the form HTML
}
add_shortcode('parcel_moving_form', 'parcel_moving_form_shortcode');





//===================================== Admin page to display form submissions with Delete feature ==========================================
function parcel_moving_add_admin_menu()
{
    add_menu_page(
        'Parcel Moving Submissions',
        'Parcel Entries',
        'manage_options',
        'parcel-moving-submissions',
        'parcel_moving_display_submissions',
        'dashicons-list-view',
        20
    );
}

add_action('admin_menu', 'parcel_moving_add_admin_menu');

function parcel_moving_display_submissions()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'parcel_moving_data1';

    // Handle delete action if the delete request is sent
    if (isset($_GET['delete_id'])) {
        $delete_id = intval($_GET['delete_id']);
        $wpdb->delete($table_name, array('id' => $delete_id)); // Delete entry from the database
        echo '<div class="updated notice is-dismissible"><p>Entry deleted successfully.</p></div>';
    }

    $results = $wpdb->get_results("SELECT * FROM $table_name");

    echo '<div class="wrap"><h1>Parcel Moving Submissions</h1>';
    if ($results) {
        echo '<table class="widefat fixed" cellspacing="0">';
        echo '<thead><tr><th>From</th><th>To</th><th>Date</th><th>Full Name</th><th>Phone</th><th>Email</th><th>Area</th><th>Time</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        foreach ($results as $row) {
            $delete_url = esc_url(add_query_arg(array('delete_id' => $row->id)));
            echo '<tr>';
            // echo '<td>' . esc_html($row->id) . '</td>';
            echo '<td>' . esc_html($row->from_location) . '</td>';
            echo '<td>' . esc_html($row->to_location) . '</td>';
            echo '<td>' . esc_html($row->date) . '</td>';
            echo '<td>' . esc_html($row->full_name) . '</td>';
            echo '<td>' . esc_html($row->phone) . '</td>';
            echo '<td>' . esc_html($row->email) . '</td>';
            echo '<td>' . esc_html($row->area) . '</td>';
            echo '<td>' . esc_html($row->time) . '</td>';
            // Add the delete action link
            echo '<td><a href="' . $delete_url . '" onclick="return confirm(\'Are you sure you want to delete this entry?\');" class="button button-danger">Delete</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No submissions found.</p>';
    }
    echo '</div>';
}
