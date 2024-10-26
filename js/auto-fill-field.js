document.addEventListener('DOMContentLoaded', function () {
   console.log('bismillah')
   const fromLocationInput = document.getElementById('from_location');
   const toLocationInput = document.getElementById('to_location');
   const fromLocationSuggestions = document.getElementById(
      'from_location_suggestions'
   );
   const toLocationSuggestions = document.getElementById(
      'to_location_suggestions'
   );

   // Function to fetch location data from Nominatim API
   async function fetchLocationData(query) {
      const response = await fetch(
         `https://nominatim.openstreetmap.org/search?q=${query}&format=json&addressdetails=1&countrycodes=DE`
      );
      const data = await response.json();
      return data;
   }

   function showSuggestions(input, suggestionsBox, locations) {
      const inputValue = input.value.toLowerCase();

      // Filter locations based on input value
      const filteredSuggestions = locations.filter(location =>
         location.display_name.toLowerCase().includes(inputValue)
      );

      // Clear previous suggestions
      suggestionsBox.innerHTML = '';

      if (filteredSuggestions.length > 0 && inputValue !== '') {
         filteredSuggestions.forEach(location => {
            const locationDetail =
               location?.display_name?.length > 20
                  ? location.display_name.slice(0, 40) + '...'
                  : location.display_name;

            const locationDetailShowSreet = location?.address?.road
               ? location?.address?.road
               : ' ' + ' ' + locationDetail;

            // console.log(location?.display_name);
            const suggestionItem = document.createElement('li');

            suggestionItem.innerHTML = `
            <div class='parcel-moving-suggestion'>
           <div class='parcel-moving-suggestion'>
    <h6 style=" margin: 0px; font-weight: 600; line-height: 17px;">
        ${location?.name}
    </h6>
    <span style="font-size: 12px; font-weight: 400; line-height: 14px; margin: 0px;">
        ${locationDetailShowSreet}
    </span>
</div>
`;
            suggestionItem.addEventListener('click', () => {
               input.value = `${location.display_name}`;
               suggestionsBox.style.display = 'none'; // Hide suggestions after selection
            });
            suggestionsBox.appendChild(suggestionItem);
         });
         suggestionsBox.style.display = 'inline'; // Show suggestions
      } else {
         suggestionsBox.style.display = 'none'; // Hide if no suggestions
      }
   }

   // Event listeners for input fields with API call
   fromLocationInput.addEventListener('input', async () => {
      const query = fromLocationInput.value;
      if (query) {
         const locations = await fetchLocationData(query);
         showSuggestions(fromLocationInput, fromLocationSuggestions, locations);
      } else {
         fromLocationSuggestions.style.display = 'none';
      }
   });

   toLocationInput.addEventListener('input', async () => {
      const query = toLocationInput.value;
      if (query) {
         const locations = await fetchLocationData(query);
         showSuggestions(toLocationInput, toLocationSuggestions, locations);
      } else {
         toLocationSuggestions.style.display = 'none';
      }
   });

   // Hide suggestions when clicking outside the input
   document.addEventListener('click', function (e) {
      if (!fromLocationInput.contains(e.target)) {
         fromLocationSuggestions.style.display = 'none';
      }
      if (!toLocationInput.contains(e.target)) {
         toLocationSuggestions.style.display = 'none';
      }
   });
});
