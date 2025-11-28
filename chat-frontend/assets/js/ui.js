// ui.js – DOM manipulation helpers for rendering messages

/** Render all messages from the queue into the chat box */
export function renderMessages(messageQueue) {
  const chatBox = document.getElementById('chat-box');
  if (!chatBox) return;
  
  chatBox.innerHTML = '';
  const messages = messageQueue.toArray();
  
  messages.forEach((msg, index) => {
    const messageWrapper = document.createElement('div');
    messageWrapper.className = 'message-wrapper';
    messageWrapper.className += msg.isUser ? ' user-wrapper' : ' bot-wrapper';
    
    // Create avatar
    const avatar = document.createElement('div');
    avatar.className = 'avatar';
    avatar.className += msg.isUser ? ' user-avatar' : ' bot-avatar';
    
    // Add initials to avatar
    const initials = document.createElement('span');
    initials.className = 'avatar-initials';
    initials.textContent = msg.isUser ? 'U' : 'B';
    avatar.appendChild(initials);
    
    // Create message bubble
    const div = document.createElement('div');
    div.className = msg.isUser ? 'message user' : 'message';
    
    // Handle different message types
    const messageType = msg.type || 'text';
    
    if (messageType === 'text') {
      // Text message
      const textSpan = document.createElement('span');
      textSpan.className = 'message-text';
      textSpan.textContent = msg.text || msg;
      div.appendChild(textSpan);
    } else if (messageType === 'image') {
      // Image message
      const img = document.createElement('img');
      img.src = msg.fileData.url;
      img.alt = msg.fileData.name;
      img.className = 'message-image';
      div.appendChild(img);
    } else if (messageType === 'video') {
      // Video message
      const video = document.createElement('video');
      video.src = msg.fileData.url;
      video.controls = true;
      video.className = 'message-video';
      div.appendChild(video);
    } else if (messageType === 'audio') {
      // Audio message
      const audioPlayer = document.createElement('div');
      audioPlayer.className = 'audio-player';
      
      const audioIcon = document.createElement('div');
      audioIcon.className = 'audio-icon';
      audioIcon.textContent = '🎵';
      
      const audio = document.createElement('audio');
      audio.src = msg.fileData.url;
      audio.controls = true;
      audio.className = 'message-audio';
      
      audioPlayer.appendChild(audioIcon);
      audioPlayer.appendChild(audio);
      div.appendChild(audioPlayer);
    } else if (messageType === 'file') {
      // File message
      const fileCard = document.createElement('div');
      fileCard.className = 'file-card';
      
      const fileIcon = document.createElement('div');
      fileIcon.className = 'file-icon';
      fileIcon.textContent = '📄';
      
      const fileInfo = document.createElement('div');
      fileInfo.className = 'file-info';
      
      const fileName = document.createElement('div');
      fileName.className = 'file-name';
      fileName.textContent = msg.fileData.name;
      
      const fileSize = document.createElement('div');
      fileSize.className = 'file-size';
      fileSize.textContent = msg.fileData.formattedSize;
      
      fileInfo.appendChild(fileName);
      fileInfo.appendChild(fileSize);
      
      const downloadBtn = document.createElement('a');
      downloadBtn.href = msg.fileData.url;
      downloadBtn.download = msg.fileData.name;
      downloadBtn.className = 'file-download';
      downloadBtn.textContent = '⬇';
      downloadBtn.setAttribute('aria-label', 'Download file');
      
      fileCard.appendChild(fileIcon);
      fileCard.appendChild(fileInfo);
      fileCard.appendChild(downloadBtn);
      
      div.appendChild(fileCard);
    }
    
    // Create delete button
    const deleteBtn = document.createElement('button');
    deleteBtn.className = 'delete-btn';
    deleteBtn.innerHTML = '×';
    deleteBtn.setAttribute('aria-label', 'Delete message');
    deleteBtn.setAttribute('data-index', index);
    deleteBtn.setAttribute('data-message-id', msg.id || ''); // Store message ID for backend deletion
    
    // Add click handler for delete
    deleteBtn.addEventListener('click', async (e) => {
      e.stopPropagation();
      const messageId = e.target.getAttribute('data-message-id');
      const indexToDelete = parseInt(e.target.getAttribute('data-index'));
      
      // Delete from local queue immediately (optimistic update)
      if (messageQueue.deleteAt(indexToDelete)) {
        renderMessages(messageQueue);
      }
      
      // If message has an ID, delete from backend in background
      if (messageId && !messageId.startsWith('temp-') && !messageId.startsWith('uploading-')) {
        try {
          // Import API dynamically
          const { API } = await import('./api.js');
          // Delete from backend (don't wait for response)
          API.deleteMessage(messageId).catch(error => {
            console.error('Failed to delete message from backend:', error);
            // Message is already removed from UI, no need to show error
          });
        } catch (error) {
          console.error('Failed to import API:', error);
        }
      }
    });
    
    div.appendChild(deleteBtn);
    
    // Add avatar and message to wrapper
    if (msg.isUser) {
      messageWrapper.appendChild(div);
      messageWrapper.appendChild(avatar);
    } else {
      messageWrapper.appendChild(avatar);
      messageWrapper.appendChild(div);
    }
    
    chatBox.appendChild(messageWrapper);
  });
  
  // Auto-scroll to bottom
  chatBox.scrollTop = chatBox.scrollHeight;
}



