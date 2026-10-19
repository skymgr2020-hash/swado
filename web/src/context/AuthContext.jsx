import { createContext, useContext, useEffect, useState } from 'react';
import { api, getToken, setToken } from '../api/client';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [isLoginOpen, setIsLoginOpen] = useState(false);
  // 'login' | 'signup' -- both handled by the same popup (LoginModal); there
  // is no standalone /register page.
  const [loginModalMode, setLoginModalMode] = useState('login');

  useEffect(() => {
    const token = getToken();
    if (!token) {
      setLoading(false);
      return;
    }
    api
      .get('/user', { auth: true })
      .then((res) => setUser(res?.data ?? null))
      .catch(() => {
        setToken(null);
        setUser(null);
      })
      .finally(() => setLoading(false));
  }, []);

  async function login(email, password) {
    const res = await api.post('/login', { email, password });
    setToken(res.data.token);
    setUser(res.data.user);
    return res.data.user;
  }

  // Used by the registration flow, which authenticates via a separate
  // phone-OTP + /register call rather than login() — still needs to land
  // the resulting session in context state, not just localStorage.
  function setSession(nextUser, token) {
    setToken(token);
    setUser(nextUser);
  }

  function logout() {
    api.post('/logout', undefined, { auth: true }).catch(() => {});
    setToken(null);
    setUser(null);
  }

  function openLogin(mode = 'login') {
    setLoginModalMode(mode === 'signup' ? 'signup' : 'login');
    setIsLoginOpen(true);
  }

  function closeLogin() {
    setIsLoginOpen(false);
  }

  return (
    <AuthContext.Provider
      value={{
        user,
        loading,
        login,
        logout,
        setSession,
        isAuthed: !!user,
        isLoginOpen,
        loginModalMode,
        openLogin,
        closeLogin,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  return useContext(AuthContext);
}
