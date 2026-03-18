@extends('layouts.admin')

@section('title', 'Sửa sản phẩm')

@section('content')
<div class="page-header">
    <h2>Sửa sản phẩm</h2>
    <a class="btn btn-primary" href="{{ route('admin.products.index', ['page' => session('admin.products.page', 1)]) }}">Quay lại</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ url('admin/products/'.e($product->getRouteKey())) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
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
                @if($product->brand)
                <div class="d-flex align-items-center mb-2 p-2 bg-light rounded">
                    @if($product->brand->logo)
                    <img src="/images/brands/{{ basename($product->brand->logo) }}" alt="{{ $product->brand->name }}" class="mr-2" style="width: 36px; height: 36px; object-fit: contain;">
                    @endif
                    <span class="font-weight-bold">Hiện tại: {{ $product->brand->name }}</span>
                </div>
                @endif
                <select name="brand_id" id="brand_id" class="form-control">
                    <option value="">-- Không chọn --</option>
                    @foreach($brands as $b)
                        <option value="{{ $b->id }}" {{ (old('brand_id', $product->brand_id) == $b->id) ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="name"><strong>Tên sản phẩm:</strong></label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Nhập tên sản phẩm" value="{{ old('name', $product->name) }}" required>
            </div>
            <div class="form-group">
                <label for="description"><strong>Mô tả:</strong></label>
                <textarea name="description" id="description" class="form-control" rows="3" placeholder="Mô tả sản phẩm">{{ old('description', $product->description) }}</textarea>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="price"><strong>Giá mới (₫):</strong></label>
                        <input type="number" name="price" id="price" class="form-control" placeholder="0" value="{{ old('price', $product->price) }}" min="0" step="1000" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="old_price"><strong>Giá cũ (₫):</strong></label>
                        <input type="number" name="old_price" id="old_price" class="form-control" placeholder="Không có" value="{{ old('old_price', $product->old_price) }}" min="0" step="1000">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        @if($product->hasVariants())
                        <label><strong>Số lượng:</strong></label>
                        <div class="form-control-plaintext text-muted small">
                            Tổng = <strong>{{ $product->variants->sum('stock') }}</strong> (tính từ tồn kho từng biến thể, không cần nhập)
                        </div>
                        @else
                        <label for="quantity"><strong>Số lượng:</strong></label>
                        <input type="number" name="quantity" id="quantity" class="form-control" placeholder="0" value="{{ old('quantity', $product->quantity) }}" min="0" required>
                        @endif
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="image"><strong>Hình ảnh:</strong></label>
                <div class="d-flex flex-wrap align-items-start mb-2" style="gap: 1rem;">
                    @if($product->image)
                        <div>
                            <img src="/images/products/{{ basename($product->image) }}" alt="{{ $product->name }}" class="img-thumbnail" style="width: 200px; height: 200px; object-fit: cover;">
                            <span class="text-muted small d-block">Ảnh hiện tại</span>
                        </div>
                    @endif
                    <div id="preview-image" class="image-preview-wrap" style="display: none;">
                        <img src="" alt="Preview" class="img-thumbnail" style="width: 200px; height: 200px; object-fit: cover;">
                        <span class="text-muted small d-block">Ảnh mới</span>
                    </div>
                </div>
                <input type="file" name="image" id="image" class="form-control-file" accept="image/*">
            </div>
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" class="custom-control-input" name="is_active" id="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="is_active">Đang bán</label>
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Cập nhật thông tin sản phẩm</button>
            </div>
        </form>

        <hr class="my-4">
        <div class="variants-section-edit">
            <h5 class="mb-2 font-weight-bold text-dark">Biến thể sản phẩm</h5>
            <p class="text-muted small mb-3">Sửa giá, tồn kho, ảnh từng dòng. Cùng màu dùng chung ảnh — chỉ cần đổi ảnh một dòng màu đó.</p>
        @if($product->variants->isNotEmpty())
            <form action="{{ route('admin.products.variants.bulk', $product) }}" method="POST" enctype="multipart/form-data" id="variants-bulk-form">
                @csrf
                @method('PUT')
                <div class="card border shadow-sm mb-3 overflow-hidden">
                    <div class="card-header bg-light py-2 px-3 d-flex align-items-center justify-content-between">
                        <span class="font-weight-bold text-dark small">Bảng biến thể ({{ $product->variants->count() }} dòng)</span>
                        <button type="submit" class="btn btn-primary btn-sm py-1 px-3">Lưu tất cả</button>
                    </div>
                    <div class="table-responsive mb-0">
                    <table class="table table-bordered mb-0 variants-edit-table">
                        <thead>
                            <tr class="bg-light">
                                <th class="align-middle py-2 font-weight-bold text-dark border-bottom" style="font-size: 0.875rem;">Thuộc tính</th>
                                <th class="align-middle py-2 font-weight-bold text-dark border-bottom" style="min-width: 110px; font-size: 0.875rem;">Giá (₫)</th>
                                <th class="align-middle py-2 font-weight-bold text-dark border-bottom" style="min-width: 90px; font-size: 0.875rem;">Tồn kho</th>
                                <th class="align-middle py-2 font-weight-bold text-dark border-bottom" style="min-width: 180px; font-size: 0.875rem;">Ảnh</th>
                                <th class="align-middle py-2 text-center font-weight-bold text-dark border-bottom" style="width: 100px; font-size: 0.875rem;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($product->variants as $v)
                            <tr>
                                <td class="align-middle">{{ $v->display_name }}</td>
                                <td class="align-middle">
                                    <input type="number" name="variants[{{ $v->id }}][price]" class="form-control form-control-sm text-right" value="{{ old('variants.'.$v->id.'.price', $v->price) }}" min="0" step="1000" style="width: 100px;" required>
                                </td>
                                <td class="align-middle">
                                    <input type="number" name="variants[{{ $v->id }}][stock]" class="form-control form-control-sm text-center" value="{{ old('variants.'.$v->id.'.stock', $v->stock) }}" min="0" style="width: 70px;" required>
                                </td>
                                <td class="align-middle">
                                    <div class="variant-image-cell d-flex align-items-center">
                                        @php $img = $v->images->first(); @endphp
                                        <div class="variant-thumb-wrap flex-shrink-0 mr-2" style="width: 72px; height: 72px; overflow: hidden; border-radius: 0.25rem; border: 1px solid #dee2e6; background: #f8f9fa;">
                                            @if($img)
                                                <img src="/images/products/{{ basename($img->image) }}" alt="" class="variant-current-img img-fluid" style="width: 100%; height: 100%; object-fit: cover;">
                                            @endif
                                            <img src="" alt="" class="variant-preview-img img-fluid d-none" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                        <div class="custom-file custom-file-sm variant-file-wrap flex-shrink-0">
                                            <input type="file" name="variants[{{ $v->id }}][image]" class="custom-file-input variant-edit-file-input" accept="image/*" id="variant-edit-file-{{ $v->id }}">
                                            <label class="custom-file-label text-truncate" for="variant-edit-file-{{ $v->id }}">Đổi ảnh</label>
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle text-center">
                                    <button type="button" class="btn btn-outline-danger btn-sm btn-delete-variant" data-url="{{ route('admin.products.variants.destroy', [$product, $v]) }}" data-csrf="{{ csrf_token() }}">Xóa</button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
            </form>
            <style>
.variants-edit-table thead th { border-top: none; }
.variants-edit-table tbody tr:hover { background-color: rgba(0,0,0,0.02); }
.variants-edit-table .variant-image-cell { min-width: 1px; }
.variants-edit-table .variant-file-wrap { width: 180px; min-width: 180px; flex-shrink: 0; }
.variants-edit-table .custom-file-sm .custom-file-label { padding-right: 3.25rem; overflow: hidden; font-size: 0.875rem; }
.variants-edit-table .custom-file-sm .custom-file-label::after { padding: 0.25rem 0.5rem; font-size: 0.8rem; content: "Duyệt"; width: auto; }
.variants-edit-table .form-control-sm { font-size: 0.875rem; }
.variants-edit-table .variant-thumb-wrap { width: 72px; height: 72px; overflow: hidden; border-radius: 0.35rem; border: 1px solid #dee2e6; background: #f8f9fa; }
</style>
            @else
            <p class="text-muted mb-3">Chưa có biến thể. Thêm biến thể bằng form bên dưới.</p>
            @endif

            <div class="card border shadow-sm">
                <div class="card-header bg-white py-2 px-3">
                    <h6 class="mb-0 font-weight-bold text-dark small">Thêm biến thể mới</h6>
                </div>
                <div class="card-body py-3">
            <form action="{{ route('admin.products.variants.store', $product) }}" method="POST" enctype="multipart/form-data" class="mb-0">
                @csrf
                @if(($attributes ?? collect())->isNotEmpty())
                <div class="form-row mb-3" id="edit-add-variant-attrs">
                    @foreach($attributes as $attr)
                    <div class="col-md-3 col-6 form-group mb-2">
                        <label class="small font-weight-bold text-dark">{{ $attr->name }}</label>
                        <div class="d-flex align-items-center" style="gap: 0.25rem;">
                            <select name="attribute_value[{{ $attr->id }}]" class="form-control form-control-sm flex-grow-1" data-attr-id="{{ $attr->id }}">
                                <option value="">— Không chọn —</option>
                                @foreach($attr->attributeValues as $av)
                                <option value="{{ $av->id }}">{{ $av->value }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-success btn-sm flex-shrink-0 btn-add-attr-value" data-attr-id="{{ $attr->id }}" data-attr-name="{{ $attr->name }}" data-store-url="{{ route('admin.attributes.values.store', $attr) }}" title="Thêm giá trị mới">＋</button>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
                <div class="form-row align-items-end">
                    <div class="col-md-2 col-6 form-group mb-2">
                        <label class="small font-weight-bold text-dark">Giá (₫)</label>
                        <input type="number" name="price" class="form-control form-control-sm" min="0" step="1000" required>
                    </div>
                    <div class="col-md-2 col-6 form-group mb-2">
                        <label class="small font-weight-bold text-dark">Tồn kho</label>
                        <input type="number" name="stock" class="form-control form-control-sm" min="0" required>
                    </div>
                    <div class="col-md-3 col-6 form-group mb-2">
                        <label class="small font-weight-bold text-dark">Ảnh (cùng màu dùng chung)</label>
                        <input type="file" name="image" class="form-control-file form-control-sm" accept="image/*">
                    </div>
                    <div class="col-md-2 col-6 form-group mb-2">
                        <button type="submit" class="btn btn-success btn-sm w-100">Thêm biến thể</button>
                    </div>
                </div>
            </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    document.querySelectorAll('.variant-edit-file-input').forEach(function(input) {
        input.addEventListener('change', function() {
            var label = this.nextElementSibling;
            var cell = this.closest('.variant-image-cell');
            var previewImg = cell ? cell.querySelector('.variant-preview-img') : null;
            var currentImg = cell ? cell.querySelector('.variant-current-img') : null;
            if (label && this.files && this.files.length) {
                label.textContent = this.files[0].name.length > 12 ? this.files[0].name.slice(0, 10) + '…' : this.files[0].name;
                if (previewImg && this.files[0].type.indexOf('image/') === 0) {
                    var fr = new FileReader();
                    fr.onload = function() {
                        previewImg.src = fr.result;
                        previewImg.classList.remove('d-none');
                        if (currentImg) currentImg.classList.add('d-none');
                    };
                    fr.readAsDataURL(this.files[0]);
                }
            } else {
                if (label) label.textContent = 'Đổi ảnh';
                if (previewImg) { previewImg.src = ''; previewImg.classList.add('d-none'); }
                if (currentImg) currentImg.classList.remove('d-none');
            }
        });
    });

    var categoriesByParent = @json($categoriesByParent ?? []);
    var categoryToParent = @json($categoryToParent ?? []);
    var selectedId = {{ json_encode(old('category_id', $product->category_id)) }};
    var parentSelect = document.getElementById('parent_category_id');
    var childSelect = document.getElementById('category_id');

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

    document.getElementById('edit-add-variant-attrs') && document.getElementById('edit-add-variant-attrs').addEventListener('click', function(e) {
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
                var sel = document.querySelector('#edit-add-variant-attrs select[data-attr-id="' + attrId + '"]');
                if (sel) {
                    var opt = document.createElement('option');
                    opt.value = data.id;
                    opt.textContent = data.value;
                    sel.appendChild(opt);
                    sel.value = data.id;
                }
            })
            .catch(function() { alert('Không thêm được giá trị.'); });
    });

    document.querySelectorAll('.btn-delete-variant').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Xóa biến thể này?')) return;
            var url = this.getAttribute('data-url');
            var token = this.getAttribute('data-csrf');
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            var csrf = document.createElement('input');
            csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = token;
            form.appendChild(csrf);
            var method = document.createElement('input');
            method.type = 'hidden'; method.name = '_method'; method.value = 'DELETE';
            form.appendChild(method);
            document.body.appendChild(form);
            form.submit();
        });
    });
})();
</script>
@endsection
