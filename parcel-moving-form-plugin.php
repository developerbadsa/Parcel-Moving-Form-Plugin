<?php
/**
 * Plugin Name: Parcel Moving Form
 * Description: A plugin for handling parcel moving form submission and saving data to the database.
 * Version: 1.0
 * Author: Rahim-Badsa
 */

// Create the database table on plugin activation
function parcel_moving_create_table()
{
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

    wp_enqueue_script('parcel-moving-script', plugin_dir_url(__FILE__) . 'parcel-moving.js', array('jquery'), '1.0', true);


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

            // Send Email to User
            $subject_user = 'Your Parcel Moving Request';
            $message_user = "Hello $first_name $last_name,\n\n";
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
            $message_admin .= "First Name: $first_name\n";
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
                    <input type="date" style="font-size:18px" id="date" name="date" value="<?php echo date('Y-m-d'); ?>"
                        required>
                    <span>
                        <!-- <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                            <g id="SVGRepo_iconCarrier">
                                <path
                                    d="M3 9H21M7 3V5M17 3V5M6 12H10V16H6V12ZM6.2 21H17.8C18.9201 21 19.4802 21 19.908 20.782C20.2843 20.5903 20.5903 20.2843 20.782 19.908C21 19.4802 21 18.9201 21 17.8V8.2C21 7.07989 21 6.51984 20.782 6.09202C20.5903 5.71569 20.2843 5.40973 19.908 5.21799C19.4802 5 18.9201 5 17.8 5H6.2C5.0799 5 4.51984 5 4.09202 5.21799C3.71569 5.40973 3.40973 5.71569 3.21799 6.09202C3 6.51984 3 7.07989 3 8.2V17.8C3 18.9201 3 19.4802 3.21799 19.908C3.40973 20.2843 3.71569 20.5903 4.09202 20.782C4.51984 21 5.07989 21 6.2 21Z"
                                    stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </g>
                        </svg> -->
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
                style="background: white; margin: auto; padding: 20px; width: 700px; position: relative; top: 50%; transform: translateY(-50%);">
                <button id="close-modal-button"
                    style="position: absolute; top: 10px; right: 10px; border: none; background: none; font-size: 18px; cursor: pointer;">&times;</button>




                <div class="parcel-moving-form-popup-content">
                    <h2 class="parcel-moving-form-popup-title">My move</h2>
                    <p>Request your free and non-binding offer here</p>
                    <span class="parcel-moving-form-popup-img">
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="773"
                            height="101.569" viewBox="0 0 773 101.569">
                            <defs>
                                <style>
                                    .a,
                                    .d,
                                    .e,
                                    .g,
                                    .h,
                                    .i,
                                    .k,
                                    .l,
                                    .m,
                                    .n,
                                    .o,
                                    .p {
                                        fill: none;
                                    }

                                    .b {
                                        clip-path: url(#a);
                                    }

                                    .c {
                                        fill: #ffb3a4;
                                        fill-rule: evenodd;
                                    }

                                    .d,
                                    .e,
                                    .g,
                                    .h,
                                    .i,
                                    .k,
                                    .l,
                                    .m,
                                    .n,
                                    .o {
                                        stroke: #ff5722;
                                        stroke-linejoin: round;
                                    }

                                    .d,
                                    .e,
                                    .g,
                                    .h,
                                    .i,
                                    .k,
                                    .l,
                                    .m,
                                    .n,
                                    .o,
                                    .p {
                                        stroke-linecap: round;
                                    }

                                    .d {
                                        stroke-width: 2px;
                                    }

                                    .e,
                                    .p {
                                        stroke-width: 3px;
                                    }

                                    .f {
                                        clip-path: url(#b);
                                    }

                                    .g {
                                        stroke-width: 2.219px;
                                    }

                                    .h {
                                        stroke-width: 1.48px;
                                    }

                                    .i {
                                        stroke-width: 1.48px;
                                    }

                                    .j {
                                        clip-path: url(#c);
                                    }

                                    .k {
                                        stroke-width: 2.219px;
                                    }

                                    .l {
                                        stroke-width: 1.48px;
                                    }

                                    .m {
                                        stroke-width: 1.48px;
                                    }

                                    .n {
                                        stroke-width: 1.48px;
                                    }

                                    .o {
                                        stroke-width: 1.48px;
                                    }

                                    .p {
                                        stroke: #cecece;
                                        stroke-dasharray: 254.667 10;
                                    }
                                </style>
                                <clipPath id="a">
                                    <rect class="a" width="84.357" height="99.069" />
                                </clipPath>
                                <clipPath id="b">
                                    <rect class="a" width="105.203" height="56.286" />
                                </clipPath>
                                <clipPath id="c">
                                    <path class="a" d="M3.31,4.3,2.2,38.628H29.426V4.3Z" transform="translate(-2.2 -4.3)" />
                                </clipPath>
                            </defs>
                            <g transform="translate(-249.5 -226.931)">
                                <g class="b" transform="translate(927.646 226.931)">
                                    <path class="c"
                                        d="M79.7,78.7V93.547c1.8,0,3.3-.825,3.3-1.875,0,1.05,1.5,1.875,3.3,1.875s3.3-.825,3.3-1.875c0,1.05,1.5,1.875,3.3,1.875V78.7Z"
                                        transform="translate(-19.938 -19.688)" />
                                    <path class="c"
                                        d="M15.2,78.7V93.547c1.8,0,3.3-.825,3.3-1.875,0,1.05,1.5,1.875,3.3,1.875s3.3-.825,3.3-1.875c0,1.05,1.5,1.875,3.3,1.875V78.7Z"
                                        transform="translate(-3.802 -19.688)" />
                                    <path class="c" d="M15.2,40.2s6.6-13.722,6.6-22.5H15.2Z"
                                        transform="translate(-3.802 -4.428)" />
                                    <path class="c" d="M30.6,40.2S24,26.473,24,17.7h6.6Z"
                                        transform="translate(-6.004 -4.428)" />
                                    <path class="c" d="M47.5,40.2s6.6-13.722,6.6-22.5H47.5Z"
                                        transform="translate(-11.883 -4.428)" />
                                    <path class="c" d="M62.9,40.2s-6.6-13.722-6.6-22.5h6.6Z"
                                        transform="translate(-14.084 -4.428)" />
                                    <path class="c" d="M79.7,40.2s6.6-13.722,6.6-22.5H79.7Z"
                                        transform="translate(-19.938 -4.428)" />
                                    <path class="c" d="M95.1,40.2s-6.6-13.722-6.6-22.5h6.6Z"
                                        transform="translate(-22.139 -4.428)" />
                                    <rect class="d" width="13.197" height="22.488" transform="translate(11.398 13.272)" />
                                    <rect class="d" width="13.197" height="22.488" transform="translate(59.762 13.272)" />
                                    <rect class="d" width="13.197" height="22.488" transform="translate(11.398 58.862)" />
                                    <rect class="d" width="13.197" height="22.488" transform="translate(59.762 58.862)" />
                                    <rect class="d" width="13.197" height="22.488" transform="translate(35.617 13.272)" />
                                    <path class="e" d="M83.607,1.5H1.5L3.75,6.149V98.3H81.358V6.149Z"
                                        transform="translate(-0.375 -0.375)" />
                                    <path class="c"
                                        d="M64.12,82.148c0-5.7-5.249-10.348-11.772-10.348S40.5,76.449,40.5,82.148Z"
                                        transform="translate(-10.132 -17.962)" />
                                    <line class="d" x2="23.095" transform="translate(30.818 64.186)" />
                                    <path class="d"
                                        d="M52.347,71.8h0A11.772,11.772,0,0,0,40.5,83.572v20.1H64.12v-20.1A11.772,11.772,0,0,0,52.347,71.8Z"
                                        transform="translate(-10.132 -17.962)" />
                                    <line class="d" x1="14.847" y2="12.222" transform="translate(15.522 85.706)" />
                                    <line class="d" x2="14.922" y2="12.222" transform="translate(53.988 85.706)" />
                                    <rect class="d" width="13.122" height="21.325" transform="translate(35.617 64.186)" />
                                    <line class="d" x1="77.608" transform="translate(3.374 47.315)" />
                                    <line class="d" x1="77.608" transform="translate(3.374 5.774)" />
                                </g>
                                <g transform="translate(311.602 297.857)">
                                    <g transform="translate(-52.602 -28.143)">
                                        <g class="f">
                                            <ellipse class="g" cx="7.324" cy="7.324" rx="7.324" ry="7.324"
                                                transform="translate(11.837 40.543)" />
                                            <path class="h"
                                                d="M27.963,63.581A3.181,3.181,0,1,1,24.781,60.4,3.181,3.181,0,0,1,27.963,63.581Z"
                                                transform="translate(-5.62 -15.714)" />
                                            <ellipse class="g" cx="7.324" cy="7.324" rx="7.324" ry="7.324"
                                                transform="translate(82.047 40.543)" />
                                            <path class="i"
                                                d="M122.863,63.581a3.181,3.181,0,1,1-3.181-3.181A3.181,3.181,0,0,1,122.863,63.581Z"
                                                transform="translate(-30.31 -15.714)" />
                                        </g>
                                        <g class="j" transform="translate(1.628 3.181)">
                                            <path class="c"
                                                d="M14.649,24.423a6.363,6.363,0,1,1,6.363-6.363,6.363,6.363,0,0,1-6.363,6.363m0-20.123A14.276,14.276,0,0,0,.445,20.428c1.258,7.99,14.2,18.2,14.2,18.2,5.7-4.217,14.279-12.577,14.279-20.049A14.279,14.279,0,0,0,14.649,4.3"
                                                transform="translate(-1.702 -4.3)" />
                                        </g>
                                        <g class="f">
                                            <path class="k"
                                                d="M12.227,47.813h-5.1A3.7,3.7,0,0,1,3.645,45.52L1.8,40.785a3.7,3.7,0,0,1-.3-1.48L2.758,5.051A3.7,3.7,0,0,1,6.457,1.5H65.865a3.7,3.7,0,0,1,2.589,1.11L89.539,23.547l.37.3,12.5,9.618a3.7,3.7,0,0,1,1.406,2.663l.592,7.768a3.7,3.7,0,0,1-3.7,4h-3.7"
                                                transform="translate(-0.39 -0.39)" />
                                            <line class="g" x1="55.635" transform="translate(26.412 47.423)" />
                                            <path class="l"
                                                d="M102.3,16.9h-9.84A1.258,1.258,0,0,0,91.2,18.158v9.248a1.258,1.258,0,0,0,1.258,1.258H99.19l7.25,3.773h5.327a1.258,1.258,0,0,0,1.258-1.258V27.627"
                                                transform="translate(-23.728 -4.397)" />
                                            <line class="m" x1="43.65" transform="translate(32.256 42.614)" />
                                            <line class="n" x1="72.133" transform="translate(1.406 31.295)" />
                                            <line class="m" x1="9.84" transform="translate(94.476 42.614)" />
                                            <path class="o" d="M133.7,42.3s-4.365.666-3.625,2.589,7.4,3.7,7.4,3.7"
                                                transform="translate(-33.819 -11.005)" />
                                        </g>
                                    </g>
                                </g>
                                <path class="p" d="M3868.735,327h-770" transform="translate(-2847.735)" />
                            </g>
                        </svg>
                    </span>
                </div>

                <!-- Input Fields -->
                <div class="parcel-moving-form-popup-fields">
                    <label>First Name: <input type="text" id="first_name" name="first_name" required></label><br>
                    <label>Last Name: <input type="text" id="last_name" name="last_name" required></label><br>
                    <label>Email: <input type="email" id="email" name="email" required></label><br>
                    <label>Extra Data: <textarea id="extra_data" name="extra_data" required></textarea><br></label>
                </div>


                <button id="submit-extra-data" type="submit">Submit</button>
            </div>
        </div>

    </form>
    <?php
    return ob_get_clean(); // Return the form HTML
}
add_shortcode('parcel_moving_form', 'parcel_moving_form_shortcode');

// Admin page to display form submissions
function parcel_moving_add_admin_menu()
{
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

function parcel_moving_display_submissions()
{
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

