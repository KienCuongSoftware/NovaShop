@extends('layouts.admin')

@section('title', 'Người dùng')

@section('content')
<div class="page-header">
    <h2>Người dùng</h2>
    <div>
        <a class="btn btn-success" href="{{ route('admin.users.create') }}">Thêm người dùng</a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th class="text-center" style="width: 60px;">STT</th>
                        <th style="width: 180px;">Tên</th>
                        <th style="width: 220px;">Email</th>
                        <th class="text-center" style="width: 100px;">Quản trị</th>
                        <th class="text-center" style="width: 1%; white-space: nowrap;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                    <tr>
                        <td class="text-center align-middle">{{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}</td>
                        <td class="align-middle">{{ $user->name }}</td>
                        <td class="align-middle">{{ $user->email }}</td>
                        <td class="text-center align-middle">
                            @if($user->is_admin)
                                <span class="badge badge-danger badge-role">Có</span>
                            @else
                                <span class="badge badge-secondary badge-role">Không</span>
                            @endif
                        </td>
                        <td class="text-center align-middle text-nowrap">
                            <a class="btn btn-info btn-sm" href="{{ route('admin.users.show', $user->id) }}">Xem</a>
                            <a class="btn btn-primary btn-sm" href="{{ route('admin.users.edit', $user->id) }}">Sửa</a>
                            <form id="delete-form-{{ $user->id }}" action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-danger btn-sm btn-delete" data-form-id="delete-form-{{ $user->id }}" data-name="{{ $user->name }}">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Chưa có người dùng nào.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($users->hasPages())
    <div class="card-footer">
        @php
            $paginator = $users;
            $current = $paginator->currentPage();
            $last = $paginator->lastPage();
            $elements = [];
            if ($last <= 6) {
                for ($i = 1; $i <= $last; $i++) { $elements[] = $i; }
            } else {
                $start = max(1, min($current - 2, $last - 5));
                $elements = [$start, $start + 1, $start + 2, '...', $start + 3, $start + 4, $start + 5];
            }
        @endphp
        <nav>
            <ul class="pagination">
                <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                    @if($paginator->onFirstPage())
                        <span class="page-link">&lsaquo;</span>
                    @else
                        <a class="page-link" href="{{ $paginator->previousPageUrl() }}">&lsaquo;</a>
                    @endif
                </li>
                @foreach($elements as $el)
                    @if($el === '...')
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    @else
                        <li class="page-item {{ (int)$el === (int)$current ? 'active' : '' }}">
                            @if((int)$el === (int)$current)
                                <span class="page-link">{{ $el }}</span>
                            @else
                                <a class="page-link" href="{{ $paginator->url($el) }}">{{ $el }}</a>
                            @endif
                        </li>
                    @endif
                @endforeach
                <li class="page-item {{ !$paginator->hasMorePages() ? 'disabled' : '' }}">
                    @if(!$paginator->hasMorePages())
                        <span class="page-link">&rsaquo;</span>
                    @else
                        <a class="page-link" href="{{ $paginator->nextPageUrl() }}">&rsaquo;</a>
                    @endif
                </li>
            </ul>
        </nav>
    </div>
    @endif
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="deleteModalLabel">Xác nhận xóa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Bạn có chắc muốn xóa người dùng <strong id="deleteItemName"></strong>?</p>
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
    var deleteItemName = document.getElementById('deleteItemName');
    var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    var formToDelete = null;
    document.querySelectorAll('.btn-delete').forEach(function(btn) {
        btn.addEventListener('click', function() {
            formToDelete = document.getElementById(this.getAttribute('data-form-id'));
            deleteItemName.textContent = '"' + (this.getAttribute('data-name') || '') + '"';
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
