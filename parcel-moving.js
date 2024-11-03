jQuery(document).ready(function ($) {
   // Show the modal on button click
   $('.goto-button').on('click', function () {
   
   console.log('clicked')
   
      $(this).closest('.parcel-moving-form').find('.extra-data-modal').fadeIn(); // Show the modal
   });

   // Hide the modal on close button click
   $('.close-modal-button, .cancel-modal').on('click', function () {
      $(this).closest('.extra-data-modal').fadeOut(); // Hide the modal
   });

   // Handle form submission
   $('.parcel-moving-form').on('submit', function (event) {
      event.preventDefault(); // Prevent default form submission

      const $form = $(this); // Reference to the current form

      // Collect data from the form
      const finalData = {
         action: 'parcel_moving_form_submit',
         parcel_moving_nonce: $form.find('[name="parcel_moving_nonce"]').val(), // Include nonce for security
         from_location: $form.find('.from_location_input').val(),
         to_location: $form.find('.to_location_input').val(),
         date: $form.find('.date_input').val(),
         full_name: $form.find('.full_name_input').val(),
         last_name: $form.find('.last_name_input').val(),
         email: $form.find('.email_input').val(),
         extra_data: $form.find('.extra_data_select').val(),
      };

      console.log('Sending data:', finalData); // Log the data being sent for debugging

      // AJAX request to submit the form
      $.post(ajax_object.ajax_url, finalData)
         .done(function (response) {
            console.log(response); // Log the response from the server for debugging
            if (response.success) {
               alert('Form submitted successfully!');
               $form[0].reset(); // Reset the form
               $form.find('.extra-data-modal').fadeOut(); // Close the modal
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