


const sections = document.querySelectorAll('.parcel-moving-form-inputs');
const dateInput = document.querySelector('.date_input');

document.addEventListener("DOMContentLoaded", function() {
const dateInput = document.querySelector(".date-input");
const dateLabel = document.querySelector(".date-picker-label");

// Check if input has value on load
if (dateInput.value) {
    dateLabel.classList.add("active");
}

dateInput.addEventListener("focus", function() {
    dateLabel.classList.add("active");
});

dateInput.addEventListener("blur", function() {
    if (!dateInput.value) {
        dateLabel.classList.remove("active");
    }
});




sections.forEach(section => {
    const fromLocationInput = section.querySelector('.from_location_input');
    const toLocationInput = section.querySelector('.to_location_input');
    const fromLocationSuggestions = section.querySelector('.from_location_suggestions_view');
    const toLocationSuggestions = section.querySelector('.to_location_suggestions_view');
    
    
    
    
    
    
    

    // Initialize AutocompleteService from Google Maps API
    const autocompleteService = new google.maps.places.AutocompleteService();

    function fetchLocationData(query, callback) {
        autocompleteService.getPlacePredictions({
            input: query,
            componentRestrictions: { country: 'DE' } // Restrict to Germany
        }, (predictions, status) => {
            if (status === google.maps.places.PlacesServiceStatus.OK) {
                callback(predictions);
            } else {
                callback([]);
            }
        });
    }

    function showSuggestions(input, suggestionsBox, predictions) {
        suggestionsBox.innerHTML = '';

        if (predictions.length > 0 && input.value !== '') {
            predictions.forEach(prediction => {
                const suggestionItem = document.createElement('li');
                suggestionItem.innerHTML = `
                    <div class='parcel-moving-suggestion'>
                        <h6 style="margin: 0; font-weight: 600; line-height: 17px;">
                            ${prediction.structured_formatting.main_text}
                        </h6>
                        <span style="font-size: 12px; font-weight: 400; line-height: 14px; margin: 0;">
                            ${prediction.structured_formatting.secondary_text || ''}
                        </span>
                    </div>
                `;
                
                suggestionItem.addEventListener('click', () => {
                    input.value = prediction.description;
                    suggestionsBox.style.display = 'none';
                });
                suggestionsBox.appendChild(suggestionItem);
            });
            suggestionsBox.style.display = 'block';
        } else {
            suggestionsBox.style.display = 'none';
        }
    }

    fromLocationInput.addEventListener('input', () => {
        const query = fromLocationInput.value;
        if (query) {
            fetchLocationData(query, predictions => {
                showSuggestions(fromLocationInput, fromLocationSuggestions, predictions);
            });
        } else {
            fromLocationSuggestions.style.display = 'none';
        }
    });

    toLocationInput.addEventListener('input', () => {
        const query = toLocationInput.value;
        if (query) {
            fetchLocationData(query, predictions => {
                showSuggestions(toLocationInput, toLocationSuggestions, predictions);
            });
        } else {
            toLocationSuggestions.style.display = 'none';
        }
    });

    document.addEventListener('click', function (e) {
        if (!section.contains(e.target)) {
            fromLocationSuggestions.style.display = 'none';
            toLocationSuggestions.style.display = 'none';
        }
    });
});
});