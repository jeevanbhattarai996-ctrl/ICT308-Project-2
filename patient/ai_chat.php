<?php
include '../includes/db.php';
include '../includes/auth.php';
include '../includes/chatbot_engine.php';

requireRole('patient');

if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [
        [
            'sender'=>'bot',
            'message'=>'Hello! I am the CarePlus Assistant. How can I help you today?',
            'type'=>'normal',
            'link'=>null,
            'link_text'=>null
        ]
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['clear_chat'])) {
        $_SESSION['chat_history'] = [
            [
                'sender'=>'bot',
                'message'=>'Hello! I am the CarePlus Assistant. How can I help you today?',
                'type'=>'normal',
                'link'=>null,
                'link_text'=>null
            ]
        ];
        header('Location: ai_chat.php');
        exit;
    }

    $message = trim($_POST['message'] ?? '');

    if ($message !== '') {
        $_SESSION['chat_history'][] = [
            'sender'=>'patient',
            'message'=>$message,
            'type'=>'normal',
            'link'=>null,
            'link_text'=>null
        ];

        $response = getChatbotResponse($message);

        $_SESSION['chat_history'][] = [
            'sender'=>'bot',
            'message'=>$response['message'],
            'type'=>$response['type'],
            'link'=>$response['link'] ?? null,
            'link_text'=>$response['link_text'] ?? null
        ];
    }

    header('Location: ai_chat.php');
    exit;
}

include '../includes/header.php';
?>

<div class="main-content">
    <?php include '../includes/sidebar.php'; ?>

    <div class="content">
        <h2>AI Chat Assistant</h2>

        <p class="welcome-text">
            Ask about CarePlus services, appointments, doctors and health features.
            This assistant provides general system guidance and does not replace
            professional medical advice.
        </p>

        <div class="section" style="max-width:850px;">
            <div id="chat-box" style="height:480px;overflow-y:auto;padding:20px;background:#f8f9fa;border:1px solid #ddd;border-radius:8px;margin-bottom:15px;">

                <?php foreach ($_SESSION['chat_history'] as $chat): ?>
                    <?php if ($chat['sender'] === 'patient'): ?>

                        <div style="display:flex;justify-content:flex-end;margin-bottom:15px;">
                            <div style="max-width:70%;background:#007bff;color:white;padding:12px 16px;border-radius:15px 15px 3px 15px;">
                                <?php echo nl2br(htmlspecialchars($chat['message'])); ?>
                            </div>
                        </div>

                    <?php else: ?>

                        <div style="display:flex;justify-content:flex-start;margin-bottom:15px;">
                            <div style="max-width:75%;background:<?php echo $chat['type'] === 'emergency' ? '#ffe5e5' : '#ffffff'; ?>;border:1px solid <?php echo $chat['type'] === 'emergency' ? '#dc3545' : '#ddd'; ?>;padding:12px 16px;border-radius:15px 15px 15px 3px;">

                                <?php if ($chat['type'] === 'emergency'): ?>
                                    <strong style="color:#dc3545;">⚠️ Emergency Warning</strong><br><br>
                                <?php endif; ?>

                                <?php echo nl2br(htmlspecialchars($chat['message'])); ?>

                                <?php if (!empty($chat['link'])): ?>
                                    <div style="margin-top:12px;">
                                        <a href="<?php echo htmlspecialchars($chat['link']); ?>" class="btn-primary">
                                            <?php echo htmlspecialchars($chat['link_text']); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>

                    <?php endif; ?>
                <?php endforeach; ?>

            </div>

            <div style="margin-bottom:15px;">
                <strong>Quick questions:</strong>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
                    <button type="button" class="quick-question" data-message="How do I book an appointment?">Book Appointment</button>
                    <button type="button" class="quick-question" data-message="How does the symptom checker work?">Symptom Checker</button>
                    <button type="button" class="quick-question" data-message="How can I view my medical records?">Medical Records</button>
                    <button type="button" class="quick-question" data-message="How do medicine reminders work?">Medicine Reminder</button>
                    <button type="button" class="quick-question" data-message="How do I start a video consultation?">Video Consultation</button>
                    <button type="button" class="quick-question" data-message="What can you do?">Help</button>
                </div>
            </div>

            <form method="POST" id="chat-form" style="display:flex;gap:10px;">
                <input
                    type="text"
                    name="message"
                    id="message"
                    placeholder="Type your message..."
                    autocomplete="off"
                    required
                    style="flex:1;padding:12px;border:1px solid #ccc;border-radius:6px;"
                >
                <button type="submit" class="btn-primary">Send</button>
            </form>

            <form method="POST" style="margin-top:10px;">
                <button type="submit" name="clear_chat" value="1" class="btn-secondary">
                    Clear Chat
                </button>
            </form>
        </div>
    </div>
</div>

<script>
const messageInput = document.getElementById('message');
const chatBox = document.getElementById('chat-box');

document.querySelectorAll('.quick-question').forEach(button => {
    button.addEventListener('click', () => {
        messageInput.value = button.dataset.message;
        messageInput.focus();
    });
});

chatBox.scrollTop = chatBox.scrollHeight;
</script>

<?php include '../includes/footer.php'; ?>