document.addEventListener('DOMContentLoaded', function () {
   const locations = [
      'Berlin',
      'Hamburg',
      'Munich',
      'Cologne',
      'Frankfurt',
      'Stuttgart',
      'Düsseldorf',
      'Leipzig',
      'Dortmund',
      'Essen',
      'Bremen',
      'Dresden',
      'Hanover',
      'Nuremberg',
      'Duisburg',
      'Bochum',
      'Wuppertal',
      'Bielefeld',
      'Bonn',
      'Mannheim',
      'Karlsruhe',
      'Wiesbaden',
      'Münster',
      'Augsburg',
      'Gelsenkirchen',
      'Mönchengladbach',
      'Braunschweig',
      'Chemnitz',
      'Kiel',
      'Aachen',
      'Halle',
      'Magdeburg',
      'Freiburg',
      'Krefeld',
      'Lübeck',
      'Oberhausen',
      'Erfurt',
      'Mainz',
      'Rostock',
      'Kassel',
      'Hagen',
      'Saarbrücken',
      'Hamm',
      'Mülheim',
      'Potsdam',
      'Ludwigshafen',
      'Oldenburg',
      'Leverkusen',
      'Osnabrück',
      'Solingen',
   ];

   const fromLocationInput = document.getElementById('from_location');
   const toLocationInput = document.getElementById('to_location');
   const fromLocationSuggestions = document.getElementById(
      'from_location_suggestions'
   );
   const toLocationSuggestions = document.getElementById(
      'to_location_suggestions'
   );

   function showSuggestions(input, suggestionsBox, array) {
      const inputValue = input.value.toLowerCase();
      const filteredSuggestions = array.filter(location =>
         location.toLowerCase().startsWith(inputValue)
      );

      // Clear previous suggestions
      suggestionsBox.innerHTML = '';

      if (filteredSuggestions.length > 0 && inputValue !== '') {
         filteredSuggestions.forEach(location => {
            const suggestionItem = document.createElement('li');
            suggestionItem.textContent = location;
            suggestionItem.addEventListener('click', () => {
               input.value = location;
               suggestionsBox.style.display = 'none'; // Hide suggestions after selection
            });
            suggestionsBox.appendChild(suggestionItem);
         });
         suggestionsBox.style.display = 'block'; // Show suggestions
      } else {
         suggestionsBox.style.display = 'none'; // Hide if no suggestions
      }
   }

   // Event listeners for input fields
   fromLocationInput.addEventListener('input', () =>
      showSuggestions(fromLocationInput, fromLocationSuggestions, locations)
   );
   toLocationInput.addEventListener('input', () =>
      showSuggestions(toLocationInput, toLocationSuggestions, locations)
   );

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
