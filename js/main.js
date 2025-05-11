document.addEventListener('DOMContentLoaded', function() {

    // --- Anonymous Checkbox Logic ---
    const anonymousCheck = document.getElementById('anonymousCheck');
    const fullNameInput = document.getElementById('fullName');
    const contactInfoInput = document.getElementById('contactInfo');

    if (anonymousCheck && fullNameInput && contactInfoInput) {
        // Function to update field states
        const updateAnonymousFields = () => {
             if (anonymousCheck.checked) {
                fullNameInput.disabled = true;
                contactInfoInput.disabled = true;
                fullNameInput.value = ''; // Clear value
                contactInfoInput.value = ''; // Clear value
                fullNameInput.required = false; // No longer required if using HTML5 validation
                contactInfoInput.required = false;
            } else {
                fullNameInput.disabled = false;
                contactInfoInput.disabled = false;
                // Decide if these should become required when not anonymous
                // fullNameInput.required = true; // Uncomment if name is required when NOT anonymous
                // contactInfoInput.required = false; // Contact info likely remains optional
            }
        };

        // Add event listener
        anonymousCheck.addEventListener('change', updateAnonymousFields);

        // Initial check in case the checkbox is checked on page load (e.g., back button)
        updateAnonymousFields();
    }
    // --- End Anonymous Checkbox Logic ---


    // --- Chat Assistant Logic ---
    const chatWidget = document.getElementById('chat-widget');
    const chatHeader = document.getElementById('chat-header');
    const chatBody = document.getElementById('chat-body');
    const chatToggle = document.getElementById('chat-toggle');
    const chatMessages = document.getElementById('chat-messages');
    const chatInput = document.getElementById('chat-input');
    const chatSend = document.getElementById('chat-send');

    let chatHistory = []; // Store conversation history for context

    // Function to add a message to the chat display
    function addMessage(sender, text, type = 'normal') { // type can be 'normal' or 'error'
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message');
        messageDiv.classList.add(sender); // 'user' or 'assistant' or 'error'
        if (type === 'error') {
             messageDiv.classList.add('error');
             messageDiv.textContent = `Error: ${text}`;
        } else {
            messageDiv.textContent = text;
        }

        chatMessages.appendChild(messageDiv);
        // Scroll to the bottom
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Function to send message to backend and get reply
    async function sendMessageToBackend() {
        const messageText = chatInput.value.trim();
        if (!messageText) return; // Don't send empty messages

        // Display user message immediately
        addMessage('user', messageText);
        chatHistory.push({ role: 'user', content: messageText }); // Add to history

        // Clear input and disable while waiting
        chatInput.value = '';
        chatInput.disabled = true;
        chatSend.disabled = true;

        try {
            const response = await fetch('api/chatAssistant.php', { // Call YOUR backend proxy
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    message: messageText,
                    history: chatHistory // Send history for context
                 })
            });

            if (!response.ok) {
                // Try to get error message from backend response
                let errorData = await response.json().catch(() => ({ error: 'Unknown server error.' }));
                throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.reply) {
                addMessage('assistant', data.reply);
                chatHistory.push({ role: 'assistant', content: data.reply }); // Add assistant reply to history
                 // Optional: Limit history length to prevent very large requests
                 if (chatHistory.length > 10) { // Keep last 10 exchanges (adjust as needed)
                    chatHistory = chatHistory.slice(-10);
                 }
            } else if (data.error) {
                 addMessage('assistant', data.error, 'error');
            } else {
                addMessage('assistant', 'Received an empty response from the assistant.', 'error');
            }

        } catch (error) {
            console.error('Error fetching chat reply:', error);
            addMessage('assistant', `Sorry, there was an error connecting to the assistant. (${error.message})`, 'error');
        } finally {
            // Re-enable input
            chatInput.disabled = false;
            chatSend.disabled = false;
            chatInput.focus();
        }
    }

    // --- Event Listeners for Chat ---
    if (chatWidget) { // Only add listeners if the widget exists
        // Send message on button click
        chatSend.addEventListener('click', sendMessageToBackend);

        // Send message on Enter key press in input
        chatInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault(); // Prevent default form submission (if it were in a form)
                sendMessageToBackend();
            }
        });

        // Toggle chat body visibility
        chatHeader.addEventListener('click', function() {
            const isHidden = chatBody.style.display === 'none';
            chatBody.style.display = isHidden ? 'flex' : 'none';
            chatToggle.textContent = isHidden ? '[-]' : '[+]';
        });
    }
    // --- End Chat Assistant Logic ---

}); // End DOMContentLoaded