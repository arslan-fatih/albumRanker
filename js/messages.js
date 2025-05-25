// Global variables
let currentConversation = null;
let messagePollingInterval = null;

// Initialize messages page
function initMessagesPage() {
    // Load conversations
    loadConversations();
    
    // Initialize event listeners
    initMessageEventListeners();
    
    // Start message polling
    startMessagePolling();
}

// Initialize message event listeners
function initMessageEventListeners() {
    // Message form
    const messageForm = document.getElementById('messageForm');
    if (messageForm) {
        messageForm.addEventListener('submit', handleSendMessage);
    }
    
    // Conversation search
    const searchInput = document.getElementById('conversationSearch');
    if (searchInput) {
        searchInput.addEventListener('input', handleConversationSearch);
    }
}

// Load conversations
function loadConversations() {
    fetch('messages.php?action=get_conversations')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayConversations(data.conversations);
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Failed to load conversations:', error);
            showAlert('Failed to load conversations. Please try again.', 'danger');
        });
}

// Display conversations
function displayConversations(conversations) {
    const container = document.getElementById('conversationsList');
    if (!container) return;
    
    if (conversations.length === 0) {
        container.innerHTML = '<p class="text-center">No conversations yet</p>';
        return;
    }
    
    container.innerHTML = conversations.map(conversation => `
        <div class="conversation-item ${conversation.other_user_id === currentConversation?.other_user_id ? 'active' : ''}" 
             onclick="loadConversation(${conversation.other_user_id})">
            <div class="conversation-avatar">
                <img src="${conversation.profile_pic}" alt="${conversation.username}">
            </div>
            <div class="conversation-info">
                <h6>${conversation.username}</h6>
                <p>${conversation.last_message}</p>
                ${conversation.unread_count > 0 ? `
                    <span class="badge badge-primary">${conversation.unread_count}</span>
                ` : ''}
            </div>
        </div>
    `).join('');
}

// Load conversation
function loadConversation(userId) {
    currentConversation = { other_user_id: userId };
    
    // Update active conversation
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.classList.remove('active');
        if (item.getAttribute('onclick').includes(userId)) {
            item.classList.add('active');
        }
    });
    
    // Load messages
    loadMessages(userId);
}

// Load messages
function loadMessages(userId, page = 1) {
    const queryParams = new URLSearchParams({
        action: 'get_messages',
        user_id: userId,
        page: page
    });
    
    fetch(`messages.php?${queryParams}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMessages(data.messages);
                // Update pagination
                updateMessagePagination(data.total, data.pages);
                // Scroll to bottom
                scrollToBottom();
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Failed to load messages:', error);
            showAlert('Failed to load messages. Please try again.', 'danger');
        });
}

// Display messages
function displayMessages(messages) {
    const container = document.getElementById('messagesContainer');
    if (!container) return;
    
    if (messages.length === 0) {
        container.innerHTML = '<p class="text-center">No messages yet</p>';
        return;
    }
    
    container.innerHTML = messages.map(message => `
        <div class="message ${message.sender_id === currentUser.id ? 'sent' : 'received'}">
            <div class="message-content">
                <p>${message.content}</p>
                <small>${formatDate(message.created_at)}</small>
            </div>
        </div>
    `).join('');
}

// Update message pagination
function updateMessagePagination(total, pages) {
    const container = document.getElementById('messagePagination');
    if (!container) return;
    
    if (pages <= 1) {
        container.style.display = 'none';
        return;
    }
    
    container.style.display = 'flex';
    container.innerHTML = `
        <button class="btn btn-outline-primary" onclick="loadMessages(${currentConversation.other_user_id}, ${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
            Previous
        </button>
        <span class="mx-3">Page ${currentPage} of ${pages}</span>
        <button class="btn btn-outline-primary" onclick="loadMessages(${currentConversation.other_user_id}, ${currentPage + 1})" ${currentPage === pages ? 'disabled' : ''}>
            Next
        </button>
    `;
}

// Handle send message
function handleSendMessage(event) {
    event.preventDefault();
    
    if (!currentConversation) {
        showAlert('Please select a conversation', 'warning');
        return;
    }
    
    const formData = new FormData(event.target);
    formData.append('action', 'send_message');
    formData.append('receiver_id', currentConversation.other_user_id);
    
    fetch('messages.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear input
            event.target.reset();
            // Reload messages
            loadMessages(currentConversation.other_user_id);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Failed to send message:', error);
        showAlert('Failed to send message. Please try again.', 'danger');
    });
}

// Handle conversation search
function handleConversationSearch(event) {
    const query = event.target.value.toLowerCase();
    const items = document.querySelectorAll('.conversation-item');
    
    items.forEach(item => {
        const username = item.querySelector('h6').textContent.toLowerCase();
        const message = item.querySelector('p').textContent.toLowerCase();
        
        if (username.includes(query) || message.includes(query)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

// Start message polling
function startMessagePolling() {
    // Check for new messages every 5 seconds
    messagePollingInterval = setInterval(checkNewMessages, 5000);
}

// Check for new messages
function checkNewMessages() {
    if (!currentConversation) return;
    
    const queryParams = new URLSearchParams({
        action: 'get_messages',
        user_id: currentConversation.other_user_id,
        page: 1
    });
    
    fetch(`messages.php?${queryParams}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.messages.length > 0) {
                const lastMessage = data.messages[data.messages.length - 1];
                const container = document.getElementById('messagesContainer');
                
                // Check if we need to add new messages
                const lastMessageId = container.querySelector('.message:last-child')?.dataset.messageId;
                if (!lastMessageId || lastMessageId !== lastMessage.id) {
                    loadMessages(currentConversation.other_user_id);
                }
            }
        })
        .catch(error => console.error('Message check failed:', error));
}

// Scroll to bottom of messages
function scrollToBottom() {
    const container = document.getElementById('messagesContainer');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
}

// Delete message
function deleteMessage(messageId) {
    if (!confirm('Are you sure you want to delete this message?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_message');
    formData.append('message_id', messageId);
    
    fetch('messages.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload messages
            loadMessages(currentConversation.other_user_id);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Failed to delete message:', error);
        showAlert('Failed to delete message. Please try again.', 'danger');
    });
}

// Clean up when leaving page
window.addEventListener('beforeunload', () => {
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
    }
});

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', initMessagesPage); 