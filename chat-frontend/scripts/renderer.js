// renderer.js – Helper functions for rendering UI elements

/**
 * Create a message DOM element.
 * @param {string} text Message text
 * @param {boolean} isUser Whether this is a user message
 * @returns {HTMLElement}
 */
export function createMessageElement(text, isUser = false) {
  const div = document.createElement('div');
  div.className = isUser ? 'message user' : 'message';
  div.textContent = text;
  return div;
}

/**
 * Scroll chat box to bottom.
 */
export function scrollToBottom() {
  const chatBox = document.getElementById('chat-box');
  if (chatBox) {
    chatBox.scrollTop = chatBox.scrollHeight;
  }
}
