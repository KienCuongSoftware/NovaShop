(function() {
    function initImagePreview() {
        document.querySelectorAll('input[type="file"][accept*="image"]').forEach(function(input) {
            var previewId = 'preview-' + input.id;
            var previewEl = document.getElementById(previewId);
            if (!previewEl) return;
            input.addEventListener('change', function(e) {
                var file = e.target.files[0];
                var img = previewEl.querySelector('img');
                if (file) {
                    previewEl.style.display = 'block';
                    if (img) {
                        if (img.src && img.src.startsWith('blob:')) URL.revokeObjectURL(img.src);
                        img.src = URL.createObjectURL(file);
                    }
                } else {
                    previewEl.style.display = 'none';
                    if (img && img.src && img.src.startsWith('blob:')) URL.revokeObjectURL(img.src);
                }
            });
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initImagePreview);
    } else {
        initImagePreview();
    }
})();
