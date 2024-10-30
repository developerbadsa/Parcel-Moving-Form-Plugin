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
    $table_name = $wpdb->prefix . 'parcel_moving_data1';
    $parcel_locations_table = $wpdb->prefix . 'parcel_locations';
    $charset_collate = $wpdb->get_charset_collate();


    // SQL statement for creating the locations table
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

    // SQL statement for creating the locations table
    $sql2 = "CREATE TABLE $parcel_locations_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        location varchar(255) NOT NULL,
        detail text NOT NULL,
        PRIMARY KEY  (id)
    );";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Execute the query
    dbDelta($sql);
    dbDelta($sql2);

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


    // Localize script to pass data from PHP to JavaScript
    wp_localize_script('auto-fill-field', 'parcelMovingData', array(
        'ajax_url' => admin_url('admin-ajax.php') // This is the URL for AJAX requests
    ));






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
                    <input type="text" id="from_location" class="from_location" placeholder="Auszugsadresse" autocomplete="off"
                        name="from_location" required>
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
                <ul id="from_location_suggestions"  class="suggestions-list from_location_suggestions_view"></ul>
            </div>
            <!-- To Location -->
            <div>
                <label class="parcel-moving-form-input">
                    <input type="text" id="to_location" class="to_location" placeholder="Einzugsadresse" name="to_location" autocomplete="off"
                        required>
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
                <ul id="to_location_suggestions" class="suggestions-list to_location_suggestions_view"></ul>
            </div>
            <!-- Date -->
            <div>
                <label class="parcel-moving-form-input">
                    <input type="date" style="font-size:18px" placeholder="Umzugsdtum" id="date" name="date" required>
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
            <button type="button" id="goto-button">Kostenloses Angebot erhalten</button>
        </div>

        <!-- Modal for Extra Data -->
        <div id="extra-data-modal"
            style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999;">

            <!-- Main container of popup -->
            <div class="parcel-moving-form-popup"
                style="">
                <button id="close-modal-button"
                    style="position: absolute; top: 10px; right: 10px; border: none; background: none; font-size: 18px; cursor: pointer;">&times;</button>




                <div class="parcel-moving-form-popup-content">
                    <h2 class="parcel-moving-form-popup-title">Mein Umzug</h2>
                    <p>Fordern Sie hier ihr kostenloses und unverbindliches Angebot an</p>


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
                    <label> <input placeholder="Vor- und Nachname" type="text" id="full_name" name="full_name" required></label>
                    <label> <input placeholder="Email" type="email" id="email" name="email" required></label>
                    <label> <input placeholder="Telefonnummer" type="phone" id="last_name" name="last_name"
                            required></label>
                    <label> <select placeholder="Fläche" id="extra_data" name="extra_data" required>
                            <option value="20">Wohnung 20 m²</option>
                            <option value="40">20 to 40 m²</option>
                            <option value="60">40 to 60 m²</option>
                            <option value="80">60 to 80 m²</option>
                            <option value="100">80 to 100 m²</option>
                            <option value="200">100 to 200 m²</option>
                            <option value="300">200 m² und darüber</option>
                        </select></label>
                </div>

                <!-- Checkboxs with agreements -->
                <div class="parcel-moving-form-popup-checkboxs">
                    <div class="parcel-moving-form-popup-checkbox-item">
                        <input type="checkbox" name="" id="checkbox1" required>
                        <span>
                     
Ich stimme den Datenschutzbestimmungen, der Widerrufsbelehrung und den AGB zu. Sie erlauben uns, das Angebot auch telefonisch bereitzustellen.
                        </span>
                    </div>
                    <div class="parcel-moving-form-popup-checkbox-item">
                        <input type="checkbox" name="" id="checkbox1" required>
                        <span>
                       
