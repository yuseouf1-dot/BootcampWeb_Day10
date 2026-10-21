const form = document.getElementById('searchForm');
const errorsBox = document.getElementById('errors');
const results = document.getElementById('results');

function showErrors(messages) {
    errorsBox.innerHTML = '';
    messages.forEach(message => {
        const line = document.createElement('div');
        line.textContent = message;
        errorsBox.appendChild(line);
    });
    errorsBox.hidden = false;
}

function showProducts(products) {
    products.forEach(product => {
        const row = document.createElement('tr');
        ['type', 'brand', 'price', 'number', 'stock'].forEach(key => {
            const cell = document.createElement('td');
            cell.textContent = product[key];
            row.appendChild(cell);
        });
        results.appendChild(row);
    });
}

form.addEventListener('submit', function(event) {
    event.preventDefault();

    results.innerHTML = '';
    errorsBox.hidden = true;

    const params = new URLSearchParams(new FormData(form));

    fetch(`./task05.php?${params}`)
        .then(response => response.json().then(data => ({ ok: response.ok, data })))
        .then(({ ok, data }) => {
            if (!ok) {
                showErrors(data.errors);
                return;
            }
            showProducts(data.products);
        })
        .catch(error => {
            showErrors(['There is an error.']);
            console.error('Error:', error);
        });
});
