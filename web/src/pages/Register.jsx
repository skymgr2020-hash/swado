import { useEffect } from 'react';
import { Navigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

// Signup no longer has its own page -- it's handled by the shared login/signup
// popup (LoginModal). Any hit on /register just opens that popup in signup
// mode and bounces to the home page.
export default function Register() {
  const { openLogin } = useAuth();

  useEffect(() => {
    openLogin('signup');
    // Run once on mount -- openLogin is stable for our purposes here.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return <Navigate to="/" replace />;
}
