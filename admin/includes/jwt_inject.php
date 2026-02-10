<!-- JWT Token Injection Script -->
<script>
    // Inject JWT token from session into localStorage for API calls
    <?php if (isset($_SESSION['jwt_token'])): ?>
        localStorage.setItem('jwt_token', '<?= $_SESSION['jwt_token'] ?>');
    <?php endif; ?>
</script>
