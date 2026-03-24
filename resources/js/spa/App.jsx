import { useCallback, useEffect, useMemo, useState } from 'react';

const TOKEN_KEY = 'novashop_spa_token';

function getHeaders(json = true) {
    const h = {};
    if (json) {
        h['Content-Type'] = 'application/json';
        h.Accept = 'application/json';
    }
    const t = localStorage.getItem(TOKEN_KEY);
    if (t) {
        h.Authorization = `Bearer ${t}`;
    }
    return h;
}

async function api(path, options = {}) {
    const res = await fetch(`/api/v1${path}`, {
        ...options,
        headers: { ...getHeaders(!(options.body instanceof FormData)), ...options.headers },
    });
    const text = await res.text();
    let data = null;
    try {
        data = text ? JSON.parse(text) : null;
    } catch {
        data = { message: text };
    }
    if (!res.ok) {
        const err = new Error(data?.message || res.statusText);
        err.status = res.status;
        err.data = data;
        throw err;
    }
    return data;
}

/** Laravel JSON: ưu tiên message trong `errors` (422), sau đó `message` (403, …). */
function firstApiError(data, fallback = '') {
    if (!data) {
        return fallback;
    }
    if (data.errors && typeof data.errors === 'object') {
        for (const key of Object.keys(data.errors)) {
            const arr = data.errors[key];
            if (Array.isArray(arr) && arr[0]) {
                return arr[0];
            }
        }
    }
    if (data.message) {
        return data.message;
    }
    return fallback;
}

