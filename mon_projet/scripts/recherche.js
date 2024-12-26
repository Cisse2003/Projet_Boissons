function fetchSuggestions(query) {
    const suggestionsList = document.getElementById('suggestions');
    
    if (query.length === 0) {
        suggestionsList.innerHTML = '';
        suggestionsList.classList.remove('visible');
        return;
    }

    fetch(`chercher_des_suggestions.php?query=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                suggestionsList.innerHTML = '';
                suggestionsList.classList.add('visible');
                data.forEach(item => {
                    const suggestionItem = document.createElement('div');
                    suggestionItem.classList.add('suggestion-item');
                    suggestionItem.textContent = item.titre;
                    suggestionItem.addEventListener('click', () => {
                        // Redirection vers la recette spécifique
                        window.location.href = `resultats_recherche.php?query=${encodeURIComponent(item.titre)}`;
                    });
                    suggestionsList.appendChild(suggestionItem);
                });
            } else {
                suggestionsList.classList.remove('visible');
                suggestionsList.innerHTML = '';
            }
        })
        .catch(error => console.error('Erreur:', error));
}

function performSearch() {
    const input = document.getElementById('search-input');
    const query = input.value.trim();
    
    if (query.length > 0) {
        window.location.href = `resultats_recherche.php?query=${encodeURIComponent(query)}`;
    } else {
        alert('Veuillez entrer un nom de recette.');
    }
}

