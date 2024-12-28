function previewPhoto(event) {
    const file = event.target.files[0]; // Le premier fichier sélectionné
    const preview = document.getElementById('photoPreview'); // Image où la prévisualisation doit apparaître

    // Vérifie si un fichier est sélectionné
    if (file) {
        const reader = new FileReader(); // Crée un objet FileReader

        // Lorsque le fichier est chargé, afficher l'image dans l'élément <img>
        reader.onload = function(e) {
            preview.src = e.target.result; // Définit l'image à afficher
        };

        reader.readAsDataURL(file); // Lire le fichier en tant que DataURL
    }
}
