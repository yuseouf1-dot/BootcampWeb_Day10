const typeInput = document.getElementById('type');
const brandInput = document.getElementById('brand');
const typeMessage = document.getElementById('typeMessage');
const brandMessage = document.getElementById('brandMessage');

let lastRequestId = 0;

function showMessage(element, result) {
    element.textContent = result.message;
    element.classList.toggle('alert-success', result.valid);
    element.classList.toggle('alert-danger', !result.valid);
}

function checkFields() {
    const requestId = ++lastRequestId;
    const params = new URLSearchParams({
        type: typeInput.value,
        brand: brandInput.value
    });

    fetch(`./task04.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (requestId !== lastRequestId) {
                return;
            }
            showMessage(typeMessage, data.type);
            showMessage(brandMessage, data.brand);
        })
        .catch(error => {
            if (requestId !== lastRequestId) {
                return;
            }
            const failed = { valid: false, message: 'There is an error.' };
            showMessage(typeMessage, failed);
            showMessage(brandMessage, failed);
            console.error('Error:', error);
        });
}

typeInput.addEventListener('input', checkFields);
brandInput.addEventListener('input', checkFields);

checkFields();
