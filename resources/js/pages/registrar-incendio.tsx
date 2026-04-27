import { Head, router } from '@inertiajs/react';
import { isAxiosError } from 'axios';
import { motion } from 'framer-motion';
import {
    AlertTriangle,
    Camera,
    Flame,
    GraduationCap,
    Home,
    MapPin,
    Send,
} from 'lucide-react';
import { useCallback, useRef, useState } from 'react';
import { toast } from 'sonner';

import { MapComponent } from '@/components/map-component';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios-setup';
import { cn } from '@/lib/utils';
import { dashboard, registrarIncendio as registrarIncendioRoute } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import type { NivelRisco } from '@/types/dashboard';

function toDatetimeLocalValue(d: Date): string {
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function firstValidationMessage(
    errors: Record<string, string[] | string | undefined>,
): string | null {
    for (const value of Object.values(errors)) {
        if (Array.isArray(value) && value[0]) {
            return value[0];
        }
        if (typeof value === 'string' && value) {
            return value;
        }
    }

    return null;
}

const niveisRisco: {
    value: NivelRisco;
    label: string;
    desc: string;
    icon: string;
}[] = [
    {
        value: 'baixo',
        label: 'Baixo',
        desc: 'Vegetação rasteira, longe de construções',
        icon: '🟢',
    },
    {
        value: 'medio',
        label: 'Médio',
        desc: 'Área de mata, risco moderado de propagação',
        icon: '🟡',
    },
    {
        value: 'alto',
        label: 'Alto / GRAVE',
        desc: 'Próximo a comunidades, construções ou combustível',
        icon: '🔴',
    },
];

const tiposLocal = [
    { value: 'residencia' as const, label: 'Residências', icon: Home },
    { value: 'escola' as const, label: 'Escola', icon: GraduationCap },
    {
        value: 'infraestrutura' as const,
        label: 'Combustível/Inflamável',
        icon: Flame,
    },
];

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Registrar incêndio',
        href: registrarIncendioRoute().url,
    },
];

