import { useEffect, useRef } from 'react';
import { useBranding } from '../../context/BrandingContext';
import { useGoogleMaps } from '../../hooks/useGoogleMaps';

function num(v) {
  const n = Number(v);
  return Number.isFinite(n) ? n : null;
}

// Live-ish tracking map: restaurant, delivery address and (when the driver is
// assigned) the driver marker, redrawn whenever the order payload updates.
// Uses the same admin Google Maps key as LocationPicker; degrades to a static
// note when no key is configured.
export default function TrackingMap({ order }) {
  const { branding } = useBranding();
  const { loaded, error } = useGoogleMaps(branding.google_maps_api_key);
  const divRef = useRef(null);
  const mapRef = useRef(null);
  const markersRef = useRef({});
  const lineRef = useRef(null);

  const restaurant = order?.restaurant || {};
  const rLat = num(restaurant.latitude ?? restaurant.lat);
  const rLng = num(restaurant.longitude ?? restaurant.lng);
  const dLat = num(order?.delivery_lat);
  const dLng = num(order?.delivery_lng);
  const drv = order?.driver_location || {};
  const drvLat = num(order?.driver_lat ?? drv.lat ?? drv.latitude);
  const drvLng = num(order?.driver_lng ?? drv.lng ?? drv.longitude);

  useEffect(() => {
    if (!loaded || !divRef.current || !window.google?.maps) return;
    const g = window.google.maps;

    if (!mapRef.current) {
      mapRef.current = new g.Map(divRef.current, {
        center: { lat: rLat ?? dLat ?? 20.5937, lng: rLng ?? dLng ?? 78.9629 },
        zoom: 14,
        disableDefaultUI: true,
        zoomControl: true,
        gestureHandling: 'greedy',
      });
    }
    const map = mapRef.current;

    const place = (key, lat, lng, label, color) => {
      if (lat == null || lng == null) {
        if (markersRef.current[key]) {
          markersRef.current[key].setMap(null);
          delete markersRef.current[key];
        }
        return;
      }
      if (markersRef.current[key]) {
        markersRef.current[key].setPosition({ lat, lng });
      } else {
        markersRef.current[key] = new g.Marker({
          position: { lat, lng },
          map,
          title: label,
          icon: {
            path: g.SymbolPath.CIRCLE,
            scale: 8,
            fillColor: color,
            fillOpacity: 1,
            strokeColor: '#fff',
            strokeWeight: 3,
          },
        });
      }
    };

    place('restaurant', rLat, rLng, restaurant.name || 'Restaurant', '#ff5a1f');
    place('delivery', dLat, dLng, 'Delivery address', '#1a7f37');
    place('driver', drvLat, drvLng, order?.driver?.name || 'Delivery partner', '#111827');

    const path = [
      drvLat != null ? { lat: drvLat, lng: drvLng } : rLat != null ? { lat: rLat, lng: rLng } : null,
      dLat != null ? { lat: dLat, lng: dLng } : null,
    ].filter(Boolean);
    if (path.length === 2) {
      if (lineRef.current) lineRef.current.setPath(path);
      else
        lineRef.current = new g.Polyline({
          path,
          map,
          strokeColor: '#ff5a1f',
          strokeOpacity: 0.9,
          strokeWeight: 4,
        });
    }

    const pts = Object.values(markersRef.current).map((m) => m.getPosition());
    if (pts.length === 1) {
      map.setCenter(pts[0]);
    } else if (pts.length > 1) {
      const bounds = new g.LatLngBounds();
      pts.forEach((p) => bounds.extend(p));
      map.fitBounds(bounds, 64);
    }
  }, [loaded, rLat, rLng, dLat, dLng, drvLat, drvLng, restaurant.name, order?.driver?.name]);

  if (!branding.google_maps_api_key || error) {
    return (
      <div className="flex h-full min-h-52 items-center justify-center rounded-2xl border border-dashed border-line-strong bg-surface-alt p-6 text-center text-sm text-ink-soft">
        {error ? 'Map could not load.' : 'Live map is not configured for this store.'}
      </div>
    );
  }

  return (
    <div
      ref={divRef}
      className="h-full min-h-52 w-full overflow-hidden rounded-2xl border border-line bg-surface-alt"
    />
  );
}
