import { useEffect, useMemo, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { api, friendlyError } from '../api/client';
import { useBranding } from '../context/BrandingContext';
import { ErrorState } from '../components/Skeletons';

// Legal slugs come from GET /content/legal (data: { terms, privacy, refund,
// contact_email }). The admin edits these in a plain <textarea>, so the value
// is plain text with blank-line paragraph breaks -- only treated as HTML when
// it actually contains tags. Everything else in the footer renders a small
// branded info page so no link is a dead end.
const HTML_LIKE = /<(p|br|h[1-6]|ul|ol|li|strong|em|a|div)\b/i;
const LEGAL = {
  terms: { key: 'terms', title: 'Terms of Service' },
  privacy: { key: 'privacy', title: 'Privacy Policy' },
  refund: { key: 'refund', title: 'Refund Policy' },
};

const INFO = {
  about: {
    title: 'About Us',
    body: (b) =>
      b.footer_description ||
      `${b.app_name || 'Swado'} connects you with the best restaurants in your city for fast, reliable food delivery.`,
  },
  careers: {
    title: 'Careers',
    body: (b) => `We're always looking for people who love food and great products. Write to us at ${b.support_email || 'our support address'} to say hello.`,
  },
  blog: {
    title: 'Blog',
    body: () => 'Stories, updates and food guides are coming soon.',
  },
  help: {
    title: 'Help Center',
    body: (b) => `Need a hand with an order? Reach our support team at ${b.support_email || 'the support address below'}${b.support_phone ? ` or call ${b.support_phone}` : ''}.`,
  },
  faqs: {
    title: 'FAQs',
    body: (b) => `Common questions about orders, delivery and refunds. For anything else, contact ${b.support_email || 'support'}.`,
  },
};

export default function InfoPage() {
  const { slug } = useParams();
  const { branding } = useBranding();
  const legal = LEGAL[slug];
  const info = INFO[slug];

  const [html, setHtml] = useState(null);
  const [loading, setLoading] = useState(!!legal);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (!legal) return undefined;
    let cancelled = false;
    setLoading(true);
    setError(null);
    setHtml(null);
    api
      .get('/content/legal')
      .then((res) => {
        if (cancelled) return;
        const data = res?.data ?? {};
        setHtml(data[legal.key] ?? '');
      })
      .catch((err) => !cancelled && setError(friendlyError(err, "We couldn't load this page. Please try again.")))
      .finally(() => !cancelled && setLoading(false));
    return () => {
      cancelled = true;
    };
  }, [slug, legal]);

  const title = legal?.title || info?.title || 'Information';
  const isHtml = useMemo(() => typeof html === 'string' && HTML_LIKE.test(html), [html]);

  return (
    <div className="mx-auto max-w-2xl px-4 py-10">
      <Link to="/" className="text-sm font-bold text-brand">
        ← Back to home
      </Link>
      <h1 className="mt-3 font-display text-2xl font-extrabold tracking-tight text-ink">{title}</h1>

      <div className="mt-4 rounded-2xl border border-line bg-surface p-5 shadow-card">
        {legal ? (
          <>
            {loading && <p className="text-sm text-ink-soft">Loading…</p>}
            {error && <ErrorState message={error} />}
            {!loading && !error && (html
              ? (isHtml ? (
                <div
                  className="prose-legal text-sm leading-7 text-ink-soft [&_h2]:mt-4 [&_h2]:font-display [&_h2]:text-base [&_h2]:font-extrabold [&_h2]:text-ink [&_h3]:mt-3 [&_h3]:font-bold [&_h3]:text-ink [&_a]:font-semibold [&_a]:text-brand [&_ul]:my-2 [&_ul]:list-disc [&_ul]:pl-5 [&_p]:my-2"
                  dangerouslySetInnerHTML={{ __html: html }}
                />
              ) : (
                <div className="space-y-3 text-sm leading-7 text-ink-soft">
                  {html
                    .split(/\n{2,}/)
                    .map((para) => para.trim())
                    .filter(Boolean)
                    .map((para, i) => (
                      <p key={i} className="whitespace-pre-line">
                        {para}
                      </p>
                    ))}
                </div>
              ))
              : (
                <p className="text-sm text-ink-soft">This policy hasn’t been published yet.</p>
              ))}
          </>
        ) : info ? (
          <p className="text-sm leading-7 text-ink-soft">{info.body(branding)}</p>
        ) : (
          <p className="text-sm text-ink-soft">Page not found.</p>
        )}
      </div>

      {(info || legal) && (branding.support_email || branding.support_phone) && (
        <p className="mt-4 text-sm text-ink-soft">
          Contact us:{' '}
          {branding.support_email && (
            <a className="font-semibold text-brand" href={`mailto:${branding.support_email}`}>
              {branding.support_email}
            </a>
          )}
          {branding.support_email && branding.support_phone && ' · '}
          {branding.support_phone && (
            <a className="font-semibold text-brand" href={`tel:${branding.support_phone}`}>
              {branding.support_phone}
            </a>
          )}
        </p>
      )}
    </div>
  );
}
