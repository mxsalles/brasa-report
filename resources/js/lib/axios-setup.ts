import axios from 'axios';

/**
 * Obtém o cookie CSRF do Sanctum para pedidos XHR com sessão (SPA).
 */
export async function ensureSanctumCsrfCookie(): Promise<void> {
    await axios.get('/sanctum/csrf-cookie');
}

function readXsrfTokenFromCookie(): string | null {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    if (! match) {
        return null;
    }

    return decodeURIComponent(match[1]);
}

axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

axios.interceptors.request.use((config) => {
    const token = readXsrfTokenFromCookie();
    if (token) {
        config.headers.set('X-XSRF-TOKEN', token);
    }

    const url = typeof config.url === 'string' ? config.url : config.url?.toString() ?? '';
    const path = url.startsWith('http') ? new URL(url).pathname : url;
    if (path.startsWith('/api')) {
        const hasReferer = config.headers.get('Referer');
        const hasOrigin = config.headers.get('Origin');
        if (! hasReferer && ! hasOrigin && typeof window !== 'undefined') {
            config.headers.set('Referer', window.location.href);
        }
    }

    return config;
});

export default axios;
