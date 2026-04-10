</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const replyButtons = document.querySelectorAll('.reply-btn');
    const parentInput = document.getElementById('parent_comment_id');
    const replyToInput = document.getElementById('reply_to_user_id');
    const box = document.getElementById('replying_to_box');

    replyButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            if (!parentInput || !replyToInput || !box) return;
            parentInput.value = btn.dataset.commentId;
            replyToInput.value = btn.dataset.userId;
            box.style.display = 'block';
            box.textContent = `Replying to @${btn.dataset.userName}`;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
});
</script>
</body>
</html>