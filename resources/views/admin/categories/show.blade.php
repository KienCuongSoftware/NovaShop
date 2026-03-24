@extends('layouts.admin')

@section('title', 'Chi tiết danh mục')

@section('content')
<div class="page-header">
    <h2>Chi tiết danh mục</h2>
    <a class="btn btn-primary" href="{{ route('admin.categories.index', ['page' => session('admin.categories.page', 1)]) }}">Quay lại</a>
</div>

<div class="card">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Tên:</dt>
            <dd class="col-sm-9">{{ $category->name }}</dd>
            @if($category->parent)
            <dt class="col-sm-3">Danh mục cha:</dt>
            <dd class="col-sm-9"><a href="{{ route('admin.categories.show', $category->parent) }}">{{ $category->parent->name }}</a></dd>
            @endif
            @if($category->image)
            <dt class="col-sm-3">Ảnh:</dt>
            <dd class="col-sm-9">
                <img src="/images/categories/{{ basename($category->image) }}" alt="{{ $category->name }}" class="img-thumbnail" style="max-height: 120px;">
            </dd>
            @endif
        </dl>
        @if($category->children->isNotEmpty())
        <hr>
        <div>
            <h5 class="mb-3">Danh mục con</h5>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 60px;">Cấp</th>
                            <th>Tên</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($category->children as $child)
                            <tr>
                                <td class="text-muted">1</td>
                                <td class="font-weight-bold">{{ $child->name }}</td>
                            </tr>
                            @foreach($child->children ?? [] as $leaf)
                                <tr>
                                    <td class="text-muted">2</td>
                                    <td class="text-muted">— {{ $leaf->name }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
            <small class="text-muted d-block mt-2">Muốn sửa/thêm/xóa danh mục con, hãy bấm <strong>Sửa</strong> ở danh mục cha.</small>
        </div>
        @endif
        <hr>
        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="{{ route('admin.categories.edit', $category) }}">Sửa</a>
            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return bsConfirmSubmit(this, 'Bạn có chắc muốn xóa danh mục \'{{ addslashes($category->name) }}\'?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Xóa</button>
            </form>
        </div>
    </div>
</div>
@endsection
