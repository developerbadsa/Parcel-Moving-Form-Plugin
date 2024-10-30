document.addEventListener('DOMContentLoaded', function () {
    const sections = document.querySelectorAll('.parcel-moving-form-inputs');
    console.log(sections)

    sections.forEach(section => {
        const fromLocationInput = section.querySelector('.from_location');
        const toLocationInput = section.querySelector('.to_location');
        const fromLocationSuggestions = section.querySelector('.from_location_suggestions_view');
        const toLocationSuggestions = section.querySelector('.to_location_suggestions_view');

        async function fetchLocationData(query) {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/search?q=${query}&format=json&addressdetails=1&countrycodes=DE`
            );
            const data = await response.json();
            return data;
        }

        function showSuggestions(input, suggestionsBox, locations) {
            const inputValue = input.value.toLowerCase();
            const filteredSuggestions = locations.filter(location =>
                location.display_name.toLowerCase().includes(inputValue)
            );

            suggestionsBox.innerHTML = '';

            if (filteredSuggestions.length > 0 && inputValue !== '') {
                filteredSuggestions.forEach(location => {
                    const suggestionItem = document.createElement('li');
                        const locationDetail =
               location?.display_name?.length > 20
                  ? location.display_name.slice(0, 40) + '...'
                  : location.display_name;

            const locationDetailShowSreet = location?.address?.road
               ? location?.address?.road
               : ' ' + ' ' + locationDetail;
                    
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
                        suggestionsBox.style.display = 'none';
                    });
                    suggestionsBox.appendChild(suggestionItem);
                });
                suggestionsBox.style.display = 'inline';
            } else {
                suggestionsBox.style.display = 'none';
            }
        }

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

        document.addEventListener('click', function (e) {
            // Hide suggestions only if clicking outside the current section
            if (!section.contains(e.target)) {
                fromLocationSuggestions.style.display = 'none';
                toLocationSuggestions.style.display = 'none';
            }
        });
    });
});