import { useEffect, useState } from 'react';

let loadPromise = null;

function loadScript(apiKey) {
  if (window.google?.maps?.places) return Promise.resolve();
  if (loadPromise) return loadPromise;

  loadPromise = new Promise((resolve, reject) => {
    const script = document.createElement('script');
    script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=places&loading=async`;
    script.async = true;
    script.defer = true;
    script.onload = () => resolve();
    script.onerror = () => {
      loadPromise = null;
      reject(new Error('Failed to load Google Maps.'));
    };
    document.head.appendChild(script);
  });

  return loadPromise;
}

// Loads the same Maps JS API + Places library the admin's own checkout/store
// pages embed (see admin/resources/views/checkout.blade.php), keyed off the
// admin-configured `google_maps_api_key` from GET /api/app/branding.
export function useGoogleMaps(apiKey) {
  const [loaded, setLoaded] = useState(!!window.google?.maps?.places);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (!apiKey) return undefined;
    if (window.google?.maps?.places) {
      setLoaded(true);
      return undefined;
    }
    let cancelled = false;
    loadScript(apiKey)
      .then(() => !cancelled && setLoaded(true))
      .catch((err) => !cancelled && setError(err.message));
    return () => {
      cancelled = true;
    };
  }, [apiKey]);

  return { loaded, error };
}
