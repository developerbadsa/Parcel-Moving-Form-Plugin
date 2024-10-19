// Inside your parcel-moving.js
jQuery(document).ready(function($) {
  $('#parcel-moving-form').on('submit', function(e) {
      e.preventDefault(); // Prevent the default form submission

      // Prepare data to send
      var formData = $(this).serialize(); // Serialize the form data
      formData += '&action=parcel_moving_form_submit'; // Add the action for WordPress AJAX
      
      // AJAX request
      $.ajax({
          url: ajax_object.ajax_url, // Use localized URL
          type: 'POST',
          data: formData,
          success: function(response) {
              // Handle success
              console.log(response);
              if (response.success) {
                  alert(response.data);
              } else {
                  alert('Error: ' + response.data);
              }
          },
          error: function(jqXHR, textStatus, errorThrown) {
              console.log('Error: ' + textStatus, errorThrown); // Log any errors
          }
      });
  });
});