export default function App() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [token, setToken] = useState(() => localStorage.getItem(TOKEN_KEY));
    const [user, setUser] = useState(null);
    const [products, setProducts] = useState([]);
    const [meta, setMeta] = useState(null);
    const [cart, setCart] = useState(null);
    const [msg, setMsg] = useState('');
    const [err, setErr] = useState('');
    /** @type {[Record<number, number>, function]} productId → product_variant_id */
    const [variantPick, setVariantPick] = useState({});

    const loadProducts = useCallback(async () => {
        setErr('');
        try {
            const data = await api('/products?per_page=8');
            setProducts(data.data || []);
            setMeta(data.meta || null);
        } catch (e) {
            setErr(e.message);
        }
    }, []);

    const loadCart = useCallback(async () => {
        if (!token) {
            setCart(null);
            return;
        }
        try {
            const data = await api('/cart');
            setCart(data);
        } catch {
            setCart(null);
        }
    }, [token]);

    useEffect(() => {
        loadProducts();
    }, [loadProducts]);

    /** Mặc định chọn biến thể còn hàng đầu tiên khi đổi danh sách SP */
    const productsKey = useMemo(() => products.map((p) => p.id).join(','), [products]);
    useEffect(() => {
        setVariantPick((prev) => {
            const next = { ...prev };
            for (const p of products) {
                if (!p.has_variants || !p.variants?.length) {
                    continue;
                }
                const inStock = p.variants.filter((v) => v.stock > 0);
                if (!inStock.length) {
                    delete next[p.id];
                    continue;
                }
                const current = next[p.id];
                const stillValid = current && inStock.some((v) => v.id === current);
                if (!stillValid) {
                    next[p.id] = inStock[0].id;
                }
            }
            return next;
        });
    }, [productsKey, products]);

    useEffect(() => {
        if (!token) {
            setUser(null);
            setCart(null);
            return;
        }
        (async () => {
            try {
                const u = await api('/user');
                setUser(u);
                loadCart();
            } catch {
                setUser(null);
                localStorage.removeItem(TOKEN_KEY);
                setToken(null);
            }
        })();
    }, [token, loadCart]);

    async function login(e) {
        e.preventDefault();
        setMsg('');
        setErr('');
        try {
            const data = await api('/auth/login', {
                method: 'POST',
                body: JSON.stringify({ email, password, device_name: 'spa-demo' }),
            });
            localStorage.setItem(TOKEN_KEY, data.token);
            setToken(data.token);
            setUser(data.user);
            setMsg('Đã đăng nhập.');
        } catch (e) {
            setErr(firstApiError(e.data, e.message));
        }
    }

    function logout() {
        if (token) {
            api('/auth/logout', { method: 'POST' }).catch(() => {});
        }
        localStorage.removeItem(TOKEN_KEY);
        setToken(null);
        setUser(null);
        setCart(null);
        setMsg('Đã đăng xuất.');
    }

    async function addToCart(p) {
        setMsg('');
        setErr('');
        if (!token) {
            setErr('Đăng nhập để thêm giỏ.');
            return;
        }
        let productVariantId = null;
        if (p.has_variants && p.variants?.length) {
            const vid = variantPick[p.id];
            const variant = p.variants.find((v) => v.id === vid);
            if (!variant || variant.stock < 1) {
                setErr('Chọn biến thể còn hàng.');
                return;
            }
            productVariantId = variant.id;
        }
        try {
            const body = { product_id: p.id, quantity: 1 };
            if (productVariantId) {
                body.product_variant_id = productVariantId;
            }
            const data = await api('/cart/items', {
                method: 'POST',
                body: JSON.stringify(body),
            });
            setCart(data);
            setMsg('Đã thêm vào giỏ.');
        } catch (e) {
            setErr(firstApiError(e.data, e.message));
        }
    }

    return (
        <div className="min-h-screen">
            <header className="border-b border-zinc-200 bg-white shadow-sm">
                <div className="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4 px-4 py-4">
                    <div>
                        <h1 className="text-xl font-bold text-red-600">NovaShop SPA</h1>
                        <p className="text-sm text-zinc-500">Demo React + API v1 (Vite proxy /api → Laravel)</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        {user ? (
                            <>
                                <span className="text-sm">{user.name}</span>
                                {cart && (
                                    <span className="rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-700">
                                        Giỏ: {cart.items?.length || 0} dòng — {cart.total?.toLocaleString('vi-VN')} đ
                                    </span>
                                )}
                                <button
                                    type="button"
                                    className="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm"
                                    onClick={logout}
                                >
                                    Đăng xuất
                                </button>
                            </>
                        ) : null}
                    </div>
                </div>
            </header>

            <main className="mx-auto max-w-5xl px-4 py-8">
                {!user && (
                    <form
                        onSubmit={login}
                        className="mb-10 max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-sm"
                    >
                        <h2 className="mb-4 font-semibold">Đăng nhập (token Sanctum)</h2>
                        <p className="mb-4 text-xs text-zinc-500">
                            Nếu bạn chỉ đăng nhập bằng Google trên site chính: vào <strong>Hồ sơ</strong> → đặt mật khẩu
                            trước, rồi đăng nhập SPA bằng email + mật khẩu đó.
                        </p>
                        <label htmlFor="spa-login-email" className="mb-2 block text-sm">
                            Email
                        </label>
                        <input
                            id="spa-login-email"
                            name="email"
                            autoComplete="email"
                            className="mb-3 w-full rounded border border-zinc-300 px-3 py-2"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            type="email"
                            required
                        />
                        <label htmlFor="spa-login-password" className="mb-2 block text-sm">
                            Mật khẩu
                        </label>
                        <input
                            id="spa-login-password"
                            name="password"
                            autoComplete="current-password"
                            className="mb-4 w-full rounded border border-zinc-300 px-3 py-2"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            type="password"
                            required
                        />
                        <button type="submit" className="rounded-lg bg-red-600 px-4 py-2 text-white">
                            Đăng nhập
                        </button>
                    </form>
                )}

                {msg && <p className="mb-4 rounded-lg bg-emerald-50 px-4 py-2 text-emerald-800">{msg}</p>}
                {err && <p className="mb-4 rounded-lg bg-red-50 px-4 py-2 text-red-800">{err}</p>}

                <h2 className="mb-4 text-lg font-semibold">Sản phẩm (API)</h2>
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {products.map((p) => {
                        const variantOptions = p.has_variants && p.variants?.length ? p.variants : [];
                        const inStockVariants = variantOptions.filter((v) => v.stock > 0);
                        const selectedVid = variantPick[p.id];
                        const selectedVariant = variantOptions.find((v) => v.id === selectedVid);
                        const showPrice =
                            selectedVariant != null
                                ? Math.round(selectedVariant.price)
                                : Math.round(p.effective_price);

                        return (
                            <article key={p.id} className="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
                                <h3 className="line-clamp-2 font-medium">{p.name}</h3>
                                {variantOptions.length > 0 ? (
                                    <div className="mt-2">
                                        <label
                                            htmlFor={`variant-${p.id}`}
                                            className="mb-1 block text-xs font-medium text-zinc-600"
                                        >
                                            Biến thể
                                        </label>
                                        <select
                                            id={`variant-${p.id}`}
                                            name={`variant-${p.id}`}
                                            className="w-full rounded border border-zinc-300 px-2 py-1.5 text-sm"
                                            value={selectedVid ?? ''}
                                            onChange={(e) => {
                                                const raw = e.target.value;
                                                setVariantPick((prev) => ({
                                                    ...prev,
                                                    [p.id]: raw === '' ? undefined : Number(raw),
                                                }));
                                            }}
                                            disabled={!inStockVariants.length}
                                        >
                                            {!inStockVariants.length ? (
                                                <option value="">Hết hàng</option>
                                            ) : null}
                                            {inStockVariants.map((v) => (
                                                <option key={v.id} value={v.id}>
                                                    {v.display_name} — {Math.round(v.price).toLocaleString('vi-VN')} đ
                                                    (còn {v.stock})
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                ) : null}
                                <p className="mt-2 text-red-600 font-semibold">
                                    {showPrice.toLocaleString('vi-VN')} đ
                                </p>
                                <button
                                    type="button"
                                    className="mt-3 w-full rounded-lg bg-zinc-900 py-2 text-sm text-white disabled:opacity-50"
                                    onClick={() => addToCart(p)}
                                    disabled={!p.is_active || (variantOptions.length > 0 && !inStockVariants.length)}
                                >
                                    Thêm giỏ
                                </button>
                            </article>
                        );
                    })}
                </div>
                {meta && (
                    <p className="mt-6 text-center text-sm text-zinc-500">
                        Trang {meta.current_page} / {meta.last_page}
                    </p>
                )}
            </main>
        </div>
    );
}
