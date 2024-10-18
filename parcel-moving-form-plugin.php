<?php
/*
Plugin Name: Parcel Moving Form Plugin
Description: A custom multi-step form for parcel moving service.
Version: 1.0
Author: Rahim
*/

// Hook to add the form via shortcode
add_shortcode('parcel_moving_form', 'parcel_moving_form_shortcode');

// Function to display the multi-step form
function parcel_moving_form_shortcode() {
    ob_start();
    ?>
    <form id="parcel-moving-form">
        <div id="step-1">
            <h2>Step 1: Parcel Information</h2>
            <label for="from-location">From Location:</label>
            <input type="text" id="from-location" name="from_location" required>

            <label for="to-location">To Location:</label>
            <input type="text" id="to-location" name="to_location" required>

            <label for="date">Date:</label>
            <input type="date" id="date" name="date" required>

            <button type="button" id="next-step">Next</button>
        </div>
        
        <div id="step-2" style="display:none;">
            <h2>Step 2: Personal Information</h2>
            <label for="first-name">First Name:</label>
            <input type="text" id="first-name" name="first_name" required>

            <label for="last-name">Last Name:</label>
            <input type="text" id="last-name" name="last_name" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="extra-data">Additional Data:</label>
            <textarea id="extra-data" name="extra_data"></textarea>

            <button type="submit">Submit</button>
        </div>
    </form>

    <script>
    document.getElementById('next-step').addEventListener('click', function() {
        document.getElementById('step-1').style.display = 'none';
        document.getElementById('step-2').style.display = 'block';
    });

    document.getElementById('parcel-moving-form').addEventListener('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            alert('Form Submitted Successfully!');
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

// Handle form submission
add_action('wp_ajax_nopriv_parcel_moving_form_submit', 'parcel_moving_form_submit');
add_action('wp_ajax_parcel_moving_form_submit', 'parcel_moving_form_submit');

function parcel_moving_form_submit() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $from_location = sanitize_text_field($_POST['from_location']);
        $to_location = sanitize_text_field($_POST['to_location']);
        $date = sanitize_text_field($_POST['date']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $extra_data = sanitize_textarea_field($_POST['extra_data']);

        // Process form data (e.g., send an email or save to database)
        wp_send_json_success('Form submitted successfully');
    } else {
        wp_send_json_error('Invalid request');
    }

    wp_die();
}
