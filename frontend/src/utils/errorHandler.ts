import axios, { AxiosError } from 'axios';

export interface ApiError {
  status: string;
  message: string;
  errors?: Record<string, string[]>;
  code?: string;
}

export interface ErrorHandlerOptions {
  showToast?: boolean;
  logError?: boolean;
  rethrow?: boolean;
}

/**
 * Handle API errors with standardized format
 */
export const handleApiError = (
  error: unknown,
  options: ErrorHandlerOptions = {}
): ApiError => {
  const { showToast = true, logError = true, rethrow = false } = options;

  let apiError: ApiError = {
    status: 'error',
    message: 'An unexpected error occurred',
  };

  if (axios.isAxiosError(error)) {
    const axiosError = error as AxiosError<ApiError>;

    if (axiosError.response) {
      // Server responded with error
      apiError = {
        status: axiosError.response.data?.status || 'error',
        message: axiosError.response.data?.message || 'Server error occurred',
        errors: axiosError.response.data?.errors,
        code: axiosError.response.data?.code,
      };
    } else if (axiosError.request) {
      // Request made but no response
      apiError = {
        status: 'error',
        message: 'Network error. Please check your connection.',
        code: 'NETWORK_ERROR',
      };
    } else {
      // Error in request setup
      apiError = {
        status: 'error',
        message: axiosError.message || 'Request failed',
        code: 'REQUEST_ERROR',
      };
    }
  } else if (error instanceof Error) {
    apiError = {
      status: 'error',
      message: error.message,
    };
  }

  // Log error if enabled
  if (logError) {
    console.error('API Error:', apiError);
  }

  // Show toast notification if enabled
  if (showToast) {
    showErrorToast(apiError.message);
  }

  // Rethrow if needed
  if (rethrow) {
    throw error;
  }

  return apiError;
};

/**
 * Get user-friendly error message
 */
export const getErrorMessage = (error: unknown): string => {
  const apiError = handleApiError(error, { showToast: false, logError: false });
  return apiError.message;
};

/**
 * Get validation errors from API response
 */
export const getValidationErrors = (error: unknown): Record<string, string[]> | null => {
  if (axios.isAxiosError(error)) {
    const axiosError = error as AxiosError<ApiError>;
    return axiosError.response?.data?.errors || null;
  }
  return null;
};

/**
 * Check if error is authentication error
 */
export const isAuthError = (error: unknown): boolean => {
  if (axios.isAxiosError(error)) {
    const axiosError = error as AxiosError<ApiError>;
    return (
      axiosError.response?.status === 401 ||
      axiosError.response?.data?.code === 'UNAUTHORIZED'
    );
  }
  return false;
};

/**
 * Check if error is authorization error (forbidden)
 */
export const isForbiddenError = (error: unknown): boolean => {
  if (axios.isAxiosError(error)) {
    const axiosError = error as AxiosError<ApiError>;
    return (
      axiosError.response?.status === 403 ||
      axiosError.response?.data?.code === 'FORBIDDEN'
    );
  }
  return false;
};

/**
 * Check if error is validation error
 */
export const isValidationError = (error: unknown): boolean => {
  if (axios.isAxiosError(error)) {
    const axiosError = error as AxiosError<ApiError>;
    return (
      axiosError.response?.status === 400 &&
      axiosError.response?.data?.code === 'VALIDATION_ERROR'
    );
  }
  return false;
};

/**
 * Show error toast notification
 * This is a placeholder - will be replaced with actual toast implementation
 */
let toastCallback: ((message: string, type: 'success' | 'error' | 'info') => void) | null = null;

export const setToastCallback = (
  callback: (message: string, type: 'success' | 'error' | 'info') => void
) => {
  toastCallback = callback;
};

export const showErrorToast = (message: string) => {
  if (toastCallback) {
    toastCallback(message, 'error');
  } else {
    // Fallback to console if toast not configured
    console.error('Toast:', message);
  }
};

export const showSuccessToast = (message: string) => {
  if (toastCallback) {
    toastCallback(message, 'success');
  } else {
    console.log('Toast:', message);
  }
};

export const showInfoToast = (message: string) => {
  if (toastCallback) {
    toastCallback(message, 'info');
  } else {
    console.info('Toast:', message);
  }
};
