<?php
session_start();


    <section class="academic-notice">
        <div class="notice-content">
            <h3>Intellectual Honesty & Integrity</h3>
            <p>Originality is the foundation of your academic career. Learn how to cite correctly and avoid plagiarism.</p>
        </div>
        <a href="plagiarism.php" class="btn-notice">Review Code of Conduct</a>
    </section>

    <section class="resources-section">
        <div class="section-header" style="text-align:center; width:100%;"><h2>Global Academic Resources</h2></div>
        <div class="resource-grid">
            <a href="https://scholar.google.com/" target="_blank" class="resource-card"><div class="resource-icon">🔍</div><h3>Google Scholar</h3><p>Access diverse scholarly articles and theses.</p></a>
            <a href="https://www.nature.com/" target="_blank" class="resource-card"><div class="resource-icon">🧬</div><h3>Nature Journal</h3><p>Cutting-edge multidisciplinary science research.</p></a>
            <a href="https://www.ted.com/talks" target="_blank" class="resource-card"><div class="resource-icon">💡</div><h3>TED Talks</h3><p>Expert insights on education, science, and tech.</p></a>
            <a href="https://www.jstor.org/" target="_blank" class="resource-card"><div class="resource-icon">📚</div><h3>JSTOR</h3><p>Primary sources and academic journal library.</p></a>
        </div>
    </section>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-about"><h3>Spires Academy</h3><p>Fostering intellectual curiosity through rigorous language training and AI-assisted evaluations.</p></div>
            <div class="footer-links"><h4>Quick Links</h4><ul><li><a href="plagiarism.php">Integrity Policy</a></li><li><a href="listening.php">Listening</a></li><li><a href="reading.php">Reading</a></li><li><a href="writing.php">Writing</a></li></ul></div>
        </div>
        <div class="footer-bottom">&copy; 2026 Spires Academy. All rights reserved.</div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkinBtn = document.getElementById('checkinBtn');
            const feedbackArea = document.getElementById('checkinFeedback');
            if (checkinBtn) {
                checkinBtn.addEventListener('click', function() {
                    feedbackArea.innerText = 'Verifying...';
                    fetch('api_checkin.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'daily_checkin' }) })
                    .then(r => r.json()).then(data => {
                        if (data.status === 'success') {
                            feedbackArea.innerText = '✅ Success'; feedbackArea.style.color = 'var(--oxford-blue)';
                            checkinBtn.innerText = 'Attendance Logged'; checkinBtn.disabled = true;
                            document.querySelector('.day.today').classList.add('checked-in');
                        } else { feedbackArea.innerText = '❌ Error'; feedbackArea.style.color = '#dc3545'; }
                    });
                });
            }
        });
    </script>
</body>
</html>