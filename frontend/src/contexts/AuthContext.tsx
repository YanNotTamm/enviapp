import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import axios from 'axios';
import apiClient from '../utils/axiosConfig';
import { handleApiError, getErrorMessage } from '../utils/errorHandler';

interface User {
  id: number;
  username: string;
  email: string;
  role: string;
  nama_perusahaan: string;
  alamat_perusahaan: string;
  no_telp: string;
  envipoin: number;
  email_verified: boolean;
  created_at: string;
}

interface AuthContextType {
  user: User | null;
  token: string | null;
  login: (email: string, password: string) => Promise<void>;
  register: (userData: RegisterData) => Promise<void>;
  logout: () => void;
  loading: boolean;
  error: string | null;
  clearError: () => void;
}

interface RegisterData {
  username: string;
  email: string;
  password: string;
  nama_perusahaan: string;
  alamat_perusahaan: string;
  no_telp: string;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

export const AuthProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(localStorage.getItem('token'));
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (token) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      fetchUserProfile();
    } else {
      setLoading(false);
    }
  }, [token]);

  const fetchUserProfile = async () => {
    try {
      const response = await apiClient.get('/user/profile');
      setUser(response.data.data);
    } catch (error) {
      console.error('Failed to fetch user profile:', error);
      logout();
    } finally {
      setLoading(false);
    }
  };

  const login = async (email: string, password: string) => {
    try {
      setError(null);
      console.log('Sending login request to API...');
      const response = await apiClient.post('/auth/login', { email, password });
      console.log('Login API response:', response.data);
      
      const { token: newToken, user: userData } = response.data.data;
      
      localStorage.setItem('token', newToken);
      setToken(newToken);
      setUser(userData);
      axios.defaults.headers.common['Authorization'] = `Bearer ${newToken}`;
      console.log('Login successful, token and user set');
    } catch (error: any) {
      console.error('Login API error:', error);
      const errorMessage = getErrorMessage(error);
      setError(errorMessage);
      throw new Error(errorMessage);
    }
  };

  const register = async (userData: RegisterData) => {
    try {
      setError(null);
      const response = await apiClient.post('/auth/register', userData);
      
      // Registration successful - show message but don't auto-login
      // User needs to verify email first
      if (response.data.status === 'success') {
        return; // Success, component will handle redirect
      }
    } catch (error: any) {
      const errorMessage = getErrorMessage(error);
      setError(errorMessage);
      throw new Error(errorMessage);
    }
  };

  const logout = () => {
    localStorage.removeItem('token');
    setToken(null);
    setUser(null);
    delete axios.defaults.headers.common['Authorization'];
  };

  const clearError = () => {
    setError(null);
  };

  const value = {
    user,
    token,
    login,
    register,
    logout,
    loading,
    error,
    clearError,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};