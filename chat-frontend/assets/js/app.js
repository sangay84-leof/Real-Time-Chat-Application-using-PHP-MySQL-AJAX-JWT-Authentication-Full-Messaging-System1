// app.js – Entry point for the chat frontend
// Integrates with PHP backend API

import { CircularQueue } from './queue.js';
import { renderMessages } from './ui.js';
import { API, PollingManager } from './api.js';

// Initialize a queue with a fixed capacity of 5 messages (auto-deletes oldest when full)
export const messageQueue = new CircularQueue(5);

// Helper function to determine file type
function getFileType(file) {
  const mimeType = file.type;
  if (mimeType.startsWith('image/')) return 'image';
  if (mimeType.startsWith('video/')) return 'video';
  if (mimeType.startsWith('audio/')) return 'audio';
  return 'file';
}

// Helper function to format file size
function formatFileSize(bytes) {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

// Audio recording state
let mediaRecorder = null;
let audioChunks = [];
let isRecording = false;

// Bind send button event
function bindEvents() {
  const input = document.getElementById('message-input');
  const sendBtn = document.getElementById('send-button');
  const fileInput = document.getElementById('file-input');
  const attachmentBtn = document.getElementById('attachment-button');
  const micBtn = document.getElementById('mic-button');

  if (!input || !sendBtn || !fileInput || !attachmentBtn || !micBtn) return;

  // Attachment button click - trigger file input
  attachmentBtn.addEventListener('click', () => {
    fileInput.click();
  });

  // Microphone button click - start/stop recording
  micBtn.addEventListener('click', async () => {
    if (!isRecording) {
      // Start recording
      try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);
        audioChunks = [];

        mediaRecorder.ondataavailable = (event) => {
          audioChunks.push(event.data);
        };

        mediaRecorder.onstop = () => {
          const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
          const audioUrl = URL.createObjectURL(audioBlob);

          const message = {
            text: null,
            isUser: true,
            timestamp: new Date(),
            type: 'audio',
            fileData: {
              name: `audio-${Date.now()}.webm`,
              url: audioUrl,
              size: audioBlob.size,
              mimeType: 'audio/webm',
              formattedSize: formatFileSize(audioBlob.size)
            }
          };

          messageQueue.enqueue(message);
          renderMessages(messageQueue);

          // Stop all tracks
          stream.getTracks().forEach(track => track.stop());
        };

        mediaRecorder.start();
        isRecording = true;
        micBtn.classList.add('recording');
        micBtn.textContent = '⏹️'; // Stop icon
      } catch (error) {
        console.error('Error accessing microphone:', error);
        alert('Could not access microphone. Please grant permission.');
      }
    } else {
      // Stop recording
      if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
        isRecording = false;
        micBtn.classList.remove('recording');
        micBtn.textContent = '🎤'; // Mic icon
      }
    }
  });

  // File input change - handle file selection
  fileInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const fileType = getFileType(file);
    const fileUrl = URL.createObjectURL(file);

    const message = {
      text: null,
      isUser: true,
      timestamp: new Date(),
      type: fileType,
      fileData: {
        name: file.name,
        url: fileUrl,
        size: file.size,
        mimeType: file.type,
        formattedSize: formatFileSize(file.size)
      }
    };

    messageQueue.enqueue(message);
    renderMessages(messageQueue);
    
    // Reset file input
    fileInput.value = '';
  });

  // Send on button click
  sendBtn.addEventListener('click', () => {
    const text = input.value.trim();
    if (text) {
      messageQueue.enqueue({ 
        text, 
        isUser: true, 
        timestamp: new Date(),
        type: 'text'
      });
      input.value = '';
      renderMessages(messageQueue);
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

// Polling manager for real-time updates
let pollingManager = null;

// Load messages from backend
async function loadMessages() {
  try {
    const response = await API.getMessages();
    const messages = response.data.messages;
    
    // Clear queue and add messages
    messageQueue.buffer = new Array(messageQueue.capacity);
    messageQueue.head = 0;
    messageQueue.tail = 0;
    messageQueue.size = 0;
    
    messages.forEach(msg => {
      messageQueue.enqueue(msg);
    });
    
    renderMessages(messageQueue);
    
    // Set last message ID for polling
    if (messages.length > 0 && pollingManager) {
      const lastId = Math.max(...messages.map(m => m.id));
      pollingManager.setLastMessageId(lastId);
    }
  } catch (error) {
    console.error('Failed to load messages:', error);
  }
}

// Handle new messages from polling
function handleNewMessages(messages) {
  messages.forEach(msg => {
    messageQueue.enqueue(msg);
  });
  renderMessages(messageQueue);
}

// Check authentication and initialize
async function initialize() {
  try {
    // Check if user is authenticated
    await API.getCurrentUser();
    
    // Load messages
    await loadMessages();
    
    // Start polling for new messages
    pollingManager = new PollingManager(handleNewMessages);
    pollingManager.start();
    
    // Bind UI events
    bindEvents();
    
    // Bind logout button
    const logoutBtn = document.getElementById('logout-button');
    if (logoutBtn) {
      logoutBtn.addEventListener('click', (e) => {
        e.preventDefault();
        
        // Stop polling immediately
        if (pollingManager) {
          pollingManager.stop();
        }
        
        // Call logout API in background (don't wait for it)
        API.logout().catch(err => console.log('Logout API error:', err));
        
        // Redirect immediately for instant logout
        window.location.href = 'auth.html';
      });
    }
  } catch (error) {
    // Not authenticated, redirect to login
    window.location.href = 'auth.html';
  }
}

// Update send button to use backend
const originalBindEvents = bindEvents;
bindEvents = function() {
  const input = document.getElementById('message-input');
  const sendBtn = document.getElementById('send-button');
  const fileInput = document.getElementById('file-input');
  const attachmentBtn = document.getElementById('attachment-button');
  const micBtn = document.getElementById('mic-button');

  if (!input || !sendBtn || !fileInput || !attachmentBtn || !micBtn) return;

  // Attachment button click - trigger file input
  attachmentBtn.addEventListener('click', () => {
    fileInput.click();
  });

  // Microphone button click - start/stop recording
  micBtn.addEventListener('click', async () => {
    if (!isRecording) {
      // Start recording
      try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);
        audioChunks = [];

        mediaRecorder.ondataavailable = (event) => {
          audioChunks.push(event.data);
        };

        mediaRecorder.onstop = async () => {
          const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
          const audioFile = new File([audioBlob], `audio-${Date.now()}.webm`, { type: 'audio/webm' });

          try {
            const response = await API.uploadFile(audioFile);
            // Add the REAL message immediately from response
            if (response.data && response.data.message) {
              messageQueue.enqueue(response.data.message);
              renderMessages(messageQueue);
            }
          } catch (error) {
            console.error('Failed to upload audio:', error);
            alert('Failed to send audio message');
          }

          // Stop all tracks
          stream.getTracks().forEach(track => track.stop());
        };

        mediaRecorder.start();
        isRecording = true;
        micBtn.classList.add('recording');
        micBtn.textContent = '⏹️'; // Stop icon
      } catch (error) {
        console.error('Error accessing microphone:', error);
        alert('Could not access microphone. Please grant permission.');
      }
    } else {
      // Stop recording
      if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
        isRecording = false;
        micBtn.classList.remove('recording');
        micBtn.textContent = '🎤'; // Mic icon
      }
    }
  });

  // File input change - handle file selection
  fileInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;

    // Show uploading indicator immediately
    const uploadingMessage = {
      text: `📤 Uploading ${file.name}...`,
      isUser: true,
      timestamp: new Date().toISOString(),
      type: 'text',
      id: 'uploading-' + Date.now()
    };
    messageQueue.enqueue(uploadingMessage);
    renderMessages(messageQueue);
    
    // Reset file input immediately
    fileInput.value = '';
    
    // Upload file in background (non-blocking)
    API.uploadFile(file)
      .then((response) => {
        // Remove uploading message on success
        const messages = messageQueue.toArray();
        const index = messages.findIndex(m => m.id === uploadingMessage.id);
        if (index !== -1) {
          messageQueue.deleteAt(index);
        }
        
        // Add the REAL message immediately from response
        if (response.data && response.data.message) {
          messageQueue.enqueue(response.data.message);
        }
        
        renderMessages(messageQueue);
      })
      .catch(error => {
        console.error('Failed to upload file:', error);
        // Replace uploading message with error
        const messages = messageQueue.toArray();
        const index = messages.findIndex(m => m.id === uploadingMessage.id);
        if (index !== -1) {
          messageQueue.deleteAt(index);
          // Show error message
          messageQueue.enqueue({
            text: `❌ Failed to upload ${file.name}`,
            isUser: true,
            timestamp: new Date().toISOString(),
            type: 'text',
            id: 'error-' + Date.now()
          });
          renderMessages(messageQueue);
        }
      });
  });

  // Send on button click
  sendBtn.addEventListener('click', async () => {
    const text = input.value.trim();
    if (text) {
      // Clear input immediately for better UX
      input.value = '';
      
      // Add message to local queue immediately (optimistic update)
      const tempMessage = {
        text,
        isUser: true,
        timestamp: new Date().toISOString(),
        type: 'text',
        id: 'temp-' + Date.now() // Temporary ID
      };
      
      messageQueue.enqueue(tempMessage);
      renderMessages(messageQueue);
      
      // Send to backend in background
      try {
        const response = await API.sendMessage(text);
        // Message is already displayed, polling will handle any updates
      } catch (error) {
        console.error('Failed to send message:', error);
        // Remove the optimistic message if send failed
        const messages = messageQueue.toArray();
        const index = messages.findIndex(m => m.id === tempMessage.id);
        if (index !== -1) {
          messageQueue.deleteAt(index);
          renderMessages(messageQueue);
        }
        alert('Failed to send message: ' + error.message);
      }
    }
  });

  // Send on Enter key
  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      sendBtn.click();
    }
  });
};

// When the DOM is ready, initialize
window.addEventListener('DOMContentLoaded', initialize);
