import { createContext, useContext, useEffect, useState } from 'react';
import { api } from '../api/client';

const BrandingContext = createContext(null);
const STORAGE_KEY = 'swado_branding';

const FALLBACK = {
  app_name: 'Swado',
  primary_color: '#ff5a1f',
  secondary_color: '#2b2a33',
  app_logo: null,
  frontend_background_image: null,
  support_email: 'support@example.com',
  otp_service_provider: '',
  default_mobile_country_code: '+91',
  google_maps_api_key: '',
  header_branding_type: 'text',
  footer_description: '',
  footer_copyright: 'All rights reserved.',
  footer_company_title: 'Company',
  footer_support_title: 'Support',
  footer_legal_title: 'Legal',
  footer_link_about: 'About Us',
  footer_link_careers: 'Careers',
  footer_link_blog: 'Blog',
  footer_link_help: 'Help Center',
  footer_link_contact: 'Contact Us',
  footer_link_faqs: 'FAQs',
};

const CACHEABLE_KEYS = [
  'app_name',
  'primary_color',
  'secondary_color',
  'app_logo',
  'frontend_background_image',
  'support_email',
  'support_phone',
  'header_branding_type',
  'footer_description',
  'footer_copyright',
  'footer_company_title',
  'footer_support_title',
  'footer_legal_title',
  'footer_link_about',
  'footer_link_careers',
  'footer_link_blog',
  'footer_link_help',
  'footer_link_contact',
  'footer_link_faqs',
];

function applyBrandingSideEffects(data) {
  const root = document.documentElement;
  if (data.primary_color) root.style.setProperty('--brand-primary', data.primary_color);
  if (data.secondary_color) root.style.setProperty('--brand-secondary', data.secondary_color);
  document.title = data.app_name || FALLBACK.app_name;
}

function cacheableBranding(data) {
  return CACHEABLE_KEYS.reduce((acc, key) => {
    if (data[key] !== undefined) acc[key] = data[key];
    return acc;
  }, {});
}

function readSavedBranding() {
  try {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (!saved) return FALLBACK;
    const parsed = JSON.parse(saved);
    return { ...FALLBACK, ...cacheableBranding(parsed) };
  } catch {
    return FALLBACK;
  }
}

export function BrandingProvider({ children }) {
  const [branding, setBranding] = useState(readSavedBranding);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;
    api
      .get('/app/branding')
      .then((res) => {
        if (cancelled || !res?.data) return;
        const payload = res.data?.data ?? res.data;
        const data = { ...FALLBACK, ...payload };
        setBranding(data);
        localStorage.setItem(STORAGE_KEY, JSON.stringify(cacheableBranding(data)));
        applyBrandingSideEffects(data);
      })
      .catch(() => {
        // Keep fallback branding if the API is unreachable.
      })
      .finally(() => !cancelled && setLoading(false));
    return () => {
      cancelled = true;
    };
  }, []);

  return <BrandingContext.Provider value={{ branding, loading }}>{children}</BrandingContext.Provider>;
}

export function useBranding() {
  return useContext(BrandingContext);
}
