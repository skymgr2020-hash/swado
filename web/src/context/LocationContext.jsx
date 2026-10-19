import { createContext, useContext, useEffect, useState } from 'react';

const LocationContext = createContext(null);
const STORAGE_KEY = 'swado_location';

function readSavedLocation() {
  try {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (!saved) return null;
    const parsed = JSON.parse(saved);
    const lat = Number(parsed?.lat);
    const lng = Number(parsed?.lng);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
    return {
      ...parsed,
      lat,
      lng,
      label: parsed?.label || 'Selected location',
      source: parsed?.source || 'saved',
    };
  } catch {
    return null;
  }
}

export function LocationProvider({ children }) {
  const [location, setLocationState] = useState(readSavedLocation);
  const [geoError, setGeoError] = useState(null);
  const [isLocating, setIsLocating] = useState(false);
  // Force the picker open on first visit (no saved location yet) so the
  // customer always chooses/confirms where they're ordering to.
  const [isPickerOpen, setIsPickerOpen] = useState(() => !readSavedLocation());

  useEffect(() => {
    if (location) {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(location));
    } else {
      localStorage.removeItem(STORAGE_KEY);
    }
  }, [location]);

  function setLocation(next) {
    setGeoError(null);
    if (!next) {
      setLocationState(null);
      return;
    }

    const lat = Number(next.lat);
    const lng = Number(next.lng);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
      setLocationState(null);
      return;
    }

    setLocationState({
      ...next,
      lat,
      lng,
      label: next.label || 'Selected location',
      source: next.source || 'manual',
    });
  }

  function requestMyLocation() {
    if (!navigator.geolocation) {
      setGeoError('Geolocation is not supported in this browser.');
      return;
    }
    setIsLocating(true);
    setGeoError(null);
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        setLocation({
          lat: pos.coords.latitude,
          lng: pos.coords.longitude,
          label: 'Current location',
          source: 'gps',
        });
        setIsLocating(false);
      },
      (err) => {
        setGeoError(err.message || 'Could not get your location.');
        setIsLocating(false);
      },
      { enableHighAccuracy: true, timeout: 8000 }
    );
  }

  function clearLocation() {
    setLocation(null);
  }

  function openPicker() {
    setGeoError(null);
    setIsPickerOpen(true);
  }

  function closePicker() {
    setIsPickerOpen(false);
  }

  return (
    <LocationContext.Provider
      value={{
        location,
        hasLocation: !!location,
        setLocation,
        requestMyLocation,
        clearLocation,
        geoError,
        isLocating,
        isPickerOpen,
        openPicker,
        closePicker,
      }}
    >
      {children}
    </LocationContext.Provider>
  );
}

export function useUserLocation() {
  return useContext(LocationContext);
}
