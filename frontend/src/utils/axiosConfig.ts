import axios, { AxiosError, InternalAxiosRequestConfig } from 'axios';
import { handleApiError, isAuthError } from './errorHandler';

// Always use absolute URL to avoid CORS issues
const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8080/api';

console.log('API URL:', API_URL); // Debug log

// Create axios instance
export const apiClient = axios.create({
  baseURL: API_URL,
  timeout: 30000,
  withCredentials: false, // Set to false for simple CORS
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor
apiClient.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    // Add auth token if available
    const token = localStorage.getItem('token');
    if (token && config.headers) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error: AxiosError) => {
    return Promise.reject(error);
  }
);

// Response interceptor
apiClient.interceptors.response.use(
  (response) => {
    // Return successful response
    return response;
  },
  (error: AxiosError) => {
    // Handle authentication errors globally
    if (isAuthError(error)) {
      // Clear token and redirect to login
      localStorage.removeItem('token');
      delete axios.defaults.headers.common['Authorization'];
      
      // Only redirect if not already on login page
      if (window.location.pathname !== '/login') {
        window.location.href = '/login';
      }
    }

    // Handle error with standardized format
    handleApiError(error, {
      showToast: true,
      logError: true,
      rethrow: false,
    });

    return Promise.reject(error);
  }
);

export default apiClient;