Ich stimme zu, Angebote von Movinga und ausgewählten Partnern zu Werbezwecken zu erhalten.

                        </span>
                    </div>
                </div>


                <button id="submit-extra-data" type="submit">Kostenloses Angebot erhalten</button>
                <div class="parcel-form-payment-icons">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="33.79" height="39.575" viewBox="0 0 33.79 39.575">
                            <g id="ICON-Paypal" transform="translate(16.895 19.788)">
                                <g id="Group_1627" data-name="Group 1627" transform="translate(-16.895 -19.788)">
                                    <path id="Path_5493" data-name="Path 5493"
                                        d="M45.908,27.373a5.846,5.846,0,0,0-2.947-4.006c-.065-.037-.127-.079-.19-.118l0,0c0-.007,0-.014,0-.021l-.137-.059c-.1-.041-.193-.082-.287-.129a8.387,8.387,0,0,0-1.529-.542,15.048,15.048,0,0,0-2.384-.42c-.211-.023-.422-.037-.633-.051l-.223-.015c-.3-.022-.636-.033-1.018-.034q-3.379-.011-6.757-.007H26.642a1.732,1.732,0,0,0-.428.049,1.575,1.575,0,0,0-1.224,1.344L23.254,34.339c-.06.374-.123.748-.185,1.121l-.115.689a.135.135,0,0,0,.037.146L23,36.3a9.547,9.547,0,0,0-.264,1.381c-.085.48-.148.964-.225,1.446-.1.624-.2,1.247-.3,1.87q-.1.611-.2,1.223-.108.672-.214,1.343c-.086.55-.168,1.1-.255,1.651s-.177,1.087-.265,1.631q-.125.773-.249,1.547-.163,1.041-.326,2.082c-.067.432-.14.864-.2,1.3a.846.846,0,0,0,.243.712.953.953,0,0,0,.73.3h4.861c.513,0,1.026,0,1.539,0a1.548,1.548,0,0,0,1.511-1.211l1.372-8.475A1.552,1.552,0,0,1,32.3,41.787c.271,0,.542,0,.813,0a19.484,19.484,0,0,0,3.067-.207,13.613,13.613,0,0,0,2.935-.766,10.077,10.077,0,0,0,3.419-2.177,10.529,10.529,0,0,0,2.337-3.479,15.882,15.882,0,0,0,1.225-5.292A8.93,8.93,0,0,0,45.908,27.373Z"
                                        transform="translate(-12.32 -13.206)" fill="#0093c7" />
                                    <g id="Group_1625" data-name="Group 1625" transform="translate(0 0)">
                                        <path id="Path_5494" data-name="Path 5494"
                                            d="M30.422,6.078A7.037,7.037,0,0,0,27.1,1.631,10.6,10.6,0,0,0,23.774.411,20.44,20.44,0,0,0,19.6,0Q13.28-.006,6.958,0a2.139,2.139,0,0,0-.327.025A1.765,1.765,0,0,0,5.179,1.441c-.061.345-.113.693-.168,1.039l-5,31.464a1.054,1.054,0,0,0,.824,1.214,1.533,1.533,0,0,0,.343.026H6.146l2.56,0q.124-.774.249-1.547c.088-.544.178-1.087.265-1.631s.169-1.1.255-1.651q.105-.672.214-1.343.1-.611.2-1.223c.1-.623.2-1.246.3-1.87.076-.482.14-.966.225-1.446a9.548,9.548,0,0,1,.264-1.381l.012.01.075-.144a1.728,1.728,0,0,1,1.026-.874,2.063,2.063,0,0,1,.64-.091l1.839,0,1.622,0a22.445,22.445,0,0,0,2.4-.105c.69-.073,1.3-.164,1.86-.277a13.608,13.608,0,0,0,4.217-1.546,11.179,11.179,0,0,0,3.291-2.9,13.461,13.461,0,0,0,2.01-3.829,18.955,18.955,0,0,0,.686-2.585l.032-.153a2.193,2.193,0,0,0,.065-.562l0,0a.646.646,0,0,0,.017-.066,13.938,13.938,0,0,0,.162-1.858A7.813,7.813,0,0,0,30.422,6.078Z"
                                            transform="translate(0 0)" fill="#23346d" />
                                    </g>
                                    <g id="Group_1626" data-name="Group 1626" transform="translate(0)">
                                        <path id="Path_5495" data-name="Path 5495"
                                            d="M10.953,21.137c-.1.6-.2,1.207-.3,1.81-.008.044-.016.093.029.127a9.245,9.245,0,0,0-.27,1.4c-.085.48-.148.964-.225,1.446-.1.624-.2,1.247-.3,1.87q-.1.611-.2,1.223c-.072.448-.144.9-.214,1.343-.086.55-.168,1.1-.256,1.651s-.177,1.087-.264,1.631q-.125.773-.249,1.547l-2.56,0H1.182a1.531,1.531,0,0,1-.343-.026,1.054,1.054,0,0,1-.824-1.214l5-31.464c.055-.347.107-.694.168-1.039A1.765,1.765,0,0,1,6.631.026,2.13,2.13,0,0,1,6.958,0Q13.28,0,19.6,0a20.44,20.44,0,0,1,4.171.407A10.608,10.608,0,0,1,27.1,1.631a7.037,7.037,0,0,1,3.323,4.447,7.813,7.813,0,0,1,.207,2.04,13.938,13.938,0,0,1-.162,1.858.556.556,0,0,1-.017.066l-.012,0c-.142-.062-.287-.119-.426-.188a8.387,8.387,0,0,0-1.525-.54,14.952,14.952,0,0,0-2.381-.419c-.284-.032-.571-.045-.856-.066-.337-.024-.678-.033-1.016-.034-3.3-.01-6.606-.006-9.909-.008a1.7,1.7,0,0,0-.426.048,1.548,1.548,0,0,0-1.21,1.328Z"
                                            transform="translate(0 0)" fill="#23346d" />
                                    </g>
                                    <path id="Path_5496" data-name="Path 5496"
                                        d="M46.4,23.809a2.193,2.193,0,0,0,.065-.562c0-.007,0-.014,0-.021l-.137-.059c-.1-.041-.193-.082-.287-.129a8.387,8.387,0,0,0-1.529-.542,15.048,15.048,0,0,0-2.384-.42c-.211-.023-.422-.037-.633-.051l-.223-.015c-.3-.022-.636-.033-1.018-.034q-3.379-.011-6.757-.007H30.337a1.732,1.732,0,0,0-.428.049,1.575,1.575,0,0,0-1.224,1.344L26.948,34.339c-.06.374-.123.748-.185,1.121l-.115.689a.135.135,0,0,0,.037.146l.006.005.012.01.075-.144a1.728,1.728,0,0,1,1.026-.874,2.063,2.063,0,0,1,.64-.091l1.839,0,1.622,0a22.444,22.444,0,0,0,2.4-.105c.69-.073,1.3-.164,1.86-.277a13.609,13.609,0,0,0,4.217-1.546,11.179,11.179,0,0,0,3.291-2.9,13.461,13.461,0,0,0,2.01-3.829,18.954,18.954,0,0,0,.686-2.585Z"
                                        transform="translate(-16.015 -13.206)" fill="#1d2653" />
                                </g>
                            </g>
                        </svg>
                    </div>
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="70.238" height="22.684" viewBox="0 0 70.238 22.684">
                            <g id="ICON-Visa" transform="translate(35.119 11.342)">
                                <path id="Path_5497" data-name="Path 5497"
                                    d="M26.668.4l-9.2,21.94h-6L6.948,4.832A2.4,2.4,0,0,0,5.6,2.9,23.771,23.771,0,0,0,0,1.038L.135.4H9.792a2.645,2.645,0,0,1,2.617,2.237l2.39,12.7L20.706.4ZM50.175,15.177c.024-5.79-8.007-6.11-7.952-8.7.017-.787.767-1.624,2.408-1.838a10.7,10.7,0,0,1,5.6.982l1-4.652A15.251,15.251,0,0,0,45.914,0c-5.61,0-9.557,2.982-9.591,7.252-.036,3.159,2.818,4.921,4.968,5.971,2.212,1.075,2.954,1.765,2.946,2.727-.016,1.472-1.764,2.122-3.4,2.147a11.89,11.89,0,0,1-5.828-1.386l-1.029,4.807a17.207,17.207,0,0,0,6.312,1.166c5.963,0,9.863-2.945,9.881-7.506m14.813,7.163h5.249L65.656.4H60.811A2.583,2.583,0,0,0,58.4,2.01l-8.517,20.33h5.959l1.184-3.277H64.3Zm-6.332-7.774,2.987-8.238,1.719,8.238ZM34.777.4l-4.693,21.94H24.409L29.1.4Z"
                                    transform="translate(-35.119 -11.342)" fill="#1434cb" />
                            </g>
                        </svg>
                    </div>
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="63.481" height="49.381" viewBox="0 0 63.481 49.381">
                            <g id="ICON-Mastercard" transform="translate(31.741 24.69)">
                                <g id="Group_1630" data-name="Group 1630" transform="translate(-31.741 -24.69)">
                                    <g id="Group_1629" data-name="Group 1629">
                                        <path id="Path_5498" data-name="Path 5498"
                                            d="M15.433,98.479V95.21a1.94,1.94,0,0,0-1.8-2.069,2.059,2.059,0,0,0-.249,0,2.017,2.017,0,0,0-1.831.926,1.914,1.914,0,0,0-1.722-.926,1.724,1.724,0,0,0-1.526.774V93.27H7.173v5.209H8.317V95.591a1.218,1.218,0,0,1,1.039-1.374,1.2,1.2,0,0,1,.236-.01c.752,0,1.133.491,1.133,1.373v2.9H11.87V95.591a1.228,1.228,0,0,1,1.275-1.384c.774,0,1.144.491,1.144,1.373v2.9ZM32.367,93.27H30.515V91.69H29.37v1.58H28.313v1.035H29.37v2.376c0,1.209.469,1.929,1.809,1.929a2.659,2.659,0,0,0,1.417-.4l-.327-.97a2.091,2.091,0,0,1-1,.294c-.567,0-.752-.349-.752-.872V94.305h1.853Zm9.666-.131a1.537,1.537,0,0,0-1.373.763V93.27H39.538v5.209h1.133V95.558c0-.86.37-1.34,1.112-1.34a1.838,1.838,0,0,1,.708.131l.348-1.068a2.427,2.427,0,0,0-.806-.142Zm-14.613.545a3.9,3.9,0,0,0-2.125-.545c-1.318,0-2.168.632-2.168,1.667,0,.85.632,1.373,1.8,1.537l.534.076c.621.087.915.251.915.545,0,.4-.414.632-1.188.632a2.775,2.775,0,0,1-1.732-.545l-.534.882a3.756,3.756,0,0,0,2.256.676c1.5,0,2.376-.708,2.376-1.7,0-.916-.686-1.4-1.82-1.558l-.534-.077c-.491-.065-.882-.163-.882-.512,0-.381.37-.61.992-.61a3.358,3.358,0,0,1,1.624.447Zm30.359-.545a1.538,1.538,0,0,0-1.373.763V93.27H55.284v5.209h1.133V95.558c0-.86.37-1.34,1.112-1.34a1.838,1.838,0,0,1,.708.131l.348-1.068a2.427,2.427,0,0,0-.806-.142Zm-14.6,2.735A2.632,2.632,0,0,0,45.7,98.612c.086,0,.172,0,.258,0a2.728,2.728,0,0,0,1.874-.621l-.545-.916a2.292,2.292,0,0,1-1.362.469,1.67,1.67,0,0,1,0-3.335,2.291,2.291,0,0,1,1.362.469l.545-.916a2.73,2.73,0,0,0-1.874-.621,2.632,2.632,0,0,0-2.777,2.478c0,.085-.006.171,0,.257Zm10.614,0v-2.6H52.658V93.9a1.977,1.977,0,0,0-1.645-.763,2.739,2.739,0,0,0,0,5.471,1.975,1.975,0,0,0,1.645-.763v.632h1.133Zm-4.217,0a1.565,1.565,0,1,1,0,.126C49.57,95.959,49.571,95.916,49.574,95.875ZM35.9,93.139a2.738,2.738,0,0,0,.076,5.471,3.164,3.164,0,0,0,2.136-.73l-.556-.839a2.481,2.481,0,0,1-1.515.545,1.447,1.447,0,0,1-1.559-1.275H38.35c.011-.141.022-.283.022-.436A2.5,2.5,0,0,0,35.9,93.139Zm-.022,1.014a1.294,1.294,0,0,1,1.318,1.264h-2.7a1.341,1.341,0,0,1,1.384-1.264Zm28.431,1.722v-4.7H63.174V93.9a1.978,1.978,0,0,0-1.646-.763,2.739,2.739,0,0,0,0,5.471,1.975,1.975,0,0,0,1.646-.763v.632h1.133ZM66.2,97.724a.518.518,0,0,1,.2.041.533.533,0,0,1,.167.111.525.525,0,0,1,.113.165.509.509,0,0,1,0,.4.536.536,0,0,1-.113.165.542.542,0,0,1-.167.111.516.516,0,0,1-.2.041.527.527,0,0,1-.489-.317.509.509,0,0,1,0-.4.517.517,0,0,1,.281-.276.534.534,0,0,1,.209-.041Zm0,.921a.387.387,0,0,0,.156-.032.405.405,0,0,0,.127-.657.393.393,0,0,0-.127-.086.386.386,0,0,0-.156-.031.407.407,0,0,0-.16.031.4.4,0,0,0-.128.086.405.405,0,0,0,0,.571.4.4,0,0,0,.128.087.4.4,0,0,0,.16.032Zm.03-.646a.221.221,0,0,1,.143.041.138.138,0,0,1,.05.112.13.13,0,0,1-.04.1.191.191,0,0,1-.114.048l.157.182H66.3l-.146-.18h-.047v.18H66V98Zm-.119.09v.128h.118a.112.112,0,0,0,.065-.016.054.054,0,0,0,.024-.049.052.052,0,0,0-.024-.048.115.115,0,0,0-.065-.016ZM60.09,95.875a1.559,1.559,0,1,1,0,.126C60.086,95.959,60.087,95.916,60.09,95.875Zm-38.271,0v-2.6H20.685V93.9a1.977,1.977,0,0,0-1.645-.763,2.739,2.739,0,0,0,0,5.471,1.975,1.975,0,0,0,1.645-.763v.632h1.133Zm-4.217,0a1.565,1.565,0,1,1,0,.126c0-.042,0-.084,0-.126Z"
                                            transform="translate(-3.885 -49.379)" fill="#231f20" />
                                        <g id="Group_1628" data-name="Group 1628">
                                            <rect id="Rectangle_698" data-name="Rectangle 698" width="17.165"
                                                height="30.847" transform="translate(23.159 4.195)" fill="#ff5f00" />
                                            <path id="Path_5499" data-name="Path 5499"
                                                d="M24.249,19.618A19.583,19.583,0,0,1,31.742,4.2a19.618,19.618,0,1,0,0,30.847A19.584,19.584,0,0,1,24.249,19.618Z"
                                                transform="translate(0 0)" fill="#eb001b" />
                                            <path id="Path_5500" data-name="Path 5500"
                                                d="M100.979,19.618a19.617,19.617,0,0,1-31.74,15.424,19.62,19.62,0,0,0,0-30.847,19.617,19.617,0,0,1,31.74,15.423Z"
                                                transform="translate(-37.497 0)" fill="#f79e1b" />
                                            <path id="Path_5501" data-name="Path 5501"
                                                d="M133.926,68.41v-.631h.254V67.65h-.648v.129h.254v.631Zm1.259,0v-.761h-.2l-.229.524-.229-.524h-.2v.761h.14v-.574l.215.5h.145l.215-.5v.575Z"
                                                transform="translate(-72.316 -36.636)" fill="#f79e1b" />
                                        </g>
                                    </g>
                                </g>
                            </g>
                        </svg>
                    </div>
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="76.089" height="30.777" viewBox="0 0 76.089 30.777">
                            <g id="ICON-Klarna" transform="translate(38.044 15.389)">
                                <g id="Lager_2" data-name="Lager 2" transform="translate(-38.044 -15.389)">
                                    <g id="Layer_1" data-name="Layer 1">
                                        <rect id="Rectangle_699" data-name="Rectangle 699" width="76.089" height="30.777"
                                            rx="5.305" fill="#ffb3c7" />
                                        <path id="Path_5513" data-name="Path 5513"
                                            d="M153.71,35.427a4.713,4.713,0,1,0,0,7.789v.571h2.674V34.856H153.71Zm-2.432,6.193a2.3,2.3,0,1,1,2.423-2.3A2.363,2.363,0,0,1,151.278,41.62Z"
                                            transform="translate(-92.78 -21.941)" />
                                        <rect id="Rectangle_700" data-name="Rectangle 700" width="2.799" height="12.912"
                                            transform="translate(21.459 8.934)" />
                                        <path id="Path_5514" data-name="Path 5514"
                                            d="M123.985,34.618a3.247,3.247,0,0,0-2.754,1.247V34.859h-2.663V43.79h2.695V39.1a1.884,1.884,0,0,1,2.008-2.023c1.175,0,1.851.7,1.851,2V43.79h2.671V38.11A3.536,3.536,0,0,0,123.985,34.618Z"
                                            transform="translate(-75.171 -21.944)" />
                                        <path id="Path_5515" data-name="Path 5515"
                                            d="M76.631,35.427a4.713,4.713,0,1,0,0,7.789v.571H79.3V34.856H76.631ZM74.2,41.62a2.3,2.3,0,1,1,2.423-2.3,2.363,2.363,0,0,1-2.423,2.3Z"
                                            transform="translate(-43.913 -21.941)" />
                                        <path id="Path_5516" data-name="Path 5516"
                                            d="M103.258,36.447V35.284H100.52v8.931h2.744v-4.17c0-1.407,1.525-2.163,2.583-2.163h.032v-2.6A3.333,3.333,0,0,0,103.258,36.447Z"
                                            transform="translate(-63.729 -22.369)" />
                                        <path id="Path_5517" data-name="Path 5517"
                                            d="M178.628,51.032a1.678,1.678,0,1,0,1.678,1.678,1.678,1.678,0,0,0-1.678-1.678Z"
                                            transform="translate(-112.185 -32.354)" />
                                        <path id="Path_5518" data-name="Path 5518"
                                            d="M41.151,24.4h-2.9a7.417,7.417,0,0,1-3,5.986l-1.149.86,4.452,6.071h3.66l-4.1-5.586A10.275,10.275,0,0,0,41.151,24.4Z"
                                            transform="translate(-21.621 -15.471)" />
                                        <rect id="Rectangle_701" data-name="Rectangle 701" width="2.905" height="12.918"
                                            transform="translate(9.222 8.932)" />
                                    </g>
                                </g>
                            </g>
                        </svg>
                    </div>
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="88.163" height="15" viewBox="0 0 88.163 15">
                            <g id="Vorkasse" transform="translate(44.081 7.5)">
                                <g id="Group_2000" data-name="Group 2000" transform="translate(-44.081 -7.5)">
                                    <path id="Path_6920" data-name="Path 6920"
                                        d="M3.525,1.011,6.911,11.516,10.258,1.011h3.5l-5.228,14.4H5.268L0,1.011Z"
                                        transform="translate(0 -0.605)" />
                                    <path id="Path_6921" data-name="Path 6921"
                                        d="M33.026,15.378a5.769,5.769,0,0,1,.386-2.1,5.517,5.517,0,0,1,1.094-1.762,5,5,0,0,1,1.8-1.218,6.3,6.3,0,0,1,2.421-.45,6.237,6.237,0,0,1,2.406.45,5.065,5.065,0,0,1,1.8,1.218,5.481,5.481,0,0,1,1.1,1.762,5.9,5.9,0,0,1,0,4.2,5.465,5.465,0,0,1-1.094,1.757,5.032,5.032,0,0,1-1.8,1.213,6.719,6.719,0,0,1-4.837,0,5.029,5.029,0,0,1-1.8-1.213,5.465,5.465,0,0,1-1.094-1.757A5.741,5.741,0,0,1,33.026,15.378Zm5.7,2.782a2.115,2.115,0,0,0,1.678-.782,2.972,2.972,0,0,0,.669-2,2.959,2.959,0,0,0-.663-2,2.128,2.128,0,0,0-1.683-.767,2.16,2.16,0,0,0-1.7.777,2.922,2.922,0,0,0-.678,1.995,2.947,2.947,0,0,0,.673,2.01A2.161,2.161,0,0,0,38.729,18.16Z"
                                        transform="translate(-19.771 -5.893)" />
                                    <path id="Path_6922" data-name="Path 6922"
                                        d="M72.226,12.883a5.976,5.976,0,0,0-2.158.4,2.664,2.664,0,0,0-1.357,1.084v6.386H65.474V10.11h2.98v2.148a4.756,4.756,0,0,1,1.416-1.683,3.107,3.107,0,0,1,1.822-.634,4.3,4.3,0,0,1,.535.02Z"
                                        transform="translate(-39.196 -5.952)" />
                                    <path id="Path_6923" data-name="Path 6923"
                                        d="M92.336,14.8l-2.783-4.258L88.415,11.7v3.1H85.178V0h3.237V8.554l3.614-4.4h3.426L91.623,8.743l4.159,6.06Z"
                                        transform="translate(-50.992)" />
                                    <path id="Path_6924" data-name="Path 6924"
                                        d="M111.608,17.507a3.086,3.086,0,0,1,1.277-2.56,5.261,5.261,0,0,1,3.307-.985,5.99,5.99,0,0,1,2.376.426v-.466q0-1.8-2.139-1.8a4.762,4.762,0,0,0-1.639.282,7.937,7.937,0,0,0-1.678.886l-.98-2.059a8.31,8.31,0,0,1,4.624-1.386A5.491,5.491,0,0,1,120.485,11a4.16,4.16,0,0,1,1.322,3.312v2.772a1.169,1.169,0,0,0,.153.688.71.71,0,0,0,.54.233v2.693a6.732,6.732,0,0,1-1.377.158q-1.732,0-1.99-1.356l-.059-.466a4.781,4.781,0,0,1-1.688,1.381,4.66,4.66,0,0,1-2.065.48,3.778,3.778,0,0,1-2.653-.97A3.142,3.142,0,0,1,111.608,17.507Zm6.446.406a1.129,1.129,0,0,0,.515-.832v-.95a5.582,5.582,0,0,0-1.832-.346,2.545,2.545,0,0,0-1.485.406,1.225,1.225,0,0,0-.144,1.985,1.688,1.688,0,0,0,1.164.391,2.759,2.759,0,0,0,.965-.178A2.576,2.576,0,0,0,118.054,17.913Z"
                                        transform="translate(-66.814 -5.893)" />
                                    <path id="Path_6925" data-name="Path 6925"
                                        d="M145.923,20.893a7.936,7.936,0,0,1-2.678-.456,5.906,5.906,0,0,1-2.164-1.307l1.109-2.05a6.305,6.305,0,0,0,3.633,1.446q1.317,0,1.317-.832a.73.73,0,0,0-.515-.653q-.257-.119-1.326-.446-.585-.2-.976-.336t-.812-.327a4.882,4.882,0,0,1-.679-.357,4.158,4.158,0,0,1-.51-.4,1.772,1.772,0,0,1-.386-.48,2.486,2.486,0,0,1-.213-.574,2.952,2.952,0,0,1-.08-.708,3.178,3.178,0,0,1,1.208-2.589,4.967,4.967,0,0,1,3.238-.985,5.873,5.873,0,0,1,4.089,1.505l-1.258,2.03a5.531,5.531,0,0,0-3-1.2,1.753,1.753,0,0,0-.916.208.7.7,0,0,0-.341.644.692.692,0,0,0,.356.624,5.85,5.85,0,0,0,1.307.485q.733.228,1.208.391t.97.381a3.624,3.624,0,0,1,.782.445,3.36,3.36,0,0,1,.53.53,1.87,1.87,0,0,1,.346.683,3.255,3.255,0,0,1,.1.856,3.059,3.059,0,0,1-1.174,2.54A4.988,4.988,0,0,1,145.923,20.893Z"
                                        transform="translate(-84.458 -5.893)" />
                                    <path id="Path_6926" data-name="Path 6926"
                                        d="M170.855,20.893a7.937,7.937,0,0,1-2.678-.456,5.9,5.9,0,0,1-2.163-1.307l1.109-2.05a6.306,6.306,0,0,0,3.634,1.446q1.317,0,1.317-.832a.73.73,0,0,0-.515-.653q-.258-.119-1.327-.446-.584-.2-.975-.336t-.812-.327a4.9,4.9,0,0,1-.678-.357,4.127,4.127,0,0,1-.51-.4,1.772,1.772,0,0,1-.386-.48,2.523,2.523,0,0,1-.213-.574,2.952,2.952,0,0,1-.079-.708,3.178,3.178,0,0,1,1.208-2.589,4.967,4.967,0,0,1,3.238-.985,5.874,5.874,0,0,1,4.089,1.505l-1.258,2.03a5.533,5.533,0,0,0-3-1.2,1.752,1.752,0,0,0-.916.208.7.7,0,0,0-.342.644.691.691,0,0,0,.356.624,5.849,5.849,0,0,0,1.307.485q.733.228,1.208.391t.97.381a3.635,3.635,0,0,1,.782.445,3.358,3.358,0,0,1,.53.53,1.857,1.857,0,0,1,.346.683,3.252,3.252,0,0,1,.1.856,3.058,3.058,0,0,1-1.174,2.54A4.987,4.987,0,0,1,170.855,20.893Z"
                                        transform="translate(-99.385 -5.893)" />
                                    <path id="Path_6927" data-name="Path 6927"
                                        d="M196.994,20.893a6.367,6.367,0,0,1-2.4-.441,5.155,5.155,0,0,1-1.812-1.183,5.326,5.326,0,0,1-1.114-1.723,5.452,5.452,0,0,1-.4-2.059,6.039,6.039,0,0,1,.391-2.173,5.537,5.537,0,0,1,1.1-1.8,5.017,5.017,0,0,1,1.807-1.223,6.719,6.719,0,0,1,4.837,0,5,5,0,0,1,1.792,1.213,5.461,5.461,0,0,1,1.084,1.748,5.727,5.727,0,0,1,.381,2.084,7.124,7.124,0,0,1-.079,1.039h-7.872a2.2,2.2,0,0,0,.757,1.6,2.423,2.423,0,0,0,1.619.584,2.8,2.8,0,0,0,1.376-.356,1.859,1.859,0,0,0,.871-.921l2.762.772a4.874,4.874,0,0,1-1.98,2.059A6.144,6.144,0,0,1,196.994,20.893Zm-2.356-6.5h4.624a2.457,2.457,0,0,0-.738-1.6,2.353,2.353,0,0,0-3.149,0A2.456,2.456,0,0,0,194.638,14.388Z"
                                        transform="translate(-114.505 -5.893)" />
                                </g>
                            </g>
                        </svg>
                    </div>

                </div>
            </div>
        </div>

    </form>
    <?php
    return ob_get_clean(); // Return the form HTML
}
add_shortcode('parcel_moving_form', 'parcel_moving_form_shortcode');


// Include admin functionalities
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'admin/parcel-moving-admin.php';
}