@extends('layouts.admin')

@section('title', 'Người dùng')

@push('styles')
<style>
    .table-users-manage {
        table-layout: fixed;
        width: 100%;
    }
    .table-users-manage .cell-clip {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: middle;
    }
    /* Ô badge: không cho tràn sang cột Thao tác (table-layout: fixed) */
    .table-users-manage td:nth-child(4),
    .table-users-manage td:nth-child(5) {
        overflow: hidden;
    }
    .table-users-manage .table-users-badge.badge-role {
        min-width: 0;
        max-width: 100%;
        display: inline-block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: middle;
        box-sizing: border-box;
    }
    /* Khối cố định rộng ~3 nút, căn giữa trong ô; nút luôn bám trái → hàng 2 nút thẳng hàng với hàng 3 nút */
    .admin-users-actions-wrap {
        display: flex;
        justify-content: center;
    }
    .admin-users-actions {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        gap: 0.35rem;
        justify-content: flex-start;
        width: 18.5rem;
        max-width: 100%;
        box-sizing: border-box;
    }
    .admin-users-actions .btn {
        border-radius: 0.35rem;
        flex-shrink: 0;
    }
    .admin-users-actions form {
        display: block;
        margin: 0;
        flex-shrink: 0;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <h2>Người dùng</h2>
    <div class="admin-toolbar">
        <form method="GET" action="{{ route('admin.users.index') }}" class="admin-search-form mb-0">
            <div class="input-group" style="max-width: 320px;">
                <input type="text" name="q" class="form-control" placeholder="Tìm theo tên hoặc email..." value="{{ $q ?? '' }}">
                <div class="input-group-append">
                    <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                </div>
            </div>
        </form>
        <a class="btn btn-success" href="{{ route('admin.users.create') }}">Thêm người dùng</a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0 table-users-manage">
                <colgroup>
                    <col style="width: 3rem;">
                    <col style="width: 9.5rem;">
                    <col>
                    <col style="width: 5.75rem;">
                    <col style="width: 9rem;">
                    <col style="width: 19rem;">
                </colgroup>
                <thead class="thead-light">
                    <tr>
                        <th class="text-center">STT</th>
                        <th class="cell-clip">Tên</th>
                        <th class="cell-clip">Email</th>
                        <th class="text-center">Quản trị</th>
                        <th class="text-center">Tài khoản</th>
                        <th class="text-center text-nowrap">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                    <tr>
                        <td class="text-center align-middle">{{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}</td>
                        <td class="align-middle cell-clip" title="{{ $user->name }}">{{ $user->name }}</td>
                        <td class="align-middle cell-clip" title="{{ $user->email }}">{{ $user->email }}</td>
                        <td class="text-center align-middle">
                            @if($user->is_admin)
                                <span class="badge badge-danger badge-role table-users-badge">Có</span>
                            @else
                                <span class="badge badge-secondary badge-role table-users-badge">Không</span>
                            @endif
                        </td>
                        <td class="text-center align-middle">
                            @if($user->is_blocked ?? false)
                                <span class="badge badge-dark badge-role table-users-badge">Đã chặn</span>
                            @else
                                <span class="badge badge-success badge-role table-users-badge">Hoạt động</span>
                            @endif
                        </td>
                        <td class="text-center align-middle text-nowrap">
                            <div class="admin-users-actions-wrap">
                            <div class="admin-users-actions">
                                <a class="btn btn-info btn-sm" href="{{ route('admin.users.show', $user->id) }}">Xem</a>
                                <a class="btn btn-primary btn-sm" href="{{ route('admin.users.edit', $user->id) }}">Sửa</a>
                                @if($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.toggle-block', $user) }}" class="d-inline" onsubmit="return bsConfirmSubmit(this, @json(($user->is_blocked ?? false)
                                            ? ('Bỏ chặn tài khoản '.$user->email.'?'.chr(10).chr(10).'Người dùng có thể đăng nhập lại.')
                                            : ('Xác nhận CHẶN tài khoản '.$user->email.'?'.chr(10).chr(10).'Sau khi chặn, người dùng không thể đăng nhập.'.chr(10).'Hệ thống sẽ gửi email thông báo tới địa chỉ này (cần cấu hình SMTP trong .env nếu muốn gửi thật tới Gmail).')));">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ ($user->is_blocked ?? false) ? 'btn-success' : 'btn-danger' }}">
                                            {{ ($user->is_blocked ?? false) ? 'Bỏ chặn' : 'Chặn' }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Chưa có người dùng nào.</td>
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
                $start = max(1, $current - 2);
                $end = min($last, $start + 5);
                if ($end - $start < 5) {
                    $start = max(1, $end - 5);
                }
                $elements = [];
                if ($start > 1) {
                    $elements = [1, '...'];
                }
                for ($i = $start; $i <= $end; $i++) {
                    $elements[] = $i;
                }
                if ($end < $last) {
                    $elements[] = '...';
                    $elements[] = $last;
                }
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
@endsection
