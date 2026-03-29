@extends('layouts.admin')

@section('title', 'Search synonyms')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center flex-wrap">
    <div>
        <h2 class="mb-0">Search synonyms</h2>
        <div class="text-muted small mt-1">Dùng để mở rộng keyword khi tìm kiếm (ES + fallback DB).</div>
    </div>
    <a class="btn btn-success" href="{{ route('admin.search-synonyms.create') }}">+ Thêm synonym</a>
</div>

@if(session('success'))
    <div class="alert alert-success py-2 mt-3">{{ session('success') }}</div>
@endif

<div class="card mt-3">
    <div class="card-body">
        <form method="GET" class="form-inline" style="gap:10px;">
            <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control" placeholder="Tìm keyword hoặc synonym..." style="min-width:260px;">
            <button class="btn btn-outline-primary" type="submit">Tìm</button>
            <a class="btn btn-light" href="{{ route('admin.search-synonyms.index') }}">Reset</a>
        </form>
    </div>
</div>

<div class="card mt-3">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Keyword</th>
                    <th>Synonym</th>
                    <th class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td class="font-weight-bold">{{ $r->keyword }}</td>
                        <td>{{ $r->synonym }}</td>
                        <td class="text-right">
                            <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.search-synonyms.edit', $r) }}">Sửa</a>
                            <button type="button" class="btn btn-outline-danger btn-sm btn-delete" data-form-id="del-s-{{ $r->id }}" data-name="{{ $r->keyword }} → {{ $r->synonym }}">Xóa</button>
                            <form id="del-s-{{ $r->id }}" action="{{ route('admin.search-synonyms.destroy', $r) }}" method="POST" class="d-none">
                                @csrf
                                @method('DELETE')
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-4">Chưa có synonym.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($rows->hasPages())
    <div class="mt-3 d-flex justify-content-center">{{ $rows->links() }}</div>
@endif

<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="deleteModalLabel">Xác nhận xóa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Bạn có chắc muốn xóa <strong id="deleteName"></strong>?</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Xóa</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var deleteModal = document.getElementById('deleteModal');
    var deleteName = document.getElementById('deleteName');
    var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    var formToDelete = null;
    document.querySelectorAll('.btn-delete').forEach(function(btn) {
        btn.addEventListener('click', function() {
            formToDelete = document.getElementById(this.getAttribute('data-form-id'));
            deleteName.textContent = '"' + (this.getAttribute('data-name') || '') + '"';
            $(deleteModal).modal('show');
        });
    });
    confirmDeleteBtn.addEventListener('click', function() {
        if (formToDelete) formToDelete.submit();
        $(deleteModal).modal('hide');
    });
});
</script>
@endsection

