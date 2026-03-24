<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>NovaShop — SPA demo</title>
    @vite(['resources/js/spa/main.jsx'])
</head>
<body class="m-0 bg-zinc-100 text-zinc-900 antialiased">
    <div id="spa-root">
        <p class="p-6 text-center text-sm text-zinc-600">
            Đang tải SPA… Nếu màn hình trắng lâu: đang bật <code class="rounded bg-zinc-200 px-1">npm run dev</code> thì giữ cả hai
            (<code class="rounded bg-zinc-200 px-1">php artisan serve</code> + Vite); hoặc chạy <code class="rounded bg-zinc-200 px-1">npm run build</code> rồi xóa file <code class="rounded bg-zinc-200 px-1">public/hot</code> và F5.
        </p>
    </div>
</body>
</html>
