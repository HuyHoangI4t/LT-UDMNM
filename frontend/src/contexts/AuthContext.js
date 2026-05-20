import React, { createContext, useCallback, useContext, useMemo, useState } from 'react';
import axios from 'axios';

const API_URL = process.env.REACT_APP_API_URL || 'http://127.0.0.1:8000/api';
const TOKEN_KEY = 'ttn-admin-token';
const USER_KEY = 'ttn-admin-user';

const AuthContext = createContext(null);

export const AuthProvider = ({ children }) => {
  const [token, setToken] = useState(() => localStorage.getItem(TOKEN_KEY) || '');
  const [user, setUser] = useState(() => {
    try {
      const raw = localStorage.getItem(USER_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch {
      return null;
    }
  });

  const isAuthenticated = Boolean(token);

  const login = useCallback(async ({ email, password }) => {
    const response = await axios.post(`${API_URL}/login`, { email, password });
    const nextToken = response?.data?.data?.token;
    const nextUser = response?.data?.data?.user;

    if (!nextToken) {
      throw new Error('API khong tra ve access token.');
    }

    localStorage.setItem(TOKEN_KEY, nextToken);
    localStorage.setItem(USER_KEY, JSON.stringify(nextUser || null));
    setToken(nextToken);
    setUser(nextUser || null);
  }, []);

  const logout = useCallback(async () => {
    if (token) {
      try {
        await axios.post(
          `${API_URL}/logout`,
          {},
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        );
      } catch {
        // Local logout still matters if the token is already expired.
      }
    }

    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
    setToken('');
    setUser(null);
  }, [token]);

  const authHeaders = useMemo(
    () => ({
      Authorization: token ? `Bearer ${token}` : '',
    }),
    [token]
  );

  const value = useMemo(
    () => ({
      token,
      user,
      isAuthenticated,
      authHeaders,
      login,
      logout,
    }),
    [token, user, isAuthenticated, authHeaders, login, logout]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

export const useAuth = () => {
  const context = useContext(AuthContext);

  if (!context) {
    throw new Error('useAuth must be used inside AuthProvider.');
  }

  return context;
};
