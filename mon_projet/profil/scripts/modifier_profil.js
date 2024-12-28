// Gestion de la prévisualisation de l'image
        document.getElementById('profile_picture').addEventListener('change', function (event) {
            if (event.target.files && event.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('profile-picture-preview').src = e.target.result;
                };
                reader.readAsDataURL(event.target.files[0]);
            }
        });
