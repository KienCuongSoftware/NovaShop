@extends('layouts.admin')

@section('title', 'Thêm sản phẩm')

@section('content')
<div class="page-header">
    <h2>Thêm sản phẩm</h2>
    <a class="btn btn-primary" href="{{ route('admin.products.index', ['page' => session('admin.products.page', 1)]) }}">Quay lại</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label><strong>Danh mục:</strong></label>
                <div class="row">
                    <div class="col-md-6">
                        <select id="parent_category_id" class="form-control mb-2">
                            <option value="">-- Chọn danh mục cha --</option>
                            @foreach($parentCategories ?? [] as $root)
                            <option value="{{ $root->id }}">{{ $root->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <select name="category_id" id="category_id" class="form-control mb-2" required>
                            <option value="">-- Chọn danh mục con --</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="brand_id"><strong>Thương hiệu:</strong></label>
                <select name="brand_id" id="brand_id" class="form-control">
                    <option value="">-- Không chọn --</option>
                    @foreach($brands ?? [] as $b)
                        <option value="{{ $b->id }}" {{ old('brand_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
                <small class="form-text text-muted">Chọn thương hiệu nếu có. Thêm thương hiệu tại <a href="{{ route('admin.brands.index') }}">Thương hiệu</a>.</small>
            </div>
            <div class="form-group">
                <label for="name"><strong>Tên sản phẩm:</strong></label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Nhập tên sản phẩm" value="{{ old('name') }}" required>
            </div>
            <div class="form-group">
                <label for="description"><strong>Mô tả:</strong></label>
                <textarea name="description" id="description" class="form-control" rows="3" placeholder="Mô tả sản phẩm">{{ old('description') }}</textarea>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="price"><strong>Giá mới (₫):</strong></label>
                        <input type="number" name="price" id="price" class="form-control" placeholder="0" value="{{ old('price', 0) }}" min="0" step="1000" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="old_price"><strong>Giá cũ (₫):</strong></label>
                        <input type="number" name="old_price" id="old_price" class="form-control" placeholder="Không có" value="{{ old('old_price') }}" min="0" step="1000">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="quantity"><strong>Số lượng:</strong></label>
                        <input type="number" name="quantity" id="quantity" class="form-control" placeholder="0" value="{{ old('quantity', 0) }}" min="0" required>
                        <small class="form-text text-muted">Khi có biến thể bên dưới, số lượng = tổng tồn kho các biến thể (tự tính).</small>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="image"><strong>Hình ảnh:</strong></label>
                <input type="file" name="image" id="image" class="form-control-file" accept="image/*">
                <div id="preview-image" class="image-preview-wrap mt-2" style="display: none;">
                    <img src="" alt="Preview" class="img-thumbnail" style="width: 200px; height: 200px; object-fit: cover;">
                    <span class="text-muted small d-block">Ảnh mới</span>
                </div>
            </div>
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" class="custom-control-input" name="is_active" id="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="is_active">Đang bán</label>
                </div>
            </div>

            <hr class="my-4">
            <div class="variants-section-create">
                <h5 class="mb-2 font-weight-bold text-dark">Biến thể sản phẩm</h5>
                <p class="text-muted small mb-3">Tùy chọn. Thêm thuộc tính tại <a href="{{ route('admin.attributes.index') }}" target="_blank">Thuộc tính</a>, chọn giá trị và nhập giá, tồn kho. Cùng màu có thể dùng chung một ảnh.</p>

                @if(($attributes ?? collect())->isNotEmpty())
                <div class="card border shadow-sm mb-3">
                    <div class="card-header bg-white py-2 px-3">
                        <h6 class="mb-0 font-weight-bold text-secondary">Tạo nhanh tổ hợp</h6>
                    </div>
                    <div class="card-body py-3">
                        <p class="text-muted small mb-3">Chọn giá trị từng thuộc tính rồi nhấn <strong>Tạo tất cả tổ hợp</strong> để sinh từng dòng.</p>
                        <div class="row mb-3" id="quick-combo-attrs">
                            @foreach($attributes as $attr)
                            @php
                                $attrName = (string) ($attr->name ?? '');
                                $attrNameLower = mb_strtolower($attrName, 'UTF-8');
                                $isColorAttr = str_contains($attrNameLower, 'màu') || str_contains($attrNameLower, 'mau') || str_contains($attrNameLower, 'color');
                            @endphp
                            <div class="col-md-4 col-6 mb-2" data-attr-id="{{ $attr->id }}">
                                <label class="small font-weight-bold text-dark d-block mb-1">{{ $attr->name }}</label>
                                @if($isColorAttr && ($attr->attributeValues->count() ?? 0) > 10)
                                    <div class="quick-combo-toolbar mb-2" data-quick-filter="{{ $attr->id }}">
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control quick-combo-search" placeholder="Tìm màu..." data-attr-id="{{ $attr->id }}" autocomplete="off">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary quick-combo-clear" data-attr-id="{{ $attr->id }}" title="Xóa tìm kiếm">×</button>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between mt-2" style="gap: 0.5rem;">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary quick-combo-select-all" data-attr-id="{{ $attr->id }}">Chọn tất cả</button>
                                                <button type="button" class="btn btn-outline-secondary quick-combo-unselect-all" data-attr-id="{{ $attr->id }}">Bỏ chọn</button>
                                            </div>
                                            <small class="text-muted quick-combo-count" data-attr-id="{{ $attr->id }}"></small>
                                        </div>
                                    </div>
                                    <div class="quick-combo-values quick-combo-values-scroll" data-attr-id="{{ $attr->id }}">
                                        @foreach($attr->attributeValues as $av)
                                        <label class="mb-0 small text-muted quick-combo-item" data-text="{{ mb_strtolower((string)$av->value, 'UTF-8') }}">
                                            <input type="checkbox" class="quick-combo-value mr-1" data-attr-id="{{ $attr->id }}" data-value-id="{{ $av->id }}"> {{ $av->value }}
                                        </label>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="d-flex flex-wrap" style="gap: 0.5rem;">
                                        @foreach($attr->attributeValues as $av)
                                        <label class="mb-0 small text-muted"><input type="checkbox" class="quick-combo-value mr-1" data-attr-id="{{ $attr->id }}" data-value-id="{{ $av->id }}"> {{ $av->value }}</label>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-success btn-sm" id="btn-generate-combos">Tạo tất cả tổ hợp</button>
                    </div>
                </div>
                @endif

                <div class="card border shadow-sm mb-3 overflow-hidden">
                    <div class="card-header bg-light py-2 px-3 d-flex align-items-center justify-content-between">
                        <span class="font-weight-bold text-dark small">Bảng biến thể</span>
                        @if(($attributes ?? collect())->isNotEmpty())
                        <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2" id="btn-add-variant">+ Thêm dòng</button>
                        @endif
                    </div>
                    <div class="table-responsive mb-0">
                <table class="table table-bordered mb-0 variants-attr-table" id="variants-table">
                    <thead>
                        <tr class="bg-light">
                            @if(($attributes ?? collect())->isNotEmpty())
                                @foreach($attributes as $attr)
                                <th class="text-nowrap align-middle py-2 px-2 font-weight-bold text-dark border-bottom" style="font-size: 0.875rem;">{{ $attr->name }}</th>
                                @endforeach
                            @endif
                            <th class="text-right align-middle py-2 px-2 font-weight-bold text-dark border-bottom" style="min-width: 110px; font-size: 0.875rem;">Giá (₫)</th>
                            <th class="text-center align-middle py-2 px-2 font-weight-bold text-dark border-bottom" style="min-width: 90px; font-size: 0.875rem;">Tồn kho</th>
                            <th class="align-middle py-2 px-2 font-weight-bold text-dark border-bottom" style="min-width: 200px; font-size: 0.875rem;">Ảnh</th>
                            <th class="text-center align-middle py-2 px-2 border-bottom" style="width: 48px;"></th>
                        </tr>
                    </thead>
                    <tbody id="variants-tbody">
                        <tr class="variant-row align-middle" data-index="0">
                            @if(($attributes ?? collect())->isNotEmpty())
                                @foreach($attributes as $attr)
                                <td class="py-2 px-2">
                                    <div class="d-flex align-items-center gap-1">
                                        <select name="variants[0][attribute_value][{{ $attr->id }}]" class="form-control form-control-sm flex-grow-1" data-attr-id="{{ $attr->id }}" style="min-width: 0;">
                                            <option value="">—</option>
                                            @foreach($attr->attributeValues as $av)
                                            <option value="{{ $av->id }}">{{ $av->value }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-outline-success btn-sm flex-shrink-0 btn-add-attr-value" data-attr-id="{{ $attr->id }}" data-attr-name="{{ $attr->name }}" data-store-url="{{ route('admin.attributes.values.store', $attr) }}" title="Thêm giá trị mới">＋</button>
                                    </div>
                                </td>
                                @endforeach
                            @endif
                            <td class="py-2 px-2 text-right">
                                <input type="number" name="variants[0][price]" class="form-control form-control-sm text-right" min="0" step="1000" value="0" style="width: 100px;">
                            </td>
                            <td class="py-2 px-2 text-center">
                                <input type="number" name="variants[0][stock]" class="form-control form-control-sm text-center" min="0" value="0" style="width: 70px;">
                            </td>
                            <td class="py-2 px-2">
                                <div class="variant-image-cell-create d-flex align-items-center flex-wrap" style="gap: 0.5rem;">
                                    <div class="variant-preview-wrap-create flex-shrink-0 d-none" style="width: 72px; height: 72px; overflow: hidden; border-radius: 0.25rem; border: 1px solid #dee2e6; background: #f8f9fa;">
                                        <img src="" alt="" class="variant-preview-img-create img-fluid" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <div class="custom-file custom-file-sm">
                                        <input type="file" name="variants[0][image]" class="custom-file-input variant-create-file-input" accept="image/*">
                                        <label class="custom-file-label text-truncate">Chọn ảnh</label>
                                    </div>
                                </div>
                            </td>
                            <td class="py-2 px-2 text-center">
                                <button type="button" class="btn btn-outline-danger btn-sm btn-remove-variant" title="Xóa dòng">×</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                    </div>
                </div>
            </div>
            <style>
            .variants-attr-table thead th { border-top: none; }
            .variants-attr-table tbody tr:hover { background-color: rgba(0,0,0,0.02); }
            .variants-attr-table td { vertical-align: middle !important; }
            .variants-attr-table .custom-file-sm .custom-file-label::after { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
            .variants-attr-table .custom-file-input:lang(vi) ~ .custom-file-label::after { content: "Duyệt"; }
            .variants-attr-table .variant-image-cell-create { min-width: 1px; }
            .variants-attr-table .form-control-sm { font-size: 0.875rem; }

            .quick-combo-values-scroll{
                max-height: 220px;
                overflow-y: auto;
                padding: 0.5rem;
                border: 1px solid #e9ecef;
                border-radius: 0.5rem;
                background: #fff;
            }
            .quick-combo-values-scroll .quick-combo-item{
                display: inline-flex;
                align-items: center;
                margin-right: 0.75rem;
                margin-bottom: 0.5rem;
                white-space: nowrap;
            }
            .variant-image-shared{
                opacity: 0.7;
            }
            </style>

            <hr class="my-4">
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal thông báo Bootstrap --}}
<div class="modal fade" id="variantNotifyModal" tabindex="-1" role="dialog" aria-labelledby="variantNotifyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header border-0 pb-0" id="variantNotifyModalHeader">
                <h5 class="modal-title" id="variantNotifyModalLabel">Thông báo</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Đóng" style="opacity: 0.9;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body py-4" id="variantNotifyModalBody"></div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
<style>
#variantNotifyModalHeader.bg-success .close,
#variantNotifyModalHeader.bg-danger .close,
#variantNotifyModalHeader.bg-primary .close { color: #fff !important; }
#variantNotifyModalHeader.bg-warning .close { color: #212529 !important; }
</style>

<script>
(function() {
    function showVariantNotify(message, type) {
        type = type || 'info';
        var body = document.getElementById('variantNotifyModalBody');
        var header = document.getElementById('variantNotifyModalHeader');
        if (body) body.textContent = message;
        if (header) {
            header.className = 'modal-header border-0 pb-0';
            if (type === 'success') header.classList.add('bg-success', 'text-white');
            else if (type === 'danger' || type === 'error') header.classList.add('bg-danger', 'text-white');
            else if (type === 'warning') header.classList.add('bg-warning');
            else header.classList.add('bg-primary', 'text-white');
        }
        if (typeof $ !== 'undefined' && $.fn.modal) $('#variantNotifyModal').modal('show');
        else alert(message);
    }

    document.getElementById('variants-table') && document.getElementById('variants-table').addEventListener('change', function(e) {
        if (e.target.classList.contains('variant-create-file-input') && e.target.files && e.target.files.length) {
            var label = e.target.nextElementSibling;
            if (label) label.textContent = e.target.files[0].name.length > 18 ? e.target.files[0].name.slice(0, 16) + '…' : e.target.files[0].name;
            var cell = e.target.closest('.variant-image-cell-create');
            var previewWrap = cell ? cell.querySelector('.variant-preview-wrap-create') : null;
            var previewImg = cell ? cell.querySelector('.variant-preview-img-create') : null;
            if (previewImg && previewWrap && e.target.files[0].type.indexOf('image/') === 0) {
                var fr = new FileReader();
                fr.onload = function() {
                    previewImg.src = fr.result;
                    previewWrap.classList.remove('d-none');
                };
                fr.readAsDataURL(e.target.files[0]);
            }
        }
    });

    var categoriesByParent = @json($categoriesByParent ?? []);
    var categoryToParent = @json($categoryToParent ?? []);
    var selectedId = {{ json_encode(old('category_id')) }};
    var parentSelect = document.getElementById('parent_category_id');
    var childSelect = document.getElementById('category_id');

    // Share ảnh theo thuộc tính "Màu/Color": mỗi màu chỉ hiển thị 1 ô chọn ảnh
    var colorAttrId = (function() {
        var attrs = @json(($attributes ?? collect())->map(fn($a) => ['id' => $a->id, 'name' => $a->name])->values()->all());
        for (var i = 0; i < attrs.length; i++) {
            var name = (attrs[i].name || '').toString().toLowerCase();
            if (name.indexOf('màu') !== -1 || name.indexOf('mau') !== -1 || name.indexOf('color') !== -1) {
                return parseInt(attrs[i].id, 10);
            }
        }
        return null;
    })();

    function updateChildOptions() {
        var parentId = parentSelect.value;
        childSelect.innerHTML = '<option value="">-- Chọn danh mục con --</option>';
        if (parentId && categoriesByParent[parentId]) {
            categoriesByParent[parentId].forEach(function(c) {
                var opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.name;
                if (selectedId && c.id == selectedId) opt.selected = true;
                childSelect.appendChild(opt);
            });
        }
    }

    parentSelect.addEventListener('change', function() {
        selectedId = null;
        updateChildOptions();
    });

    if (selectedId && categoryToParent[selectedId]) {
        parentSelect.value = categoryToParent[selectedId];
    }
    updateChildOptions();

    function getColorValueId(row) {
        if (!colorAttrId) return '';
        var sel = row.querySelector('select[data-attr-id="' + colorAttrId + '"]');
        return sel ? (sel.value || '') : '';
    }

    function getColorLabelText(valueId) {
        if (!valueId || !colorAttrId) return '';
        var sel = document.querySelector('select[data-attr-id="' + colorAttrId + '"]');
        if (!sel) return '';
        var opt = sel.querySelector('option[value="' + valueId + '"]');
        return opt ? (opt.textContent || '').trim() : '';
    }

    function resetImageCell(row) {
        var cell = row.querySelector('.variant-image-cell-create');
        if (!cell) return;
        var file = cell.querySelector('input[type="file"]');
        var hint = cell.querySelector('.variant-shared-image-hint');
        if (hint) hint.remove();
        cell.classList.remove('variant-image-shared');
        if (file) file.disabled = false;
    }

    function markSharedImageCell(row, colorText) {
        var cell = row.querySelector('.variant-image-cell-create');
        if (!cell) return;
        var file = cell.querySelector('input[type="file"]');
        var label = cell.querySelector('.custom-file-label');
        var previewWrap = cell.querySelector('.variant-preview-wrap-create');
        var previewImg = cell.querySelector('.variant-preview-img-create');
        if (file) {
            file.disabled = true;
            file.value = '';
        }
        if (label) label.textContent = 'Dùng ảnh chung';
        if (previewWrap) previewWrap.classList.add('d-none');
        if (previewImg) previewImg.src = '';
        cell.classList.add('variant-image-shared');
        if (!cell.querySelector('.variant-shared-image-hint')) {
            var hint = document.createElement('div');
            hint.className = 'variant-shared-image-hint text-muted small mt-1';
            hint.textContent = 'Dùng ảnh của màu ' + (colorText || '');
            cell.appendChild(hint);
        }
    }

    function syncSharedImagesByColor() {
        if (!colorAttrId) return;
        var tbody = document.getElementById('variants-tbody');
        if (!tbody) return;
        var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr.variant-row'));
        rows.forEach(resetImageCell);
        var firstRowByColor = {};
        rows.forEach(function(row) {
            var colorValueId = getColorValueId(row);
            if (!colorValueId) return; // chưa chọn màu => vẫn cho chọn ảnh
            if (!firstRowByColor[colorValueId]) {
                firstRowByColor[colorValueId] = row;
            } else {
                markSharedImageCell(row, getColorLabelText(colorValueId));
            }
        });
    }

    // Quick combo: search + select all for color attribute (and any block rendered with .quick-combo-values-scroll)
    function updateQuickComboCount(attrId) {
        var container = document.querySelector('.quick-combo-values-scroll[data-attr-id="' + attrId + '"]');
        var countEl = document.querySelector('.quick-combo-count[data-attr-id="' + attrId + '"]');
        if (!container || !countEl) return;
        var all = container.querySelectorAll('label.quick-combo-item');
        var visible = 0;
        var checked = 0;
        all.forEach(function(lbl){
            if (lbl.style.display === 'none') return;
            visible++;
            var cb = lbl.querySelector('input[type="checkbox"]');
            if (cb && cb.checked) checked++;
        });
        countEl.textContent = checked + ' đã chọn / ' + visible + ' đang hiện';
    }

    document.querySelectorAll('.quick-combo-search').forEach(function(input){
        var attrId = input.getAttribute('data-attr-id');
        var container = document.querySelector('.quick-combo-values-scroll[data-attr-id="' + attrId + '"]');
        if (!container) return;
        updateQuickComboCount(attrId);
        input.addEventListener('input', function(){
            var q = (this.value || '').trim().toLowerCase();
            container.querySelectorAll('label.quick-combo-item').forEach(function(lbl){
                var t = lbl.getAttribute('data-text') || '';
                lbl.style.display = q === '' || t.indexOf(q) !== -1 ? '' : 'none';
            });
            updateQuickComboCount(attrId);
        });
        container.addEventListener('change', function(e){
            if (e.target && e.target.matches('input[type="checkbox"].quick-combo-value')) {
                updateQuickComboCount(attrId);
            }
        });
    });

    document.querySelectorAll('.quick-combo-clear').forEach(function(btn){
        btn.addEventListener('click', function(){
            var attrId = this.getAttribute('data-attr-id');
            var input = document.querySelector('.quick-combo-search[data-attr-id="' + attrId + '"]');
            if (input) { input.value = ''; input.dispatchEvent(new Event('input')); input.focus(); }
        });
    });
    document.querySelectorAll('.quick-combo-select-all').forEach(function(btn){
        btn.addEventListener('click', function(){
            var attrId = this.getAttribute('data-attr-id');
            var container = document.querySelector('.quick-combo-values-scroll[data-attr-id="' + attrId + '"]');
            if (!container) return;
            container.querySelectorAll('label.quick-combo-item').forEach(function(lbl){
                if (lbl.style.display === 'none') return;
                var cb = lbl.querySelector('input[type="checkbox"]');
                if (cb) cb.checked = true;
            });
            updateQuickComboCount(attrId);
        });
    });
    document.querySelectorAll('.quick-combo-unselect-all').forEach(function(btn){
        btn.addEventListener('click', function(){
            var attrId = this.getAttribute('data-attr-id');
            var container = document.querySelector('.quick-combo-values-scroll[data-attr-id="' + attrId + '"]');
            if (!container) return;
            container.querySelectorAll('label.quick-combo-item').forEach(function(lbl){
                if (lbl.style.display === 'none') return;
                var cb = lbl.querySelector('input[type="checkbox"]');
                if (cb) cb.checked = false;
            });
            updateQuickComboCount(attrId);
        });
    });

    var variantIndex = 1;
    var tbody = document.getElementById('variants-tbody');
    var btnAdd = document.getElementById('btn-add-variant');
    if (tbody && btnAdd) {
        btnAdd.addEventListener('click', function() {
            var firstRow = tbody.querySelector('.variant-row');
            if (!firstRow) return;
            var clone = firstRow.cloneNode(true);
            clone.classList.remove('variant-row');
            clone.classList.add('variant-row');
            clone.setAttribute('data-index', variantIndex);
            clone.querySelectorAll('select, input').forEach(function(el) {
                var n = el.getAttribute('name');
                if (n && n.indexOf('variants[0]') !== -1) {
                    el.setAttribute('name', n.replace('variants[0]', 'variants[' + variantIndex + ']'));
                }
                if (el.type === 'file') {
                    el.value = '';
                    var lbl = el.nextElementSibling;
                    if (lbl && lbl.classList.contains('custom-file-label')) lbl.textContent = 'Chọn ảnh';
                } else if (el.tagName === 'SELECT') el.selectedIndex = 0;
                else if (el.type !== 'file') el.value = el.type === 'number' ? '0' : '';
            });
            clone.querySelectorAll('.variant-preview-wrap-create').forEach(function(w) { w.classList.add('d-none'); });
            clone.querySelectorAll('.variant-preview-img-create').forEach(function(i) { i.src = ''; });
            clone.querySelectorAll('.btn-remove-variant').forEach(function(btn) {
                btn.onclick = function() { clone.remove(); syncSharedImagesByColor(); };
            });
            tbody.appendChild(clone);
            variantIndex++;
            syncSharedImagesByColor();
        });
        tbody.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-remove-variant') && tbody.querySelectorAll('.variant-row').length > 1) {
                e.target.closest('tr').remove();
                syncSharedImagesByColor();
            }
        });
    }

    document.getElementById('variants-table') && document.getElementById('variants-table').addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-add-attr-value');
        if (!btn) return;
        e.preventDefault();
        var attrId = btn.getAttribute('data-attr-id');
        var attrName = btn.getAttribute('data-attr-name');
        var url = btn.getAttribute('data-store-url');
        var value = prompt('Thêm giá trị mới cho ' + attrName + ':');
        if (!value || !value.trim()) return;
        var token = document.querySelector('input[name="_token"]') && document.querySelector('input[name="_token"]').value;
        var formData = new FormData();
        formData.append('_token', token);
        formData.append('value', value.trim());
        fetch(url, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var selects = document.querySelectorAll('select[data-attr-id="' + attrId + '"]');
                selects.forEach(function(sel) {
                    var opt = document.createElement('option');
                    opt.value = data.id;
                    opt.textContent = data.value;
                    sel.appendChild(opt);
                    if (sel.closest('tr') === btn.closest('tr')) sel.value = data.id;
                });
                var quickWrap = document.querySelector('#quick-combo-attrs [data-attr-id="' + attrId + '"] .d-flex.flex-wrap');
                if (quickWrap) {
                    var lab = document.createElement('label');
                    lab.className = 'mr-2 mb-0 small';
                    var cb = document.createElement('input');
                    cb.type = 'checkbox';
                    cb.className = 'quick-combo-value';
                    cb.setAttribute('data-attr-id', attrId);
                    cb.setAttribute('data-value-id', data.id);
                    lab.appendChild(cb);
                    lab.appendChild(document.createTextNode(' ' + data.value));
                    quickWrap.appendChild(lab);
                }
            })
            .catch(function() { showVariantNotify('Không thêm được giá trị.', 'danger'); });
    });

    function cartesian(arrays) {
        return arrays.length === 0 ? [[]] : arrays.reduce(function(acc, curr) {
            return acc.flatMap(function(a) { return curr.map(function(c) { return a.concat([c]); }); });
        }, [[]]);
    }

    function addVariantRowWithCombination(combination, defaultPrice, defaultStock) {
        var firstRow = document.getElementById('variants-tbody') && document.getElementById('variants-tbody').querySelector('.variant-row');
        if (!firstRow) return;
        var clone = firstRow.cloneNode(true);
        clone.setAttribute('data-index', variantIndex);
        clone.querySelectorAll('select, input').forEach(function(el) {
            var n = el.getAttribute('name');
            if (n && n.indexOf('variants[0]') !== -1) {
                el.setAttribute('name', n.replace('variants[0]', 'variants[' + variantIndex + ']'));
            }
            if (el.type === 'file') {
                el.value = '';
                var lbl = el.nextElementSibling;
                if (lbl && lbl.classList.contains('custom-file-label')) lbl.textContent = 'Chọn ảnh';
            }
            if (el.tagName === 'SELECT') {
                var attrId = parseInt(el.getAttribute('data-attr-id'), 10);
                var pair = combination.find(function(p) { return p.attrId === attrId; });
                if (pair) el.value = String(pair.valueId);
                else el.selectedIndex = 0;
            }
            if (el.name && el.name.indexOf('price') !== -1) el.value = defaultPrice;
            if (el.name && el.name.indexOf('stock') !== -1) el.value = defaultStock;
        });
        clone.querySelectorAll('.variant-preview-wrap-create').forEach(function(w) { w.classList.add('d-none'); });
        clone.querySelectorAll('.variant-preview-img-create').forEach(function(i) { i.src = ''; });
        clone.querySelectorAll('.btn-remove-variant').forEach(function(btn) {
            btn.onclick = function() { clone.remove(); syncSharedImagesByColor(); };
        });
        document.getElementById('variants-tbody').appendChild(clone);
        variantIndex++;
        syncSharedImagesByColor();
    }

    var btnGenerate = document.getElementById('btn-generate-combos');
    if (btnGenerate && document.getElementById('variants-tbody')) {
        btnGenerate.addEventListener('click', function() {
            var checkboxes = document.querySelectorAll('#quick-combo-attrs .quick-combo-value:checked');
            var byAttr = {};
            checkboxes.forEach(function(cb) {
                var aid = parseInt(cb.getAttribute('data-attr-id'), 10);
                var vid = parseInt(cb.getAttribute('data-value-id'), 10);
                if (!byAttr[aid]) byAttr[aid] = [];
                byAttr[aid].push({ attrId: aid, valueId: vid });
            });
            var attrIds = Object.keys(byAttr).map(Number);
            if (attrIds.length === 0) {
                alert('Vui lòng chọn ít nhất một giá trị ở từng thuộc tính cần tạo tổ hợp.');
                return;
            }
            var valueArrays = attrIds.map(function(aid) { return byAttr[aid]; });
            var combinations = cartesian(valueArrays);
            var priceEl = document.getElementById('price');
            var defaultPrice = priceEl ? parseInt(priceEl.value, 10) || 0 : 0;
            combinations.forEach(function(combo) {
                addVariantRowWithCombination(combo, defaultPrice, 0);
            });
            if (combinations.length > 0) {
                showVariantNotify('Đã tạo ' + combinations.length + ' biến thể. Bạn có thể sửa giá/tồn kho từng dòng nếu cần.', 'success');
            }
            syncSharedImagesByColor();
        });
    }
    // Khi đổi "màu" trong từng dòng => re-sync để chỉ hiện 1 ô ảnh/màu
    document.getElementById('variants-table') && document.getElementById('variants-table').addEventListener('change', function(e) {
        if (!colorAttrId) return;
        if (e.target && e.target.tagName === 'SELECT' && e.target.getAttribute('data-attr-id') == String(colorAttrId)) {
            syncSharedImagesByColor();
        }
    });

    // init
    syncSharedImagesByColor();
})();
</script>
@endsection
