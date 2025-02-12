document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("analyzeButton").addEventListener("click", uploadImage);
});

function uploadImage() {
    let input = document.getElementById('imageInput');
    let file = input.files[0];
    if (!file) {
        alert("Bitte ein Bild auswählen!");
        return;
    }

    let formData = new FormData();
    formData.append("file", file);

    fetch('http://127.0.0.1:5000/analyze', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('result').innerText = "Ergebnis: " + data.result;
    })
    .catch(error => console.error('Fehler:', error));
}