export default function RegistrarIncendio() {
    const [coordenadas, setCoordenadas] = useState<{
        lat: number;
        lng: number;
    } | null>(null);
    const [nivelRisco, setNivelRisco] = useState<NivelRisco>('medio');
    const [tipoLocal, setTipoLocal] = useState<string | null>(null);
    const [observacao, setObservacao] = useState('');
    const [fotoPreview, setFotoPreview] = useState<string | null>(null);
    const [detectadoEm, setDetectadoEm] = useState(() =>
        toDatetimeLocalValue(new Date()),
    );
    const [enviando, setEnviando] = useState(false);
    const fileRef = useRef<HTMLInputElement>(null);

    const handleMapClick = useCallback((lat: number, lng: number) => {
        setCoordenadas({ lat, lng });
    }, []);

    const handleFoto = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setFotoPreview(URL.createObjectURL(file));
        }
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (! coordenadas) {
            return;
        }

        setEnviando(true);
        try {
            const detectadoIso = new Date(detectadoEm).toISOString();

            await axios.post('/api/incendios', {
                latitude: coordenadas.lat,
                longitude: coordenadas.lng,
                nivel_risco: nivelRisco,
                detectado_em: detectadoIso,
            });

            toast.success('Incêndio registrado com sucesso');
            router.visit(dashboard().url);
        } catch (error) {
            if (isAxiosError(error)) {
                const status = error.response?.status;
                const payload = error.response?.data as
                    | { errors?: Record<string, string[]>; message?: string }
                    | undefined;

                if (status === 422 && payload?.errors) {
                    const msg =
                        firstValidationMessage(payload.errors) ??
                        'Dados inválidos. Verifique os campos.';
                    toast.error(msg);

                    return;
                }

                if (status === 401) {
                    toast.error(
                        'Não autenticado na API (sessão ou Sanctum). Atualize a página ou confira domínios stateful no .env.',
                    );

                    return;
                }

                if (status === 419) {
                    toast.error(
                        'Sessão de segurança expirou. Atualize a página e tente novamente.',
                    );

                    return;
                }

                if (status && status >= 500) {
                    toast.error(
                        'Erro ao registrar incêndio. Tente novamente.',
                    );

                    return;
                }
            }

            toast.error('Erro ao registrar incêndio. Tente novamente.');
        } finally {
            setEnviando(false);
        }
    };

    const formularioDesabilitado = enviando;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Registrar incêndio" />
            <div className="p-4 lg:p-6">
                <motion.div
                    initial={{ opacity: 0, y: -10 }}
                    animate={{ opacity: 1, y: 0 }}
                >
                    <h1 className="mb-1 text-2xl font-bold">
                        Registrar Incêndio
                    </h1>
                    <p className="mb-6 text-sm text-muted-foreground">
                        Clique no mapa para marcar a localização e preencha os
                        dados
                    </p>
                </motion.div>

                <form onSubmit={(e) => void handleSubmit(e)}>
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <motion.div
                            initial={{ opacity: 0, x: -20 }}
                            animate={{ opacity: 1, x: 0 }}
                            transition={{ delay: 0.1 }}
                            className="glass-panel overflow-hidden rounded-xl"
                        >
                            <div className="border-b border-border p-3">
                                <div className="flex items-center gap-2">
                                    <MapPin className="size-4 text-primary" />
                                    <span className="text-sm font-medium">
                                        Localização do Foco
                                    </span>
                                    {coordenadas ? (
                                        <span className="ml-auto font-mono text-xs text-muted-foreground">
                                            {coordenadas.lat.toFixed(5)},{' '}
                                            {coordenadas.lng.toFixed(5)}
                                        </span>
                                    ) : null}
                                </div>
                            </div>
                            <div className="h-72 lg:h-96">
                                <MapComponent
                                    incendios={[]}
                                    onMapClick={handleMapClick}
                                    selectedPoint={coordenadas}
                                />
                            </div>
                            {coordenadas ? (
                                <div className="border-t border-border p-3 space-y-2">
                                    <div className="grid grid-cols-2 gap-2">
                                        <div>
                                            <Label
                                                htmlFor="latitude"
                                                className="text-xs text-muted-foreground"
                                            >
                                                Latitude
                                            </Label>
                                            <Input
                                                id="latitude"
                                                readOnly
                                                value={coordenadas.lat.toFixed(
                                                    7,
                                                )}
                                                className="font-mono text-xs"
                                            />
                                        </div>
                                        <div>
                                            <Label
                                                htmlFor="longitude"
                                                className="text-xs text-muted-foreground"
                                            >
                                                Longitude
                                            </Label>
                                            <Input
                                                id="longitude"
                                                readOnly
                                                value={coordenadas.lng.toFixed(
                                                    7,
                                                )}
                                                className="font-mono text-xs"
                                            />
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="border-t border-primary/20 bg-primary/5 p-3">
                                    <p className="text-center text-xs text-primary">
                                        Toque no mapa para marcar o foco de
                                        incêndio
                                    </p>
                                </div>
                            )}
                        </motion.div>

                        <motion.div
                            initial={{ opacity: 0, x: 20 }}
                            animate={{ opacity: 1, x: 0 }}
                            transition={{ delay: 0.2 }}
                            className="space-y-5"
                        >
                            <div className="glass-panel rounded-xl p-5">
                                <Label
                                    htmlFor="detectado_em"
                                    className="mb-3 flex items-center gap-2 text-sm font-semibold"
                                >
                                    <MapPin className="size-4 text-primary" />
                                    Detectado em
                                </Label>
                                <Input
                                    id="detectado_em"
                                    type="datetime-local"
                                    value={detectadoEm}
                                    onChange={(e) =>
                                        setDetectadoEm(e.target.value)
                                    }
                                    disabled={formularioDesabilitado}
                                    className="font-mono text-sm"
                                />
                            </div>

                            <div className="glass-panel rounded-xl p-5">
                                <Label className="mb-3 flex items-center gap-2 text-sm font-semibold">
                                    <AlertTriangle className="size-4 text-primary" />
                                    Nível de Risco
                                </Label>
                                <div className="space-y-2">
                                    {niveisRisco.map((nivel) => (
                                        <button
                                            key={nivel.value}
                                            type="button"
                                            disabled={formularioDesabilitado}
                                            onClick={() =>
                                                setNivelRisco(nivel.value)
                                            }
                                            className={cn(
                                                'flex w-full items-center gap-3 rounded-lg border p-3 text-left transition-all',
                                                nivelRisco === nivel.value
                                                    ? 'border-primary bg-primary/10'
                                                    : 'border-border bg-secondary/30 hover:bg-secondary/50',
                                            )}
                                        >
                                            <span className="text-lg">
                                                {nivel.icon}
                                            </span>
                                            <div>
                                                <span className="text-sm font-medium">
                                                    {nivel.label}
                                                </span>
                                                <p className="text-xs text-muted-foreground">
                                                    {nivel.desc}
                                                </p>
                                            </div>
                                        </button>
                                    ))}
                                </div>
                            </div>

                            {nivelRisco === 'alto' ? (
                                <motion.div
                                    initial={{ opacity: 0, height: 0 }}
                                    animate={{ opacity: 1, height: 'auto' }}
                                    className="glass-panel rounded-xl p-5"
                                >
                                    <Label className="mb-3 block text-sm font-semibold">
                                        Proximidade de local crítico
                                    </Label>
                                    <div className="grid grid-cols-3 gap-2">
                                        {tiposLocal.map((tipo) => (
                                            <button
                                                key={tipo.value}
                                                type="button"
                                                disabled={formularioDesabilitado}
                                                onClick={() =>
                                                    setTipoLocal(
                                                        tipoLocal ===
                                                            tipo.value
                                                            ? null
                                                            : tipo.value,
                                                    )
                                                }
                                                className={cn(
                                                    'flex flex-col items-center gap-2 rounded-lg border p-3 text-center transition-all',
                                                    tipoLocal === tipo.value
                                                        ? 'border-critical bg-critical/10'
                                                        : 'border-border bg-secondary/30 hover:bg-secondary/50',
                                                )}
                                            >
                                                <tipo.icon className="size-5" />
                                                <span className="text-xs font-medium">
                                                    {tipo.label}
                                                </span>
                                            </button>
                                        ))}
                                    </div>
                                </motion.div>
                            ) : null}

                            <div className="glass-panel rounded-xl p-5">
                                <Label
                                    htmlFor="observacao"
                                    className="mb-2 block text-sm font-semibold"
                                >
                                    Observação{' '}
                                    <span className="font-normal text-muted-foreground">
                                        (opcional)
                                    </span>
                                </Label>
                                <Textarea
                                    id="observacao"
                                    placeholder="Descreva o incêndio, vegetação, vento, acessos..."
                                    value={observacao}
                                    onChange={(e) =>
                                        setObservacao(e.target.value)
                                    }
                                    rows={3}
                                    className="resize-none"
                                    disabled={formularioDesabilitado}
                                />
                            </div>

                            <div className="glass-panel rounded-xl p-5">
                                <Label className="mb-3 block text-sm font-semibold">
                                    Foto do Incêndio
                                </Label>
                                <input
                                    ref={fileRef}
                                    type="file"
                                    accept="image/*"
                                    capture="environment"
                                    onChange={handleFoto}
                                    className="hidden"
                                    disabled={formularioDesabilitado}
                                />
                                {fotoPreview ? (
                                    <div className="relative overflow-hidden rounded-lg">
                                        <img
                                            src={fotoPreview}
                                            alt="Pré-visualização"
                                            className="h-40 w-full rounded-lg object-cover"
                                        />
                                        <button
                                            type="button"
                                            disabled={formularioDesabilitado}
                                            onClick={() =>
                                                setFotoPreview(null)
                                            }
                                            className="absolute top-2 right-2 flex size-8 items-center justify-center rounded-full bg-background/80 text-foreground backdrop-blur-sm transition-colors hover:bg-background"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                ) : (
                                    <button
                                        type="button"
                                        disabled={formularioDesabilitado}
                                        onClick={() =>
                                            fileRef.current?.click()
                                        }
                                        className="flex h-32 w-full flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-border transition-colors hover:border-primary/50"
                                    >
                                        <Camera className="size-8 text-muted-foreground" />
                                        <span className="text-sm text-muted-foreground">
                                            Tirar foto ou selecionar
                                        </span>
                                    </button>
                                )}
                            </div>

                            <Button
                                type="submit"
                                size="lg"
                                className="w-full gap-2"
                                disabled={
                                    ! coordenadas ||
                                    formularioDesabilitado
                                }
                            >
                                <Send className="size-4" />
                                {enviando ? 'Enviando...' : 'Registrar Ocorrência'}
                            </Button>
                        </motion.div>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
