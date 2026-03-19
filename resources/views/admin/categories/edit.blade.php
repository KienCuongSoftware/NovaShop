@extends('layouts.admin')

@section('title', 'Sửa danh mục')

@section('content')
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb bg-transparent p-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.categories.index') }}">Danh mục</a></li>
        <li class="breadcrumb-item active" aria-current="page">Sửa: {{ $category->name }}</li>
    </ol>
</nav>

<div class="page-header">
    <h2>Sửa danh mục</h2>
    <a class="btn btn-outline-secondary" href="{{ route('admin.categories.index', ['page' => session('admin.categories.page', 1)]) }}">← Quay lại</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.categories.update', $category) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="name">Tên danh mục <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" class="form-control form-control-lg" placeholder="Nhập tên danh mục" required>
                    </div>
                    <div class="form-group">
                        <label for="parent_id">Danh mục cha</label>
                        <select name="parent_id" id="parent_id" class="form-control">
                            <option value="">— Không (danh mục gốc) —</option>
                            @foreach($parentCategories ?? [] as $root)
                                @if(!in_array($root->id, $excludeIds ?? []))
                                <option value="{{ $root->id }}" {{ old('parent_id', $category->parent_id) == $root->id ? 'selected' : '' }}>{{ $root->name }}</option>
                                @endif
                                @foreach($root->children ?? [] as $child)
                                    @if(!in_array($child->id, $excludeIds ?? []))
                                    <option value="{{ $child->id }}" {{ old('parent_id', $category->parent_id) == $child->id ? 'selected' : '' }}>　└ {{ $child->name }}</option>
                                    @endif
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                </div>
                @if(!$category->parent_id)
                <div class="col-md-4" id="category-image-col">
                    <div class="form-group">
                        <label>Ảnh danh mục</label>
                        <div class="border rounded p-2 bg-light text-center">
                            @if($category->image)
                                <img id="current-img" src="/images/categories/{{ basename($category->image) }}" alt="{{ $category->name }}" class="img-fluid rounded" style="max-height: 160px; object-fit: cover;">
                                <p class="text-muted small mt-1 mb-0">Ảnh hiện tại</p>
                            @else
                                <div id="current-img" class="py-4 text-muted">Chưa có ảnh</div>
                            @endif
                            <img id="preview-img" src="" alt="" class="img-fluid rounded mt-2" style="max-height: 120px; object-fit: cover; display: none;">
                        </div>
                        <input type="file" name="image" id="image" class="form-control-file mt-2" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" onchange="previewImage(this)">
                        <small class="form-text text-muted">Chỉ danh mục gốc. JPEG, PNG, GIF, WebP; tối đa 2MB</small>
                    </div>
                </div>
                @endif
            </div>

            @if($category->parent_id === null)
            <hr>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">Quản lý danh mục con</h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addChildRow({{ (int) $category->id }})">+ Thêm cấp 1</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addLeafRow()">+ Thêm cấp 2</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" id="childrenTable">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 80px;">Cấp</th>
                            <th>Tên</th>
                            <th style="width: 160px;">Thuộc</th>
                            <th style="width: 90px;" class="text-right">Xóa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($category->children as $child)
                            <tr data-level="1" data-id="{{ $child->id }}">
                                <td class="text-muted">1</td>
                                <td>
                                    <input class="form-control form-control-sm" name="children[{{ $child->id }}][name]" value="{{ $child->name }}">
                                </td>
                                <td class="text-muted">—</td>
                                <td class="text-right">
                                    <label class="mb-0 small text-muted">
                                        <input type="checkbox" name="delete_ids[]" value="{{ $child->id }}"> Xóa
                                    </label>
                                </td>
                            </tr>
                            @foreach($child->children ?? [] as $leaf)
                                <tr data-level="2" data-id="{{ $leaf->id }}">
                                    <td class="text-muted">2</td>
                                    <td>
                                        <input class="form-control form-control-sm" name="children[{{ $leaf->id }}][name]" value="{{ $leaf->name }}">
                                    </td>
                                    <td class="text-muted">{{ $child->name }}</td>
                                    <td class="text-right">
                                        <label class="mb-0 small text-muted">
                                            <input type="checkbox" name="delete_ids[]" value="{{ $leaf->id }}"> Xóa
                                        </label>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
            <small class="text-muted d-block mt-2">
                Ghi chú: hệ thống chỉ xóa được danh mục <strong>lá</strong> và <strong>không có sản phẩm</strong>. Nếu trùng tên trong cùng cấp, hệ thống sẽ bỏ qua dòng đó.
            </small>
            @endif

            <hr>
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('admin.categories.index') }}" class="btn btn-link text-muted">Hủy</a>
                <button type="submit" class="btn btn-primary px-4">Cập nhật</button>
            </div>
        </form>
    </div>
</div>

<script>
function previewImage(input) {
    var preview = document.getElementById('preview-img');
    var current = document.getElementById('current-img');
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (current) current.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function addChildRow(parentId) {
    var tbody = document.querySelector('#childrenTable tbody');
    if (!tbody) return;
    var idx = tbody.querySelectorAll('tr[data-new="1"]').length;
    var tr = document.createElement('tr');
    tr.setAttribute('data-level', '1');
    tr.setAttribute('data-new', '1');
    tr.innerHTML = `
        <td class="text-muted">1</td>
        <td><input class="form-control form-control-sm" name="new_children[${idx}][name]" value="" placeholder="Tên danh mục cấp 1"></td>
        <td class="text-muted">—</td>
        <td class="text-right">
            <input type="hidden" name="new_children[${idx}][parent_id]" value="${parentId}">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">X</button>
        </td>
    `;
    tbody.appendChild(tr);
}

function addLeafRow() {
    var tbody = document.querySelector('#childrenTable tbody');
    if (!tbody) return;
    var roots = [];
    document.querySelectorAll('#childrenTable tr[data-level="1"]').forEach(function(tr) {
        var id = tr.getAttribute('data-id');
        var input = tr.querySelector('input[name^="children["]');
        var name = input ? (input.value || '').trim() : '';
        if (id) {
            roots.push({ id: id, name: name || ('ID ' + id) });
        }
    });
    if (!roots.length) {
        alert('Bạn cần có ít nhất 1 danh mục cấp 1 để thêm cấp 2.');
        return;
    }
    var idx = tbody.querySelectorAll('tr[data-new="1"]').length;
    var options = roots.map(r => `<option value="${r.id}">${r.name}</option>`).join('');
    var tr = document.createElement('tr');
    tr.setAttribute('data-level', '2');
    tr.setAttribute('data-new', '1');
    tr.innerHTML = `
        <td class="text-muted">2</td>
        <td><input class="form-control form-control-sm" name="new_children[${idx}][name]" value="" placeholder="Tên danh mục cấp 2"></td>
        <td>
            <select class="form-control form-control-sm" name="new_children[${idx}][parent_id]">
                ${options}
            </select>
        </td>
        <td class="text-right">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">X</button>
        </td>
    `;
    tbody.appendChild(tr);
}
</script>
@endsection
