import { useEffect, useRef, useState } from 'react';
import { api } from '../api/client';
import { useUserLocation } from '../context/LocationContext';
import { useAuth } from '../context/AuthContext';
import { useBranding } from '../context/BrandingContext';
import { useGoogleMaps } from '../hooks/useGoogleMaps';

export default function LocationPicker() {
  const { location, hasLocation, setLocation, requestMyLocation, geoError, isLocating, isPickerOpen, closePicker } =
    useUserLocation();
  const { isAuthed } = useAuth();
  const { branding } = useBranding();
  const { loaded: mapsLoaded, error: mapsError } = useGoogleMaps(branding.google_maps_api_key);

  const searchInputRef = useRef(null);
  const autocompleteRef = useRef(null);

  const [addresses, setAddresses] = useState([]);
  const [addressesLoading, setAddressesLoading] = useState(false);
  const [addressesError, setAddressesError] = useState(null);

  const [serviceAreas, setServiceAreas] = useState([]);

  const [showManual, setShowManual] = useState(false);
  const [label, setLabel] = useState('');
  const [addressText, setAddressText] = useState('');
  const [lat, setLat] = useState('');
  const [lng, setLng] = useState('');
  const [saveToAccount, setSaveToAccount] = useState(false);
  const [manualError, setManualError] = useState(null);
  const [saving, setSaving] = useState(false);

  // Real, admin-configured delivery zones (Admin > Delivery Areas) -- public
  // endpoint, works regardless of login. Used as quick-picks so there's a
  // sensible starting point without hardcoding any city into the frontend.
  useEffect(() => {
    if (!isPickerOpen) return;
    let cancelled = false;
    api
      .get('/delivery-areas/active')
      .then((res) => !cancelled && setServiceAreas(res?.data ?? []))
      .catch(() => !cancelled && setServiceAreas([]));
    return () => {
      cancelled = true;
    };
  }, [isPickerOpen]);

  useEffect(() => {
    if (!isPickerOpen || !isAuthed) return;
    let cancelled = false;
    setAddressesLoading(true);
    setAddressesError(null);
    api
      .get('/addresses', { auth: true })
      .then((res) => !cancelled && setAddresses(res?.data ?? []))
      .catch((err) => !cancelled && setAddressesError(err.message))
      .finally(() => !cancelled && setAddressesLoading(false));
    return () => {
      cancelled = true;
    };
  }, [isPickerOpen, isAuthed]);

  // requestMyLocation() resolves asynchronously via a geolocation callback, so
  // there's no synchronous point to close the picker from the click handler
  // the way pick() does below -- wait for `location` to actually change instead.
  const awaitingGeoRef = useRef(false);
  useEffect(() => {
    if (awaitingGeoRef.current && hasLocation) {
      awaitingGeoRef.current = false;
      closePicker();
    }
  }, [hasLocation, location, closePicker]);

  // Google Places Autocomplete -- same Maps JS API + Places library the
  // admin's own checkout page embeds (admin/resources/views/checkout.blade.php),
  // keyed off the admin-configured google_maps_api_key from /api/app/branding.
  useEffect(() => {
    if (!isPickerOpen || !mapsLoaded || !searchInputRef.current || !window.google?.maps?.places) return undefined;

    const autocomplete = new window.google.maps.places.Autocomplete(searchInputRef.current, {
      fields: ['geometry', 'formatted_address', 'name'],
    });
    autocompleteRef.current = autocomplete;

    const listener = autocomplete.addListener('place_changed', () => {
      const place = autocomplete.getPlace();
      const geometry = place?.geometry?.location;
      if (!geometry) return;
      pick({
        lat: geometry.lat(),
        lng: geometry.lng(),
        label: place.formatted_address || place.name || 'Selected location',
        source: 'google_places',
      });
    });

    return () => {
      window.google.maps.event.removeListener(listener);
      autocompleteRef.current = null;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isPickerOpen, mapsLoaded]);

  function pick(next) {
    setLocation(next);
    closePicker();
  }

  function handleUseMyLocation() {
    awaitingGeoRef.current = true;
    requestMyLocation();
  }

  function pickAddress(address) {
    if (address.latitude == null || address.longitude == null) return;
    pick({
      lat: address.latitude,
      lng: address.longitude,
      label: address.name ? `${address.name}, ${address.address}` : address.address,
      source: 'address',
      addressId: address.id,
    });
  }

  async function submitManual(e) {
    e.preventDefault();
    setManualError(null);
    const parsedLat = Number(lat);
    const parsedLng = Number(lng);
    if (!addressText.trim()) {
      setManualError('Enter an address.');
      return;
    }
    if (!Number.isFinite(parsedLat) || !Number.isFinite(parsedLng)) {
      setManualError('Latitude and longitude must be numbers.');
      return;
    }

    if (saveToAccount && isAuthed) {
      setSaving(true);
      try {
        await api.post(
          '/addresses',
          { name: label || 'Other', address: addressText, latitude: parsedLat, longitude: parsedLng },
          { auth: true }
        );
      } catch (err) {
        setManualError(err.message);
        setSaving(false);
        return;
      }
      setSaving(false);
    }

    pick({
      lat: parsedLat,
      lng: parsedLng,
      label: label ? `${label}, ${addressText}` : addressText,
      source: 'manual',
    });
  }

  if (!isPickerOpen) return null;

  const mapsConfigured = !!branding.google_maps_api_key;

  return (
    <div
      className="fixed inset-0 z-50 flex items-start justify-center bg-black/50 p-4 pt-16 backdrop-blur-sm sm:items-center"
      onClick={(e) => {
        if (e.target === e.currentTarget && hasLocation) closePicker();
      }}
    >
      <div className="max-h-[85vh] w-full max-w-md overflow-y-auto rounded-3xl border border-line bg-surface p-6 shadow-pop">
        <div className="mb-4 flex items-center justify-between">
          <h2 className="font-display text-lg font-extrabold tracking-tight text-ink">Select delivery location</h2>
          {hasLocation && (
            <button onClick={closePicker} className="flex h-9 w-9 items-center justify-center rounded-full text-ink-soft transition hover:bg-surface-alt" aria-label="Close">
              <CloseIcon />
            </button>
          )}
        </div>

        {location && (
          <p className="mb-4 rounded-xl bg-surface-alt px-3 py-2.5 text-xs text-ink-soft">
            Currently delivering to <span className="font-bold text-ink">{location.label}</span>
          </p>
        )}

        {mapsConfigured ? (
          <div className="mb-3">
            <input
              ref={searchInputRef}
              type="text"
              placeholder={mapsLoaded ? 'Search for area, street name...' : 'Loading location search...'}
              disabled={!mapsLoaded}
              className="field px-4 py-3 disabled:bg-surface-alt"
            />
            {mapsError && <p className="mt-1 text-xs font-medium text-danger">{mapsError}</p>}
          </div>
        ) : (
          <p className="mb-3 rounded-xl bg-surface-alt px-3 py-2.5 text-xs text-ink-soft">
            Location search isn't configured yet — add a Google Maps API key in Admin → Settings → Map to search by
            address here. GPS, saved addresses and manual entry still work below.
          </p>
        )}

        <button
          onClick={handleUseMyLocation}
          disabled={isLocating}
          className="mb-2 flex w-full items-center gap-2 rounded-xl border border-brand bg-brand-tint px-4 py-3 text-left text-sm font-bold text-brand-strong transition hover:bg-brand-tint-strong disabled:opacity-60"
        >
          <TargetIcon />
          {isLocating ? 'Getting your location...' : 'Use my current location'}
        </button>
        {geoError && <p className="mb-3 text-xs font-medium text-danger">{geoError}</p>}

        {serviceAreas.length > 0 && (
          <div className="mb-4">
            <p className="mb-2 text-xs font-bold uppercase tracking-wide text-ink-faint">Serviceable areas</p>
            <div className="flex flex-wrap gap-2">
              {serviceAreas.map((area) => (
                <button
                  key={area.id}
                  onClick={() => pick({ lat: area.latitude, lng: area.longitude, label: area.name, source: 'service_area' })}
                  className="chip py-1.5 text-xs"
                >
                  {area.name}
                </button>
              ))}
            </div>
          </div>
        )}

        {isAuthed && (
          <div className="mb-4 mt-3">
            <p className="mb-2 text-xs font-bold uppercase tracking-wide text-ink-faint">Saved addresses</p>
            {addressesLoading && <p className="text-sm text-ink-faint">Loading...</p>}
            {addressesError && <p className="text-sm font-medium text-danger">{addressesError}</p>}
            {!addressesLoading && !addressesError && addresses.length === 0 && (
              <p className="text-sm text-ink-faint">No saved addresses yet.</p>
            )}
            <div className="space-y-2">
              {addresses.map((addr) => (
                <button
                  key={addr.id}
                  onClick={() => pickAddress(addr)}
                  className="flex w-full flex-col items-start rounded-xl border border-line-strong bg-surface px-4 py-2.5 text-left transition hover:border-brand hover:bg-brand-tint"
                >
                  <span className="text-sm font-bold text-ink">{addr.name || 'Address'}</span>
                  <span className="line-clamp-1 text-xs text-ink-soft">{addr.address}</span>
                </button>
              ))}
            </div>
          </div>
        )}

        <button onClick={() => setShowManual((v) => !v)} className="mb-2 text-sm font-bold text-brand">
          {showManual ? 'Hide manual entry' : 'Enter address manually'}
        </button>

        {showManual && (
          <form onSubmit={submitManual} className="space-y-2 rounded-2xl border border-line bg-surface-alt p-3">
            <input
              value={label}
              onChange={(e) => setLabel(e.target.value)}
              placeholder="Label (e.g. Home, Work)"
              className="field"
            />
            <textarea
              value={addressText}
              onChange={(e) => setAddressText(e.target.value)}
              placeholder="Full address"
              rows={2}
              className="field"
            />
            <div className="flex gap-2">
              <input
                value={lat}
                onChange={(e) => setLat(e.target.value)}
                placeholder="Latitude"
                inputMode="decimal"
                className="field w-1/2"
              />
              <input
                value={lng}
                onChange={(e) => setLng(e.target.value)}
                placeholder="Longitude"
                inputMode="decimal"
                className="field w-1/2"
              />
            </div>
            {isAuthed && (
              <label className="flex items-center gap-2 text-xs text-ink-soft">
                <input type="checkbox" checked={saveToAccount} onChange={(e) => setSaveToAccount(e.target.checked)} />
                Save this address to my account
              </label>
            )}
            {manualError && <p className="text-xs font-medium text-danger">{manualError}</p>}
            <button
              type="submit"
              disabled={saving}
              className="btn btn-primary w-full"
            >
              {saving ? 'Saving...' : 'Use this address'}
            </button>
          </form>
        )}
      </div>
    </div>
  );
}

function TargetIcon() {
  return (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
      <circle cx="12" cy="12" r="8" />
      <circle cx="12" cy="12" r="2" />
      <path d="M12 2v3M12 19v3M2 12h3M19 12h3" />
    </svg>
  );
}

function CloseIcon() {
  return (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
      <path d="M18 6 6 18M6 6l12 12" />
    </svg>
  );
}
