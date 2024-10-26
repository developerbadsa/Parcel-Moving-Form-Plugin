document.addEventListener('DOMContentLoaded', function () {
   const locations = [
      {title: 'Berlin', detail: 'Berlin, Germany'},
      {title: 'Hamburg', detail: 'Hamburg, Germany'},
      {title: 'Munich', detail: 'Munich, Germany'},
      {title: 'Cologne', detail: 'Cologne, Germany'},
      {title: 'Frankfurt', detail: 'Frankfurt, Germany'},
      {title: 'Stuttgart', detail: 'Stuttgart, Germany'},
      {title: 'Düsseldorf', detail: 'Düsseldorf, Germany'},
      {title: 'Leipzig', detail: 'Leipzig, Germany'},
      {title: 'Dortmund', detail: 'Dortmund, Germany'},
      {title: 'Essen', detail: 'Essen, Germany'},
      {title: 'Bremen', detail: 'Bremen, Germany'},
      {title: 'Dresden', detail: 'Dresden, Germany'},
      {title: 'Hanover', detail: 'Hanover, Germany'},
      {title: 'Nuremberg', detail: 'Nuremberg, Germany'},
      {title: 'Duisburg', detail: 'Duisburg, Germany'},
      {title: 'Bochum', detail: 'Bochum, Germany'},
      {title: 'Wuppertal', detail: 'Wuppertal, Germany'},
      {title: 'Bielefeld', detail: 'Bielefeld, Germany'},
      {title: 'Bonn', detail: 'Bonn, Germany'},
      {title: 'Mannheim', detail: 'Mannheim, Germany'},
      {title: 'Karlsruhe', detail: 'Karlsruhe, Germany'},
      {title: 'Wiesbaden', detail: 'Wiesbaden, Germany'},
      {title: 'Münster', detail: 'Münster, Germany'},
      {title: 'Augsburg', detail: 'Augsburg, Germany'},
      {title: 'Gelsenkirchen', detail: 'Gelsenkirchen, Germany'},
      {title: 'Mönchengladbach', detail: 'Mönchengladbach, Germany'},
      {title: 'Braunschweig', detail: 'Braunschweig, Germany'},
      {title: 'Chemnitz', detail: 'Chemnitz, Germany'},
      {title: 'Kiel', detail: 'Kiel, Germany'},
      {title: 'Aachen', detail: 'Aachen, Germany'},
      {title: 'Halle', detail: 'Halle, Germany'},
      {title: 'Magdeburg', detail: 'Magdeburg, Germany'},
      {title: 'Freiburg', detail: 'Freiburg, Germany'},
      {title: 'Krefeld', detail: 'Krefeld, Germany'},
      {title: 'Lübeck', detail: 'Lübeck, Germany'},
      {title: 'Oberhausen', detail: 'Oberhausen, Germany'},
      {title: 'Erfurt', detail: 'Erfurt, Germany'},
      {title: 'Mainz', detail: 'Mainz, Germany'},
      {title: 'Rostock', detail: 'Rostock, Germany'},
      {title: 'Kassel', detail: 'Kassel, Germany'},
      {title: 'Hagen', detail: 'Hagen, Germany'},
      {title: 'Saarbrücken', detail: 'Saarbrücken, Germany'},
      {title: 'Hamm', detail: 'Hamm, Germany'},
      {title: 'Mülheim', detail: 'Mülheim, Germany'},
      {title: 'Potsdam', detail: 'Potsdam, Germany'},
      {title: 'Ludwigshafen', detail: 'Ludwigshafen, Germany'},
      {title: 'Oldenburg', detail: 'Oldenburg, Germany'},
      {title: 'Leverkusen', detail: 'Leverkusen, Germany'},
      {title: 'Osnabrück', detail: 'Osnabrück, Germany'},
      {title: 'Solingen', detail: 'Solingen, Germany'},
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
         location.detail.toLowerCase().startsWith(inputValue)
      );

      // Clear previous suggestions
      suggestionsBox.innerHTML = '';

      if (filteredSuggestions.length > 0 && inputValue !== '') {
         filteredSuggestions.forEach(location => {
            console.log(location);
            const suggestionItem = document.createElement('li');
            suggestionItem.innerHTML = `
            <div class='parcel-moving-suggetion'>
               <h6>  ${location.title}</h6> 
               <span>  ${location.detail}</span> 
            </div>
            `;
            suggestionItem.addEventListener('click', () => {
               input.value = location.detail;
               suggestionsBox.style.display = 'none'; // Hide suggestions after selection
            });
            suggestionsBox.appendChild(suggestionItem);
         });
         suggestionsBox.style.display = 'inline'; // Show suggestions
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
