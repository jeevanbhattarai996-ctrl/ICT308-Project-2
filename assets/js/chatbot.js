document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('chatbot-toggle');
    const panel = document.getElementById('chatbot-panel');
    const form = document.getElementById('chatbot-form');
    const input = document.getElementById('chatbot-input');
    const messages = document.getElementById('chatbot-messages');

    toggle.addEventListener('click', function () {
        panel.classList.toggle('open');
        if (panel.classList.contains('open') && messages.children.length === 0) {
            addMessage('bot', "Hi! I'm the CarePlus Assistant. Ask me about hospital hours, doctors, or booking an appointment.");
        }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const text = input.value.trim();
        if (!text) return;
        addMessage('user', text);
        input.value = '';

        const formData = new FormData();
        formData.append('message', text);

        fetch('/careplus_hms/includes/chatbot_handler.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => addMessage('bot', data.reply))
            .catch(() => addMessage('bot', 'Sorry, something went wrong. Please try again.'));
    });

    function addMessage(sender, text) {
        const div = document.createElement('div');
        div.className = 'chatbot-msg chatbot-msg-' + sender;
        div.textContent = text;
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }
});
