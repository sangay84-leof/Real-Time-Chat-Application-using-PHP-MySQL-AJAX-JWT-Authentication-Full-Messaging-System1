// mock-server.js – Simulates incoming messages for testing

/**
 * Starts a simple interval that pushes random messages into the queue.
 * @param {CircularQueue} queue
 */
export function startMockServer(queue) {
  const sampleMessages = [
    'Hello there!',
    'How are you?',
    "What's up?",
    'This is a mock message.',
    'Testing chat UI.',
  ];

  setInterval(() => {
    const text = sampleMessages[Math.floor(Math.random() * sampleMessages.length)];
    queue.enqueue({ text, isUser: false, timestamp: new Date() });
    // Re‑render UI after adding a message
    const event = new CustomEvent('messageAdded');
    window.dispatchEvent(event);
  }, 3000); // every 3 seconds
}
