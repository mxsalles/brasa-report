import L from 'leaflet';
import { useEffect, useRef } from 'react';

import 'leaflet/dist/leaflet.css';

import type { IncendioMapa } from '@/types/operacoes';

type MapComponentProps = {
    incendios: IncendioMapa[];
    center?: [number, number];
    zoom?: number;
    className?: string;
    onMapClick?: (lat: number, lng: number) => void;
    selectedPoint?: { lat: number; lng: number } | null;
};

function getMarkerColor(status: IncendioMapa['status']): string {
    switch (status) {
        case 'ativo':
            return '#ef4444';
        case 'contido':
            return '#eab308';
        case 'resolvido':
            return '#22c55e';
    }
}

function createFireIcon(color: string): L.DivIcon {
    return L.divIcon({
        html: `<div style="
      width: 24px; height: 24px;
      background: ${color};
      border-radius: 50%;
      border: 3px solid rgba(255,255,255,0.8);
      box-shadow: 0 0 12px ${color}88;
      display: flex; align-items: center; justify-content: center;
    "><svg width="12" height="12" viewBox="0 0 24 24" fill="white"><path d="M12 23c-3.6 0-8-3.1-8-8.5C4 9.1 12 1 12 1s8 8.1 8 13.5c0 5.4-4.4 8.5-8 8.5z"/></svg></div>`,
        className: '',
        iconSize: [24, 24],
        iconAnchor: [12, 12],
    });
}

const selectedIcon = L.divIcon({
    html: `<div style="
    width: 28px; height: 28px;
    background: hsl(36, 95%, 50%);
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 0 16px hsl(36, 95%, 50%, 0.6);
    animation: pulse 1.5s infinite;
  "></div>`,
    className: '',
    iconSize: [28, 28],
    iconAnchor: [14, 14],
});

export function MapComponent({
    incendios,
    center = [-18.05, -57.45],
    zoom = 11,
    className = '',
    onMapClick,
    selectedPoint,
}: MapComponentProps) {
    const mapRef = useRef<HTMLDivElement>(null);
    const mapInstanceRef = useRef<L.Map | null>(null);
    const selectedMarkerRef = useRef<L.Marker | null>(null);
    const onMapClickRef = useRef(onMapClick);
    onMapClickRef.current = onMapClick;

    useEffect(() => {
        if (!mapRef.current || mapInstanceRef.current) {
            return;
        }

        const map = L.map(mapRef.current, {
            center,
            zoom,
            zoomControl: false,
        });

        L.control.zoom({ position: 'topright' }).addTo(map);

        const satellite = L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            {
                attribution: 'Esri, Maxar, Earthstar Geographics',
                maxZoom: 18,
            },
        );

        const topo = L.tileLayer(
            'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
            { attribution: 'OpenTopoMap', maxZoom: 17 },
        );

        const osm = L.tileLayer(
            'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            { attribution: '© OpenStreetMap', maxZoom: 19 },
        );

        satellite.addTo(map);

        L.control.layers(
            { Satélite: satellite, Topografia: topo, 'Mapa Base': osm },
            {},
            { position: 'topright' },
        ).addTo(map);

        incendios.forEach((inc) => {
            const marker = L.marker([inc.latitude, inc.longitude], {
                icon: createFireIcon(getMarkerColor(inc.status)),
            }).addTo(map);

            marker.bindPopup(`
        <div style="font-family: system-ui, sans-serif; min-width: 200px;">
          <strong style="font-size: 14px;">${inc.area_nome}</strong>
          <p style="margin: 4px 0; font-size: 12px; color: #666;">${inc.descricao}</p>
          <div style="display: flex; gap: 8px; margin-top: 8px;">
            <span style="background: ${getMarkerColor(inc.status)}22; color: ${getMarkerColor(inc.status)}; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">
              ${inc.status.toUpperCase()}
            </span>
            <span style="font-size: 11px; color: #888;">
              ${inc.nivel_risco.toUpperCase()}
            </span>
          </div>
        </div>
      `);
        });

        const handler = (e: L.LeafletMouseEvent) => {
            onMapClickRef.current?.(e.latlng.lat, e.latlng.lng);
        };
        map.on('click', handler);

        mapInstanceRef.current = map;

        return () => {
            map.off('click', handler);
            map.remove();
            mapInstanceRef.current = null;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps -- map init once per mount
    }, []);

    useEffect(() => {
        const map = mapInstanceRef.current;
        if (!map) {
            return;
        }

        if (selectedMarkerRef.current) {
            map.removeLayer(selectedMarkerRef.current);
            selectedMarkerRef.current = null;
        }

        if (selectedPoint) {
            const marker = L.marker([selectedPoint.lat, selectedPoint.lng], {
                icon: selectedIcon,
            }).addTo(map);
            marker.bindPopup('Localização selecionada').openPopup();
            selectedMarkerRef.current = marker;
        }
    }, [selectedPoint]);

    return <div ref={mapRef} className={`w-full h-full ${className}`} />;
}
