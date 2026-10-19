import { Link } from 'react-router-dom';
import { useState } from 'react';
import { useBranding } from '../context/BrandingContext';
import { useUserLocation } from '../context/LocationContext';
import { useCart } from '../context/CartContext';
import { useAuth } from '../context/AuthContext';
import SearchAutocomplete from './SearchAutocomplete';
import { PinIcon, CartIcon, SearchIcon, ChevronDownIcon } from './ui/icons';

export default function Navbar() {
  const { branding } = useBranding();
  const { location, openPicker, isLocating } = useUserLocation();
  const { itemCount, openDrawer } = useCart();
  const { user, logout, isAuthed, openLogin } = useAuth();
  const [query, setQuery] = useState('');
  const brandingType = ['text', 'logo', 'logo_text'].includes(branding.header_branding_type)
    ? branding.header_branding_type
    : 'text';
  const showLogo = branding.app_logo && brandingType !== 'text';
  const showText = brandingType !== 'logo' || !branding.app_logo;

  const searchInput =
    'w-full rounded-full border border-line-strong bg-surface-alt py-2.5 pl-10 pr-4 text-sm text-ink outline-none transition placeholder:text-ink-faint focus:border-brand focus:bg-surface focus:shadow-[0_0_0_3px_var(--color-brand-tint-strong)]';

  return (
    <header className="sticky top-0 z-30 border-b border-line bg-surface/85 backdrop-blur-md">
      <div className="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3">
        <Link to="/" className="flex min-w-0 shrink-0 items-center gap-2">
          {showLogo ? (
            <img src={branding.app_logo} alt={branding.app_name} className="h-9 w-auto max-w-32 object-contain" />
          ) : !branding.app_logo && brandingType !== 'logo' ? (
            <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-brand text-base font-extrabold text-white shadow-brand">
              {branding.app_name?.[0] ?? 'S'}
            </span>
          ) : null}
          {showText && (
            <span className="truncate font-display text-lg font-extrabold tracking-tight text-ink">
              {branding.app_name}
            </span>
          )}
        </Link>

        <button
          onClick={openPicker}
          title="Change delivery location"
          className="hidden shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1.5 text-left leading-tight transition hover:bg-surface-alt sm:flex"
        >
          <PinIcon size={18} className="shrink-0 text-brand" />
          <span className="flex min-w-0 flex-col">
            <span className="text-[11px] font-medium text-ink-faint">Delivering to</span>
            <span className="flex items-center gap-1 text-sm font-bold text-ink">
              <span className="max-w-[150px] truncate">
                {isLocating ? 'Finding location…' : location?.label || 'Select location'}
              </span>
              <ChevronDownIcon size={14} className="shrink-0 text-ink-soft" />
            </span>
          </span>
        </button>

        <button
          onClick={openPicker}
          className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-brand transition hover:bg-surface-alt sm:hidden"
          aria-label="Change delivery location"
        >
          <PinIcon />
        </button>

        <SearchAutocomplete
          value={query}
          onChange={setQuery}
          placeholder="Search for restaurants and food"
          className="flex flex-1 items-center"
          inputClassName={searchInput}
          adornment={<SearchIcon size={17} className="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-faint" />}
        />

        <button
          onClick={openDrawer}
          className="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-ink transition hover:bg-surface-alt"
          aria-label={`Open cart${itemCount > 0 ? `, ${itemCount} items` : ''}`}
        >
          <CartIcon />
          {itemCount > 0 && (
            <span className="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-brand px-1 text-[11px] font-extrabold text-white ring-2 ring-surface">
              {itemCount}
            </span>
          )}
        </button>

        {isAuthed ? (
          <div className="group relative shrink-0">
            <button className="flex items-center gap-1 rounded-full bg-surface-alt px-3.5 py-2 text-sm font-bold text-ink">
              {user?.name?.split(' ')[0] || 'Account'}
              <ChevronDownIcon size={14} className="text-ink-soft" />
            </button>
            <div className="invisible absolute right-0 z-40 mt-1.5 w-44 rounded-2xl border border-line bg-surface p-1.5 opacity-0 shadow-pop transition group-hover:visible group-hover:opacity-100">
              <Link to="/orders" className="block rounded-xl px-3 py-2 text-sm font-medium text-ink-soft transition hover:bg-surface-alt hover:text-ink">
                My orders
              </Link>
              <button
                onClick={logout}
                className="block w-full rounded-xl px-3 py-2 text-left text-sm font-medium text-danger transition hover:bg-surface-alt"
              >
                Logout
              </button>
            </div>
          </div>
        ) : (
          <button onClick={openLogin} className="btn btn-primary shrink-0 hidden sm:inline-flex">
            Log in
          </button>
        )}
        {!isAuthed && (
          <button onClick={openLogin} className="btn btn-ghost shrink-0 px-4 sm:hidden">
            Log in
          </button>
        )}
      </div>
    </header>
  );
}
