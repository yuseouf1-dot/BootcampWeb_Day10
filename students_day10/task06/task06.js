const form = document.getElementById('productForm');
const messageBox = document.getElementById('message');
const savedTable = document.getElementById('savedTable');
const saved = document.getElementById('saved');

function showMessage(lines, success) {
    messageBox.innerHTML = '';
    lines.forEach(text => {
        const line = document.createElement('div');
        line.textContent = text;
        messageBox.appendChild(line);
    });
    messageBox.classList.toggle('alert-success', success);
    messageBox.classList.toggle('alert-danger', !success);
    messageBox.hidden = false;
}

function showSavedProduct(product) {
    const row = document.createElement('tr');
    ['type', 'brand', 'price', 'stock'].forEach(key => {
        const cell = document.createElement('td');
        cell.textContent = product[key];
        row.appendChild(cell);
    });
    saved.appendChild(row);
    savedTable.hidden = false;
}

form.addEventListener('submit', function(event) {
    event.preventDefault();

    saved.innerHTML = '';
    savedTable.hidden = true;
    messageBox.hidden = true;

    fetch('./task06.php', {
        method: 'POST',
        body: new URLSearchParams(new FormData(form))
    })
        .then(response => response.json().then(data => ({ ok: response.ok, data })))
        .then(({ ok, data }) => {
            if (!ok) {
                showMessage(data.errors, false);
                return;
            }
            showMessage([data.message], true);
            showSavedProduct(data.product);
        })
        .catch(error => {
            showMessage(['There is an error.'], false);
            console.error('Error:', error);
        });
});
