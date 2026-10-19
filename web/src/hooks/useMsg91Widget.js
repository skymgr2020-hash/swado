import { useEffect, useRef, useState } from 'react';

const SCRIPT_SRC = 'https://verify.msg91.com/otp-provider.js';
let scriptPromise = null;

function loadScript() {
  if (window.initSendOTP) return Promise.resolve();
  if (scriptPromise) return scriptPromise;

  scriptPromise = new Promise((resolve, reject) => {
    const script = document.createElement('script');
    script.src = SCRIPT_SRC;
    script.async = true;
    script.onload = () => resolve();
    script.onerror = () => {
      scriptPromise = null;
      reject(new Error('Failed to load the MSG91 OTP widget.'));
    };
    document.head.appendChild(script);
  });

  return scriptPromise;
}

// MSG91's widget response shapes aren't consistently documented publicly;
// their own examples show the verified token landing in different fields
// across SDK versions. Check the plausible ones defensively instead of
// betting everything on a single guess.
export function extractMsg91Token(data) {
  if (!data) return '';
  if (typeof data === 'string') return data;
  return (
    data.message ||
    data.token ||
    data.access_token ||
    data['access-token'] ||
    data.accessToken ||
    ''
  );
}

// Wraps the widget config a real /auth/otp/send response returns
// (data.msg91_widget: {widget_id, token_auth, otp_length}) when the admin
// has MSG91 configured in "widget" mode -- see
// admin/app/Services/SmsService.php publicMsg91WidgetConfig(). The widget
// handles the actual OTP send/verify itself via MSG91's servers; our
// backend only ever sees the resulting access token (POST /auth/otp/verify
// with msg91_access_token), never the OTP digits.
export function useMsg91Widget(widgetConfig, identifier) {
  const [ready, setReady] = useState(false);
  const [scriptError, setScriptError] = useState(null);
  const initializedRef = useRef(false);

  useEffect(() => {
    if (!widgetConfig?.widget_id || !widgetConfig?.token_auth || !identifier) return undefined;
    let cancelled = false;

    loadScript()
      .then(() => {
        if (cancelled) return;
        window.initSendOTP({
          widgetId: widgetConfig.widget_id,
          tokenAuth: widgetConfig.token_auth,
          identifier,
          exposeMethods: true,
          success: () => {},
          failure: () => {},
        });
        initializedRef.current = true;
        setReady(true);
      })
      .catch((err) => !cancelled && setScriptError(err.message));

    return () => {
      cancelled = true;
    };
  }, [widgetConfig?.widget_id, widgetConfig?.token_auth, identifier]);

  function sendOtp() {
    return new Promise((resolve, reject) => {
      if (!window.sendOtp) {
        reject(new Error('OTP widget is not ready yet.'));
        return;
      }
      window.sendOtp(
        identifier,
        (data) => resolve(data),
        (error) => reject(new Error(error?.message || 'Failed to send OTP.'))
      );
    });
  }

  function verifyOtp(code, reqId) {
    return new Promise((resolve, reject) => {
      if (!window.verifyOtp) {
        reject(new Error('OTP widget is not ready yet.'));
        return;
      }
      window.verifyOtp(
        Number(code),
        (data) => resolve(data),
        (error) => reject(new Error(error?.message || 'Invalid or expired OTP.')),
        reqId
      );
    });
  }

  function retryOtp(reqId, channel = '11') {
    return new Promise((resolve, reject) => {
      if (!window.retryOtp) {
        reject(new Error('OTP widget is not ready yet.'));
        return;
      }
      window.retryOtp(
        channel,
        (data) => resolve(data),
        (error) => reject(new Error(error?.message || 'Failed to resend OTP.')),
        reqId
      );
    });
  }

  return { ready, scriptError, sendOtp, verifyOtp, retryOtp };
}
