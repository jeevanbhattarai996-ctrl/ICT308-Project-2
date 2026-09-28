<?php
// Footer include
?>
    </div>
    <footer>
        <p>&copy; 2026 CarePlus Healthcare Management System. All rights reserved.</p>
    </footer>

    <?php if (isset($_SESSION['user_id'])): ?>
    <div id="chatbot-widget">
        <button id="chatbot-toggle" aria-label="Open chat assistant">💬</button>
        <div id="chatbot-panel">
            <div id="chatbot-header">CarePlus Assistant</div>
            <div id="chatbot-messages"></div>
            <form id="chatbot-form">
                <input type="text" id="chatbot-input" placeholder="Ask about hours, doctors, booking..." autocomplete="off">
                <button type="submit">➤</button>
            </form>
        </div>
    </div>
    <script src="/careplus_hms/assets/js/chatbot.js"></script>
    <?php endif; ?>

    <script src="/careplus_hms/assets/js/script.js"></script>
</body>
</html>
