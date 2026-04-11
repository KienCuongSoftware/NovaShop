{{-- Flatpickr: giờ 24h (không AM/PM). $fpMode: 'create' | 'edit' --}}
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script>
(function() {
    var mode = @json($fpMode);
    var minNowIso = @json(now()->toIso8601String());
    var startEl = document.getElementById('start_time');
    var endEl = document.getElementById('end_time');
    if (!startEl || !endEl || typeof flatpickr === 'undefined') return;

    var common = {
        enableTime: true,
        time_24hr: true,
        dateFormat: 'Y-m-d H:i',
        altInput: true,
        altFormat: 'd/m/Y H:i',
        allowInput: true,
        minuteIncrement: 1,
        disableMobile: true
    };

    var startMin = mode === 'create' ? minNowIso : null;
    var startFp, endFp;

    startFp = flatpickr(startEl, Object.assign({}, common, {
        minDate: startMin,
        onChange: function(selected) {
            if (!endFp) return;
            if (selected.length && selected[0]) {
                endFp.set('minDate', selected[0]);
                var endDates = endFp.selectedDates;
                if (endDates.length && endDates[0] < selected[0]) {
                    endFp.setDate(selected[0], false);
                }
            } else if (mode === 'create') {
                endFp.set('minDate', minNowIso);
            } else {
                endFp.set('minDate', null);
            }
        }
    }));

    endFp = flatpickr(endEl, Object.assign({}, common, {
        minDate: mode === 'create' ? minNowIso : null
    }));

    if (startFp.selectedDates.length) {
        endFp.set('minDate', startFp.selectedDates[0]);
    }
})();
</script>
@endpush
