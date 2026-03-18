@if(count($urls) > 0)
<div class="modal fade" id="productImageModal" tabindex="-1" aria-labelledby="productImageModalLabel" aria-hidden="true" data-backdrop="true" data-keyboard="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 95%;">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-dark font-weight-bold" id="productImageModalLabel" style="font-size: 1rem;">{{ Str::limit($title ?? 'Ảnh sản phẩm', 60) }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body pt-2 pb-4">
                <div class="d-flex flex-column flex-lg-row align-items-start">
                    <div class="position-relative flex-grow-1 mb-3 mb-lg-0 text-center" style="min-height: 300px;">
                        <button type="button" class="product-lightbox-prev btn btn-light border rounded-circle shadow-sm position-absolute" style="left: 10px; top: 50%; transform: translateY(-50%); width: 44px; height: 44px; z-index: 5;" aria-label="Ảnh trước">‹</button>
                        <img id="product-lightbox-image" src="{{ $urls[0] }}" alt="" class="img-fluid" style="max-height: 70vh; object-fit: contain;">
                        <button type="button" class="product-lightbox-next btn btn-light border rounded-circle shadow-sm position-absolute" style="right: 10px; top: 50%; transform: translateY(-50%); width: 44px; height: 44px; z-index: 5;" aria-label="Ảnh sau">›</button>
                    </div>
                    <div class="product-lightbox-thumbs ml-lg-3 pl-lg-2 d-flex flex-row flex-lg-column flex-wrap justify-content-center gap-2" style="max-height: 70vh; overflow-y: auto;">
                        @foreach($urls as $i => $gurl)
                        <button type="button" class="product-lightbox-thumb border rounded flex-shrink-0 p-0 overflow-hidden {{ $i === 0 ? 'active' : '' }}" data-src="{{ $gurl }}" style="width: 56px; height: 56px; background: #fff;">
                            <img src="{{ $gurl }}" alt="" class="w-100 h-100" style="object-fit: cover;">
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
.product-lightbox-thumb.active { border: 2px solid #dc3545 !important; box-shadow: 0 0 0 1px rgba(220,53,69,0.4); }
</style>
<script>
(function() {
    var galleryUrls = @json($urls);
    if (!galleryUrls || galleryUrls.length === 0) return;
    var modal = document.getElementById('productImageModal');
    var lightboxImg = document.getElementById('product-lightbox-image');
    var thumbs = document.querySelectorAll('.product-lightbox-thumb');
    var prevBtn = document.querySelector('.product-lightbox-prev');
    var nextBtn = document.querySelector('.product-lightbox-next');
    function getCurrentIndex() {
        var src = lightboxImg && lightboxImg.src ? lightboxImg.src.replace(/^https?:\/\/[^/]+/, '') : '';
        for (var i = 0; i < galleryUrls.length; i++) {
            if (src.indexOf(galleryUrls[i]) !== -1 || (galleryUrls[i] && src.endsWith(galleryUrls[i].replace(/^\//, '')))) return i;
        }
        return 0;
    }
    function setLightboxIndex(i) {
        i = (i + galleryUrls.length) % galleryUrls.length;
        if (lightboxImg) lightboxImg.src = galleryUrls[i];
        thumbs.forEach(function(t, j) { t.classList.toggle('active', j === i); });
        return i;
    }
    if (prevBtn) prevBtn.addEventListener('click', function() { setLightboxIndex(getCurrentIndex() - 1); });
    if (nextBtn) nextBtn.addEventListener('click', function() { setLightboxIndex(getCurrentIndex() + 1); });
    thumbs.forEach(function(thumb, i) {
        thumb.addEventListener('click', function() { setLightboxIndex(i); });
    });
})();
</script>
@endif
