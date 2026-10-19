import { Link } from 'react-router-dom';
import { useBranding } from '../context/BrandingContext';

function FooterLink({ children, to, href }) {
  const cls = 'text-sm text-ink-soft transition hover:text-brand';
  if (href) {
    return (
      <a href={href} className={cls}>
        {children}
      </a>
    );
  }
  return (
    <Link to={to} className={cls}>
      {children}
    </Link>
  );
}

export default function Footer() {
  const { branding } = useBranding();
  const year = new Date().getFullYear();
  const brandingType = ['text', 'logo', 'logo_text'].includes(branding.header_branding_type)
    ? branding.header_branding_type
    : 'text';
  const showLogo = branding.app_logo && brandingType !== 'text';
  const showText = brandingType !== 'logo' || !branding.app_logo;
  const description =
    branding.footer_description || 'Order food from the best restaurants in your city. Fast delivery, great taste!';
  const copyright = branding.footer_copyright || 'All rights reserved.';
  const mailto = branding.support_email ? `mailto:${branding.support_email}` : undefined;

  return (
    <footer className="mt-4 border-t border-line bg-surface pb-16 sm:pb-0">
      <div className="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">
        <div>
          <Link to="/" className="inline-flex max-w-full items-center gap-2">
            {showLogo ? (
              <img src={branding.app_logo} alt={branding.app_name} className="h-10 w-auto max-w-36 object-contain" />
            ) : !branding.app_logo && brandingType !== 'logo' ? (
              <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-brand text-sm font-extrabold text-white">
                {branding.app_name?.[0] ?? 'S'}
              </span>
            ) : null}
            {showText && (
              <span className="truncate font-display text-lg font-extrabold tracking-tight text-ink">
                {branding.app_name || 'Swado'}
              </span>
            )}
          </Link>
          <p className="mt-3 max-w-sm text-sm leading-6 text-ink-soft">{description}</p>
        </div>

        <div>
          <h2 className="text-xs font-bold uppercase tracking-wide text-ink-faint">
            {branding.footer_company_title || 'Company'}
          </h2>
          <div className="mt-3.5 flex flex-col gap-2.5">
            <FooterLink to="/p/about">{branding.footer_link_about || 'About Us'}</FooterLink>
            <FooterLink to="/p/careers">{branding.footer_link_careers || 'Careers'}</FooterLink>
            <FooterLink to="/p/blog">{branding.footer_link_blog || 'Blog'}</FooterLink>
          </div>
        </div>

        <div>
          <h2 className="text-xs font-bold uppercase tracking-wide text-ink-faint">
            {branding.footer_support_title || 'Support'}
          </h2>
          <div className="mt-3.5 flex flex-col gap-2.5">
            <FooterLink to="/p/help">{branding.footer_link_help || 'Help Center'}</FooterLink>
            <FooterLink href={mailto} to="/p/help">
              {branding.footer_link_contact || 'Contact Us'}
            </FooterLink>
            <FooterLink to="/p/faqs">{branding.footer_link_faqs || 'FAQs'}</FooterLink>
            {branding.support_email && (
              <a className="text-sm text-ink-soft transition hover:text-brand" href={`mailto:${branding.support_email}`}>
                {branding.support_email}
              </a>
            )}
            {branding.support_phone && (
              <a className="text-sm text-ink-soft transition hover:text-brand" href={`tel:${branding.support_phone}`}>
                {branding.support_phone}
              </a>
            )}
          </div>
        </div>

        <div>
          <h2 className="text-xs font-bold uppercase tracking-wide text-ink-faint">
            {branding.footer_legal_title || 'Legal'}
          </h2>
          <div className="mt-3.5 flex flex-col gap-2.5">
            <FooterLink to="/p/terms">Terms</FooterLink>
            <FooterLink to="/p/privacy">Privacy</FooterLink>
            <FooterLink to="/p/refund">Refund Policy</FooterLink>
          </div>
        </div>
      </div>
      <div className="border-t border-line">
        <div className="mx-auto max-w-6xl px-4 py-5 text-sm text-ink-faint">
          &copy; {year} {branding.app_name || 'Swado'}. {copyright}
        </div>
      </div>
    </footer>
  );
}
