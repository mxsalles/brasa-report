import { cn } from '@/lib/utils';
import type { NivelRisco } from '@/types/dashboard';

type RiskBadgeProps = {
    nivel: NivelRisco;
    className?: string;
};

const riskConfig: Record<NivelRisco, { label: string; className: string }> = {
    alto: {
        label: 'Alto Risco',
        className: 'border-critical/30 bg-critical/15 text-critical',
    },
    medio: {
        label: 'Médio Risco',
        className: 'border-warning/30 bg-warning/15 text-warning',
    },
    baixo: {
        label: 'Baixo Risco',
        className: 'border-resolved/30 bg-resolved/15 text-resolved',
    },
};

export function RiskBadge({ nivel, className }: RiskBadgeProps) {
    const config = riskConfig[nivel];

    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold',
                config.className,
                className,
            )}
        >
            {config.label}
        </span>
    );
}
