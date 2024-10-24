<?php
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
  //   add_submenu_page(
  //     'parcel-moving-submissions',
  //     'Manage Locations',
  //     'Manage Locations',
  //     'manage_options',
  //     'manage-locations',
  //     'parcel_moving_manage_locations'
  // );
}



add_action('admin_menu', 'parcel_moving_add_admin_menu');



// display submisson entries
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








function parcel_moving_manage_locations() {


  global $wpdb;
  $table_name = $wpdb->prefix . 'parcel_locations';


// Handle form submission to add new location
if (isset($_POST['add_location'])) {
  $location_title = sanitize_text_field($_POST['location_title']);
  $location_detail = sanitize_text_field($_POST['detail_location']); // Corrected variable name

  if (!empty($location_title) && !empty($location_detail)) {


      $insertresult = $wpdb->insert($table_name, array('location' => $location_title, 'detail' => $location_detail));

      if ($insertresult) {
          echo '<div class="updated notice is-dismissible"><p>Location added successfully.</p></div>';
      } else {
          // Display error message if insertion failed
          echo '<div class="error notice is-dismissible"><p>Failed to add location. Please try again.</p></div>';
      }
  } else {
      // Display an error if fields are empty
      echo '<div class="error notice is-dismissible"><p>Both fields are required.</p></div>';
  }
}



// Handle deletion of a location
if (isset($_GET['delete_location'])) {
  $delete_id = intval($_GET['delete_location']);
  $wpdb->delete($table_name, array('id' => $delete_id));
  echo '<div class="updated notice is-dismissible"><p>Location deleted successfully.</p></div>';
}


  // Retrieve locations from the database
  $locations = $wpdb->get_results("SELECT * FROM $table_name");

  // Display the form for adding new locations
  echo '<div class="wrap"><h1>Manage Locations</h1>';
  echo '<form method="POST"><input type="text" name="location_title" placeholder="Location Title ex: berlin" required><input type="text" name="detail_location" placeholder="Details ex: berlin, germany" required><input type="submit" name="add_location" class="button button-primary" value="Add Location"></form>';
  
  


// Display existing locations in a table
if ($locations) {
  echo '<h2>Existing Locations</h2><table class="widefat fixed" cellspacing="0">';
  echo '<thead><tr><th>ID</th><th>Location</th><th>Details</th><th>Actions</th></tr></thead>';
  echo '<tbody>';
  foreach ($locations as $location) {
      echo '<tr>';
      echo '<td>' . esc_html($location->id) . '</td>';
      echo '<td>' . esc_html($location->location) . '</td>';
      echo '<td>' . esc_html($location->detail) . '</td>'; // Corrected line
      echo '<td><a href="' . esc_url(add_query_arg('delete_location', $location->id)) . '" onclick="return confirm(\'Are you sure you want to delete this location?\');" class="button button-danger">Delete</a></td>';
      echo '</tr>';
  }
  echo '</tbody></table>';
} else {
  echo '<p>No locations found.</p>';
}
  echo '</div>';
}
