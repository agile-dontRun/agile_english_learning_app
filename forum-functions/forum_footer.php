</main>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.reply-btn').forEach(function(btn) {
        btn.addEventListener('click', function () {
            var parent = document.getElementById('parent_comment_id');
            var replyUser = document.getElementById('reply_to_user_id');
            var box = document.getElementById('replying_to_box');
            if (parent && replyUser && box) {
                parent.value = this.dataset.commentId;
                replyUser.value = this.dataset.userId;
                box.style.display = 'block';
                box.textContent = 'Replying to @' + this.dataset.userName;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    });
});
</script>
</body>
</html>
