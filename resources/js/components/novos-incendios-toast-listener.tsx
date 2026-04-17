import { useEffect, useRef } from 'react';
import { toast } from 'sonner';

import axios, { ensureSanctumCsrfCookie } from '@/lib/axios-setup';

type IncendioPollItem = {
    id: string;
    detectado_em: string | null;
    area?: { nome: string } | null;
};

const POLL_INTERVAL_MS = 25_000;
const TOAST_DURATION_MS = 7_000;
const STORAGE_SEEN_KEY = 'brasa:incendios_toast_seen_ids';
const STORAGE_PRIMED_KEY = 'brasa:incendios_toast_primed';
const MAX_SEEN_IDS = 250;

async function getIncendiosRecentes(): Promise<IncendioPollItem[]> {
    const res = await axios.get<{ data: IncendioPollItem[] }>(
        '/api/incendios',
        { params: { per_page: 100 } },
    );

    return res.data.data ?? [];
}

async function getIncendiosRecentesViaWeb(): Promise<IncendioPollItem[]> {
    const res = await axios.get<{ data: IncendioPollItem[] }>(
        '/incendios-poll',
        { params: { per_page: 100 } },
    );

    return res.data.data ?? [];
}

function loadPrimed(): boolean {
    try {
        return sessionStorage.getItem(STORAGE_PRIMED_KEY) === 'true';
    } catch {
        return false;
    }
}

function savePrimed(value: boolean): void {
    try {
        sessionStorage.setItem(STORAGE_PRIMED_KEY, value ? 'true' : 'false');
    } catch {
        //
    }
}

function loadSeenIds(): string[] {
    try {
        const raw = sessionStorage.getItem(STORAGE_SEEN_KEY);
        if (!raw) {
            return [];
        }
        const parsed = JSON.parse(raw) as unknown;
        if (!Array.isArray(parsed)) {
            return [];
        }
        return parsed.filter((x): x is string => typeof x === 'string');
    } catch {
        return [];
    }
}

function saveSeenIds(ids: string[]): void {
    try {
        sessionStorage.setItem(STORAGE_SEEN_KEY, JSON.stringify(ids));
    } catch {
        //
    }
}

export function NovosIncendiosToastListener() {
    const seenIdsRef = useRef<Set<string>>(new Set(loadSeenIds()));
    const primedRef = useRef(loadPrimed());

    useEffect(() => {
        const poll = async (): Promise<void> => {
            try {
                await ensureSanctumCsrfCookie();

                let items: IncendioPollItem[];
                try {
                    items = await getIncendiosRecentes();
                } catch (err) {
                    const axiosErr = err as {
                        response?: { status?: number };
                    };
                    if (axiosErr?.response?.status === 401) {
                        items = await getIncendiosRecentesViaWeb();
                    } else {
                        throw err;
                    }
                }

                if (!primedRef.current) {
                    for (const inc of items) {
                        seenIdsRef.current.add(inc.id);
                    }
                    primedRef.current = true;
                    savePrimed(true);
                    saveSeenIds(Array.from(seenIdsRef.current).slice(-MAX_SEEN_IDS));

                    return;
                }

                let novos = 0;
                for (const inc of items) {
                    if (seenIdsRef.current.has(inc.id)) {
                        continue;
                    }
                    seenIdsRef.current.add(inc.id);
                    novos += 1;

                    const areaNome =
                        inc.area?.nome?.trim() || 'Área não informada';
                    const hora = inc.detectado_em
                        ? new Date(inc.detectado_em).toLocaleString('pt-BR')
                        : '';
                    toast.success('Novo incêndio registrado', {
                        id: `incendio-novo-${inc.id}`,
                        description: `${areaNome}${hora ? ` · ${hora}` : ''}`,
                        duration: TOAST_DURATION_MS,
                    });
                }

                if (novos > 0) {
                    saveSeenIds(
                        Array.from(seenIdsRef.current).slice(-MAX_SEEN_IDS),
                    );
                }
            } catch {
                //
            }
        };

        void poll();
        const id = window.setInterval(() => {
            void poll();
        }, POLL_INTERVAL_MS);

        return () => {
            window.clearInterval(id);
        };
    }, []);

    return null;
}
