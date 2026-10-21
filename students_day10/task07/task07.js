const REFRESH_DELAY = 4000;

const form = document.getElementById('chatForm');
const nameInput = document.getElementById('name');
const messageInput = document.getElementById('message');
const sendBtn = document.getElementById('sendBtn');
const messagesBox = document.getElementById('messages');
const emptyText = document.getElementById('empty');
const errorBox = document.getElementById('error');
const statusText = document.getElementById('status');

let lastId = 0;
let loading = false;

try {
    nameInput.value = localStorage.getItem('chatName') || '';
} catch (e) {}

function showError(messages) {
    errorBox.innerHTML = '';
    messages.forEach(text => {
        const line = document.createElement('div');
        line.textContent = text;
        errorBox.appendChild(line);
    });
    errorBox.hidden = false;
}

function formatTime(timestamp) {
    const date = new Date(timestamp * 1000);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function addMessage(message) {
    const item = document.createElement('div');
    item.className = 'mb-2';

    const header = document.createElement('div');
    const name = document.createElement('span');
    name.className = 'message-name';
    name.textContent = message.name;
    const time = document.createElement('span');
    time.className = 'message-time text-muted ms-2';
    time.textContent = formatTime(message.created_at);
    header.appendChild(name);
    header.appendChild(time);

    const text = document.createElement('div');
    text.className = 'message-text';
    text.textContent = message.message;

    item.appendChild(header);
    item.appendChild(text);
    messagesBox.appendChild(item);
}

function loadMessages() {
    if (loading) {
        return Promise.resolve();
    }
    loading = true;

    const atBottom = messagesBox.scrollHeight - messagesBox.scrollTop - messagesBox.clientHeight < 40;

    return fetch(`./task07.php?after=${lastId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('There is a problem to response');
            }
            return response.json();
        })
        .then(data => {
            data.messages.forEach(message => {
                addMessage(message);
                lastId = message.id;
            });
            if (lastId > 0) {
                emptyText.hidden = true;
            }
            if (data.messages.length > 0 && atBottom) {
                messagesBox.scrollTop = messagesBox.scrollHeight;
            }
            statusText.textContent = `updated ${new Date().toLocaleTimeString()}`;
        })
        .catch(error => {
            statusText.textContent = 'connection lost, retrying...';
            console.error('Error:', error);
        })
        .finally(() => {
            loading = false;
        });
}

form.addEventListener('submit', function(event) {
    event.preventDefault();
    errorBox.hidden = true;
    sendBtn.disabled = true;

    try {
        localStorage.setItem('chatName', nameInput.value.trim());
    } catch (e) {}

    fetch('./task07.php', {
        method: 'POST',
        body: new URLSearchParams(new FormData(form))
    })
        .then(response => response.json().then(data => ({ ok: response.ok, data })))
        .then(({ ok, data }) => {
            if (!ok) {
                showError(data.errors);
                return;
            }
            messageInput.value = '';
            messageInput.focus();
            messagesBox.scrollTop = messagesBox.scrollHeight;
            return loadMessages();
        })
        .catch(error => {
            showError(['There is an error.']);
            console.error('Error:', error);
        })
        .finally(() => {
            sendBtn.disabled = false;
        });
});

loadMessages().then(() => {
    messagesBox.scrollTop = messagesBox.scrollHeight;
});
setInterval(loadMessages, REFRESH_DELAY);
