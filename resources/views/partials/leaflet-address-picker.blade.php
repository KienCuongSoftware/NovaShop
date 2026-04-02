{{--
  Partial: chọn địa chỉ bằng OpenStreetMap (Leaflet + Nominatim).
  Cần có trên trang: #address (input text), #lat, #lng (hidden).
  Optional: @param string $mapId = 'map' (id thẻ div bản đồ)
  Optional: @param array $initialLatLng = [10.762622, 106.660172] (HCM)
  Optional: @param bool $showGeolocate = true (nút "Lấy vị trí hiện tại")
--}}
<style>
.checkout-geolocate-btn {
    border-radius: 1rem !important;
    font-weight: 600;
    padding: 0.65rem 1.35rem !important;
    font-size: 1rem !important;
    min-height: 2.75rem;
    line-height: 1.35;
}
.leaflet-address-map {
    border-radius: 1rem;
    overflow: hidden;
}
</style>
@php
    $mapId = $mapId ?? 'map';
    $initialLat = $initialLatLng[0] ?? 10.762622;
    $initialLng = $initialLatLng[1] ?? 106.660172;
    $showGeolocate = $showGeolocate ?? true;
@endphp
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<div class="mb-3">
    @if($showGeolocate ?? true)
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <button type="button" id="btn-geolocate" class="btn btn-danger btn-lg checkout-geolocate-btn">
                Lấy vị trí hiện tại
            </button>
        </div>
    @endif
    <div id="{{ $mapId }}" class="leaflet-address-map" style="height: 380px; border: 1px solid #dee2e6;"></div>
    @if($showGeolocate ?? true)
    <div class="modal fade" id="geolocate-error-modal-{{ $mapId }}" tabindex="-1" role="dialog" aria-labelledby="geolocate-error-title-{{ $mapId }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius: 1rem;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title text-danger" id="geolocate-error-title-{{ $mapId }}">Không lấy được vị trí</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body pt-2">
                    <p class="mb-0">Trình duyệt đã từ chối hoặc chưa cấp quyền vị trí. Bạn có thể bật quyền trong biểu tượng ổ khóa / thông tin trang, hoặc chọn vị trí trên bản đồ và gõ địa chỉ.</p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-danger" data-dismiss="modal" style="border-radius: 0.75rem;">Đã hiểu</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
<script>
(function() {
    var mapEl = document.getElementById('{{ $mapId }}');
    var addressInput = document.getElementById('address');
    var latInput = document.getElementById('lat');
    var lngInput = document.getElementById('lng');
    if (!mapEl || !addressInput || !latInput || !lngInput) return;

    var initialLat = {{ $initialLat }};
    var initialLng = {{ $initialLng }};
    if (latInput.value && lngInput.value) {
        initialLat = parseFloat(latInput.value);
        initialLng = parseFloat(lngInput.value);
    }

    var map = L.map('{{ $mapId }}').setView([initialLat, initialLng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    var marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);

    function updateLocation(lat, lng) {
        latInput.value = lat;
        lngInput.value = lng;
        fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng + '&accept-language=vi', {
            headers: { 'Accept': 'application/json' }
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data && data.display_name) addressInput.value = data.display_name;
        }).catch(function() {});
    }

    map.on('click', function(e) {
        var lat = e.latlng.lat, lng = e.latlng.lng;
        marker.setLatLng(e.latlng);
        updateLocation(lat, lng);
    });
    marker.on('dragend', function() {
        var ll = marker.getLatLng();
        updateLocation(ll.lat, ll.lng);
    });

    var dropdown = document.getElementById('address-suggest-dropdown');
    var searchTimeout;
    function doSearch(q, useDropdown) {
        if (!q || q.length < 2) return;
        fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(q) + '&limit=6&accept-language=vi', {
            headers: { 'Accept': 'application/json' }
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (!data || data.length === 0) {
                if (useDropdown && dropdown) dropdown.classList.add('d-none');
                return;
            }
            if (useDropdown && dropdown) {
                dropdown.innerHTML = '';
                dropdown.classList.remove('d-none');
                data.forEach(function(place) {
                    var li = document.createElement('div');
                    li.className = 'address-suggest-item';
                    li.textContent = place.display_name;
                    li.style.cursor = 'pointer';
                    li.style.padding = '0.5rem 0.75rem';
                    li.style.borderBottom = '1px solid #eee';
                    li.addEventListener('click', function() {
                        var lat = parseFloat(place.lat), lng = parseFloat(place.lon);
                        map.setView([lat, lng], 16);
                        marker.setLatLng([lat, lng]);
                        latInput.value = lat;
                        lngInput.value = lng;
                        addressInput.value = place.display_name;
                        dropdown.innerHTML = '';
                        dropdown.classList.add('d-none');
                    });
                    dropdown.appendChild(li);
                });
            } else {
                var place = data[0];
                var lat = parseFloat(place.lat), lng = parseFloat(place.lon);
                map.setView([lat, lng], 16);
                marker.setLatLng([lat, lng]);
                latInput.value = lat;
                lngInput.value = lng;
                addressInput.value = place.display_name;
            }
        }).catch(function() {});
    }
    addressInput.addEventListener('input', function() {
        var q = this.value.trim();
        if (dropdown) { dropdown.innerHTML = ''; dropdown.classList.add('d-none'); }
        clearTimeout(searchTimeout);
        if (q.length < 2) return;
        searchTimeout = setTimeout(function() { doSearch(q, !!dropdown); }, 350);
    });
    if (dropdown) {
        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target) && e.target !== addressInput) dropdown.classList.add('d-none');
        });
    }

    var btnGeolocate = document.getElementById('btn-geolocate');
    if (btnGeolocate && navigator.geolocation) {
        btnGeolocate.addEventListener('click', function() {
            btnGeolocate.disabled = true;
            btnGeolocate.textContent = 'Đang lấy vị trí...';
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    var lat = pos.coords.latitude, lng = pos.coords.longitude;
                    map.setView([lat, lng], 16);
                    marker.setLatLng([lat, lng]);
                    updateLocation(lat, lng);
                    btnGeolocate.disabled = false;
                    btnGeolocate.textContent = 'Lấy vị trí hiện tại';
                },
                function() {
                    btnGeolocate.disabled = false;
                    btnGeolocate.textContent = 'Lấy vị trí hiện tại';
                    var errModal = document.getElementById('geolocate-error-modal-{{ $mapId }}');
                    if (errModal && typeof window.jQuery !== 'undefined' && jQuery.fn.modal) {
                        jQuery(errModal).modal('show');
                    } else {
                        alert('Không lấy được vị trí. Kiểm tra quyền trình duyệt.');
                    }
                }
            );
        });
    }

    window._leafletMapInstances = window._leafletMapInstances || {};
    window._leafletMapInstances['{{ $mapId }}'] = map;
    window.refreshLeafletMap = function(id) {
        var m = window._leafletMapInstances && window._leafletMapInstances[id];
        if (m) { m.invalidateSize(); }
    };
    setTimeout(function() { map.invalidateSize(); }, 300);
})();
</script>
