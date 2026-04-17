import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import '../css/app.css';
import { ensureSanctumCsrfCookie } from './lib/axios-setup';

const appName = import.meta.env.VITE_APP_NAME || 'Brasa';

function ensureLightDocument(): void {
    document.documentElement.classList.remove('dark');
    document.documentElement.style.colorScheme = 'light';
}

ensureLightDocument();

void (async () => {
    await ensureSanctumCsrfCookie();

    createInertiaApp({
        title: (title) => (title ? `${title} - ${appName}` : appName),
        resolve: (name) =>
            resolvePageComponent(
                `./pages/${name}.tsx`,
                import.meta.glob('./pages/**/*.tsx'),
            ),
        setup({ el, App, props }) {
            const root = createRoot(el);

            root.render(
                <StrictMode>
                    <App {...props} />
                </StrictMode>,
            );
        },
        progress: {
            color: '#4B5563',
        },
    });
})();
