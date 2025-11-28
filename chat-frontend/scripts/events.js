// events.js – Bind UI events like sending messages

export function bindEvents(messageQueue) {
  const input = document.getElementById('message-input');
  const sendBtn = document.getElementById('send-button');

  if (!input || !sendBtn) return;

  // Send on button click
  sendBtn.addEventListener('click', () => {
    const text = input.value.trim();
    if (text) {
      messageQueue.enqueue(text);
      input.value = '';
      const event = new CustomEvent('messageAdded');
      window.dispatchEvent(event);
    }
  });

  // Send on Enter key
  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      sendBtn.click();
    }
  });
}
