<?php if ($authUser ?? null): ?>
            </div>
        </div>
    </div>
<?php else: ?>
</div>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelectorAll('.btn-toggle-sidebar').forEach(btn => {
        btn.addEventListener('click', () => {
            document.body.classList.toggle('sidebar-open');
        });
    });
    document.querySelectorAll('.sidebar-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', () => document.body.classList.remove('sidebar-open'));
    });
</script>
</body>
</html>
