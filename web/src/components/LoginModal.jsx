import { useEffect, useState } from 'react';
import { api, friendlyError } from '../api/client';
import { useAuth } from '../context/AuthContext';
import { useBranding } from '../context/BrandingContext';
import { useMsg91Widget, extractMsg91Token } from '../hooks/useMsg91Widget';

const STEPS = { PHONE: 'phone', OTP: 'otp', DETAILS: 'details' };

export default function LoginModal() {
  const { setSession, login, isLoginOpen, loginModalMode, closeLogin } = useAuth();
  const { branding } = useBranding();

  const [mode, setMode] = useState('login'); // 'login' | 'signup'
  const [method, setMethod] = useState('phone'); // 'phone' | 'email' (email = login only)
  const [step, setStep] = useState(STEPS.PHONE);
  const [phone, setPhone] = useState('');
  const [otp, setOtp] = useState('');
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [verifiedToken, setVerifiedToken] = useState(null);
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(false);

  // Which OTP mechanism the last /auth/otp/send call told us to use --
  // decided by the real API response (data.otp_flow === 'widget'), not
  // guessed from otp_service_provider.
  const [otpFlow, setOtpFlow] = useState('standard');
  const [widgetConfig, setWidgetConfig] = useState(null);
  const [widgetReqId, setWidgetReqId] = useState(null);
  const [awaitingWidgetSend, setAwaitingWidgetSend] = useState(false);

  const apiFlow = mode === 'signup' ? 'signup' : 'login';
  const fullPhone = `${(branding.default_mobile_country_code || '+91').replace('+', '')}${phone}`;
  const widget = useMsg91Widget(otpFlow === 'widget' ? widgetConfig : null, otpFlow === 'widget' ? fullPhone : null);

  function resetFlow() {
    setMethod('phone');
    setStep(STEPS.PHONE);
    setOtp('');
    setName('');
    setEmail('');
    setPassword('');
    setVerifiedToken(null);
    setError(null);
    setOtpFlow('standard');
    setWidgetConfig(null);
    setWidgetReqId(null);
    setAwaitingWidgetSend(false);
  }

  // Sync the popup to whichever mode opened it (Navbar = login, "Create
  // account" links = signup) and start from a clean slate each open.
  useEffect(() => {
    if (!isLoginOpen) return;
    setMode(loginModalMode === 'signup' ? 'signup' : 'login');
    setPhone('');
    resetFlow();
  }, [isLoginOpen, loginModalMode]);

  // Once the widget script has initialized for this phone number, trigger the
  // actual send (the earlier /auth/otp/send call only fetched widget config).
  useEffect(() => {
    if (!awaitingWidgetSend || !widget.ready) return;
    setAwaitingWidgetSend(false);
    widget
      .sendOtp()
      .then((data) => {
        setWidgetReqId(data?.reqId ?? data?.message ?? data?.request_id ?? null);
        setStep(STEPS.OTP);
      })
      .catch((err) => setError(friendlyError(err, "We couldn't send the code right now. Please try again in a moment.")))
      .finally(() => setLoading(false));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [awaitingWidgetSend, widget.ready]);

  if (!isLoginOpen) return null;

  const isSignup = mode === 'signup';
  const isFirebaseOtp = branding.otp_service_provider === 'firebase';

  function handleClose() {
    closeLogin();
    setPhone('');
    resetFlow();
  }

  function switchMode(nextMode) {
    setMode(nextMode);
    resetFlow();
  }

  async function sendOtp(e) {
    e.preventDefault();
    setLoading(true);
    setError(null);
    let isWidgetFlow = false;
    try {
      const res = await api.post('/auth/otp/send', { phone, flow: apiFlow, role: 'customer' });
      if (res?.data?.otp_flow === 'widget' && res.data.msg91_widget) {
        isWidgetFlow = true;
        setOtpFlow('widget');
        setWidgetConfig(res.data.msg91_widget);
        setAwaitingWidgetSend(true);
        return; // loading stays true until the widget effect resolves the send
      }
      setOtpFlow('standard');
      setStep(STEPS.OTP);
    } catch (err) {
      setError(friendlyError(err, "We couldn't send a code to that number right now. Please try again in a moment."));
    } finally {
      if (!isWidgetFlow) setLoading(false);
    }
  }

  async function verifyOtp(e) {
    e.preventDefault();
    setLoading(true);
    setError(null);
    try {
      let res;
      if (otpFlow === 'widget') {
        const data = await widget.verifyOtp(otp, widgetReqId);
        const accessToken = extractMsg91Token(data);
        res = await api.post('/auth/otp/verify', {
          phone,
          // In widget mode the backend verifies `msg91_access_token`; `otp` is
          // only sent to satisfy validation (string, 4-8 chars) -- never the
          // long access token, which fails the length rule and 422s.
          otp: /^\d{4,8}$/.test(otp) ? otp : '0000',
          flow: apiFlow,
          role: 'customer',
          msg91_access_token: accessToken,
        });
      } else {
        res = await api.post('/auth/otp/verify', { phone, otp, flow: apiFlow, role: 'customer' });
      }

      if (isSignup) {
        setVerifiedToken(res?.data?.verified_phone_token ?? null);
        setStep(STEPS.DETAILS);
      } else {
        setSession(res.data.user, res.data.token);
        handleClose();
      }
    } catch (err) {
      setError(friendlyError(err, "That code didn't work. Please check it and try again."));
    } finally {
      setLoading(false);
    }
  }

  async function completeSignup(e) {
    e.preventDefault();
    setLoading(true);
    setError(null);
    try {
      const res = await api.post('/register', {
        name,
        email,
        phone,
        verified_phone_token: verifiedToken,
        role: 'customer',
      });
      setSession(res.data.user, res.data.token);
      handleClose();
    } catch (err) {
      setError(friendlyError(err, "We couldn't create your account right now. Please try again."));
    } finally {
      setLoading(false);
    }
  }

  async function submitEmailLogin(e) {
    e.preventDefault();
    setLoading(true);
    setError(null);
    try {
      await login(email, password);
      handleClose();
    } catch (err) {
      setError(friendlyError(err, "We couldn't log you in. Please check your email and password."));
    } finally {
      setLoading(false);
    }
  }

  const title = isSignup ? 'Create account' : 'Log in';

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
      onClick={(e) => e.target === e.currentTarget && handleClose()}
    >
      <div className="w-full max-w-sm rounded-3xl border border-line bg-surface p-6 shadow-pop">
        <div className="mb-1 flex items-center justify-between">
          <h2 className="font-display text-lg font-extrabold tracking-tight text-ink">{title}</h2>
          <button
            onClick={handleClose}
            className="flex h-9 w-9 items-center justify-center rounded-full text-ink-soft transition hover:bg-surface-alt"
            aria-label="Close"
          >
            <CloseIcon />
          </button>
        </div>

        {method === 'phone' ? (
          <>
            <p className="mb-4 text-sm text-ink-soft">
              {isSignup
                ? "Sign up with your mobile number. We'll send a one-time code to verify it."
                : "We'll send a one-time code to verify your number."}
            </p>

            {isFirebaseOtp && !isSignup ? (
              <p className="rounded-xl bg-gold-tint p-3 text-sm text-gold">
                Phone login isn&rsquo;t available right now. Please log in with your email instead.
              </p>
            ) : step === STEPS.PHONE ? (
              <form onSubmit={sendOtp} className="space-y-3">
                <div>
                  <label className="mb-1.5 block text-xs font-bold text-ink-soft">Mobile number</label>
                  <div className="flex items-center gap-2">
                    <span className="rounded-xl border border-line-strong px-3 py-2.5 text-sm font-semibold text-ink-soft">
                      {branding.default_mobile_country_code}
                    </span>
                    <input
                      required
                      autoFocus
                      value={phone}
                      onChange={(e) => setPhone(e.target.value)}
                      placeholder="9876543210"
                      inputMode="numeric"
                      className="field"
                    />
                  </div>
                </div>
                {widget.scriptError && (
                  <p className="text-sm font-medium text-danger">
                    {friendlyError(
                      { message: widget.scriptError },
                      "Couldn't start phone verification. Please try again or use email login.",
                    )}
                  </p>
                )}
                {error && <p className="text-sm font-medium text-danger">{error}</p>}
                <button type="submit" disabled={loading} className="btn btn-primary w-full">
                  {loading ? 'Sending OTP...' : 'Send OTP'}
                </button>
                {(error || widget.scriptError) && !isSignup && (
                  <button
                    type="button"
                    onClick={() => {
                      setMethod('email');
                      setError(null);
                    }}
                    className="btn btn-outline w-full"
                  >
                    Log in with email instead
                  </button>
                )}
              </form>
            ) : step === STEPS.OTP ? (
              <form onSubmit={verifyOtp} className="space-y-3">
                <div>
                  <label className="mb-1.5 block text-xs font-bold text-ink-soft">
                    Enter the OTP sent to {branding.default_mobile_country_code}
                    {phone}
                  </label>
                  <input
                    required
                    autoFocus
                    value={otp}
                    onChange={(e) => setOtp(e.target.value)}
                    placeholder={otpFlow === 'widget' ? `${widgetConfig?.otp_length || 4}-digit code` : '6-digit code'}
                    inputMode="numeric"
                    className="field"
                  />
                </div>
                {error && <p className="text-sm font-medium text-danger">{error}</p>}
                <button type="submit" disabled={loading} className="btn btn-primary w-full">
                  {loading ? 'Verifying...' : isSignup ? 'Verify' : 'Verify & log in'}
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setStep(STEPS.PHONE);
                    setOtp('');
                    setError(null);
                  }}
                  className="w-full text-center text-xs font-bold text-ink-soft"
                >
                  Change number
                </button>
              </form>
            ) : (
              <form onSubmit={completeSignup} className="space-y-3">
                <div>
                  <label className="mb-1.5 block text-xs font-bold text-ink-soft">Full name</label>
                  <input
                    required
                    autoFocus
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    placeholder="Your name"
                    className="field"
                  />
                </div>
                <div>
                  <label className="mb-1.5 block text-xs font-bold text-ink-soft">Email</label>
                  <input
                    type="email"
                    required
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    placeholder="you@example.com"
                    className="field"
                  />
                </div>
                {error && <p className="text-sm font-medium text-danger">{error}</p>}
                <button type="submit" disabled={loading} className="btn btn-primary w-full">
                  {loading ? 'Creating account...' : 'Create account'}
                </button>
              </form>
            )}

            {!isSignup && (
              <p className="mt-4 text-center text-xs text-ink-faint">
                <button type="button" onClick={() => setMethod('email')} className="font-bold text-brand">
                  Log in with email instead
                </button>
              </p>
            )}
          </>
        ) : (
          <>
            <p className="mb-4 text-sm text-ink-soft">Log in with your account email and password.</p>
            <form onSubmit={submitEmailLogin} className="space-y-3">
              <div>
                <label className="mb-1.5 block text-xs font-bold text-ink-soft">Email</label>
                <input
                  type="email"
                  required
                  autoFocus
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="field"
                />
              </div>
              <div>
                <label className="mb-1.5 block text-xs font-bold text-ink-soft">Password</label>
                <input
                  type="password"
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="field"
                />
              </div>
              {error && <p className="text-sm font-medium text-danger">{error}</p>}
              <button type="submit" disabled={loading} className="btn btn-primary w-full">
                {loading ? 'Logging in...' : 'Log in'}
              </button>
            </form>
            <p className="mt-4 text-center text-xs text-ink-faint">
              <button type="button" onClick={() => setMethod('phone')} className="font-bold text-brand">
                Log in with mobile + OTP instead
              </button>
            </p>
          </>
        )}

        <p className="mt-4 text-center text-xs text-ink-faint">
          {isSignup ? (
            <>
              Already have an account?{' '}
              <button type="button" onClick={() => switchMode('login')} className="font-bold text-brand">
                Log in
              </button>
            </>
          ) : (
            <>
              New here?{' '}
              <button type="button" onClick={() => switchMode('signup')} className="font-bold text-brand">
                Create an account
              </button>
            </>
          )}
        </p>
      </div>
    </div>
  );
}

function CloseIcon() {
  return (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
      <path d="M18 6 6 18M6 6l12 12" />
    </svg>
  );
}
