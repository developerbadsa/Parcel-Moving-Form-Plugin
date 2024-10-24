

jQuery(document).ready(function ($) {
   // Show the modal on button click
   $('#goto-button').on('click', function () {
      $('#extra-data-modal').fadeIn(); // Show the modal
   });

//    Hide the modal on close button click
   $('#close-modal-button, #cancel-modal').on('click', function () {
      $('#extra-data-modal').fadeOut(); // Hide the modal
   });

   // Handle the form submission
   $('#parcel-moving-form').on('submit', function (event) {

      event.preventDefault(); // Prevent default form submission

      // Check if both checkboxes are checked
      // if (!$('#checkbox1').is(':checked') || !$('#checkbox2').is(':checked')) {
      //    alert('You must agree to the terms and conditions to proceed.');
      //    return; // Stop the form submission
      // }

         // if (!checkbox1.is(':checked') || !checkbox2.is(':checked')) {
         //    alert('You must agree to the terms and conditions to proceed.');
         //    return; // Stop the form submission
         // }

      // Collect data from the form
      const finalData = {
         action: 'parcel_moving_form_submit',
         parcel_moving_nonce: $('#parcel_moving_nonce').val(), // Include nonce for security
         from_location: $('#from_location').val(),
         to_location: $('#to_location').val(),
         date: $('#date').val(),
         full_name: $('#full_name').val(),
         last_name: $('#last_name').val(),
         email: $('#email').val(),
         extra_data: $('#extra_data').val(),
      };

      console.log('Sending data:', finalData); // Log the data being sent for debugging

      // AJAX request to submit the form
      $.post(ajax_object.ajax_url, finalData)
         .done(function (response) {
            console.log(response); // Log the response from the server for debugging
            if (response.success) {
               alert('Form submitted successfully!');
               $('#parcel-moving-form')[0].reset(); // Reset the form
               $('#extra-data-modal').fadeOut(); // Close the modal
            } else {
               alert('Error: ' + response.data);
            }
         })
         .fail(function (jqXHR, textStatus, errorThrown) {
            console.error('AJAX request failed:', textStatus, errorThrown); // Log AJAX error
            alert('An error occurred. Please try again.');
         });
   });
});
