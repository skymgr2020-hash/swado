const DEFAULT_BASE_URL = '/api';
const BASE_URL = (import.meta.env.VITE_API_BASE_URL || DEFAULT_BASE_URL).replace(/\/$/, '');
const REQUEST_TIMEOUT_MS = Number(import.meta.env.VITE_API_TIMEOUT_MS || 25000);
const TOKEN_KEY = 'swado_token';

export function getToken() {
  return localStorage.getItem(TOKEN_KEY);
}

export function setToken(token) {
  if (token) localStorage.setItem(TOKEN_KEY, token);
  else localStorage.removeItem(TOKEN_KEY);
}

class ApiError extends Error {
  constructor(message, status, payload) {
    super(message);
    this.status = status;
    this.payload = payload;
  }
}

const GENERIC_ERROR = 'Something went wrong. Please try again.';

// Server messages that are meant for developers, not customers -- framework
// exceptions, SQL errors, class/namespace names, file paths, raw route
// strings ("The route api/orders could not be found", "No query results for
// model [App\\Models\\Order]"). If a message matches, we show a clean line
// instead of leaking internals to the UI.
const TECHNICAL_MESSAGE =
  /(\\|::|\/api\/|\bapi\/[a-z]|SQLSTATE|QueryException|[A-Za-z]+Exception\b|Stack trace|vendor\/|app\/Http|Illuminate|No query results for model|could not be found|Route \[|Call to (a member function|undefined)|Undefined (variable|property|array key|index)|syntax error|Class ["'A-Za-z0-9_]+ not found|Target class|[A-Za-z]:\\\\|\/var\/www|\/home\/[a-z]|MSG91|for this widget|invalid widget|widget[ _](id|is not|not found)|OTP widget|token_?auth)/i;

function statusFallback(status) {
  if (status === 401) return 'Please log in to continue.';
  if (status === 403) return "You don't have permission to do that.";
  if (status === 404) return "We couldn't find what you were looking for.";
  if (status === 429) return "You're doing that too often. Please wait a moment and try again.";
  return GENERIC_ERROR;
}

// Public: turn any message (server-provided or thrown) into customer-safe copy.
// 4xx messages that read like real user guidance are kept; everything that
// looks like an internal detail is replaced with a clean fallback.
export function sanitizeMessage(message, status = 0, fallback) {
  const fb = fallback || statusFallback(status);
  const text = typeof message === 'string' ? message.trim() : '';
  // 401s and 5xx never carry copy worth showing a customer verbatim.
  if (status === 401) return fallback || 'Please log in to continue.';
  if (status >= 500) return fb;
  if (!text) return fb;
  if (text.length > 180 || /\n/.test(text)) return fb;
  if (TECHNICAL_MESSAGE.test(text)) return fb;
  return text;
}

function buildUrl(path) {
  if (/^https?:\/\//i.test(path)) return path;
  return `${BASE_URL}${path.startsWith('/') ? path : `/${path}`}`;
}

async function request(path, { method = 'GET', body, auth = false, signal } = {}) {
  const headers = { Accept: 'application/json' };
  if (body) headers['Content-Type'] = 'application/json';
  if (auth) {
    const token = getToken();
    if (token) headers.Authorization = `Bearer ${token}`;
  }

  const controller = new AbortController();
  const abortRequest = () => controller.abort();
  const timeoutId = window.setTimeout(abortRequest, REQUEST_TIMEOUT_MS);
  if (signal) {
    if (signal.aborted) controller.abort();
    else signal.addEventListener('abort', abortRequest, { once: true });
  }

  let res;
  try {
    res = await fetch(buildUrl(path), {
      method,
      headers,
      body: body ? JSON.stringify(body) : undefined,
      signal: controller.signal,
    });
  } catch (error) {
    const isAbort = error?.name === 'AbortError' || controller.signal.aborted;
    throw new ApiError(
      isAbort
        ? 'Connection timed out. Please try again.'
        : 'Unable to connect right now. Please check your internet and try again.',
      0,
      null
    );
  } finally {
    window.clearTimeout(timeoutId);
    signal?.removeEventListener?.('abort', abortRequest);
  }

  let payload = null;
  try {
    payload = await res.json();
  } catch {
    payload = null;
  }

  if (!res.ok) {
    const raw = payload?.message;
    const error = new ApiError(sanitizeMessage(raw, res.status), res.status, payload);
    error.serverMessage = typeof raw === 'string' ? raw : null;
    throw error;
  }

  return payload;
}

// Turns a thrown error into copy that's safe to show a customer. Messages
// thrown by `api.*` are already sanitized; this also covers errors from other
// sources and lets a caller supply a context-specific fallback.
export function friendlyError(error, fallback = GENERIC_ERROR) {
  return sanitizeMessage(error?.message, error?.status ?? 0, fallback);
}

export const api = {
  get: (path, opts) => request(path, { ...opts, method: 'GET' }),
  post: (path, body, opts) => request(path, { ...opts, method: 'POST', body }),
  put: (path, body, opts) => request(path, { ...opts, method: 'PUT', body }),
  del: (path, opts) => request(path, { ...opts, method: 'DELETE' }),
};

export { ApiError };
