# Error Handling Utilities

This directory contains utilities for standardized error handling across the frontend application.

## Files

### errorHandler.ts
Provides functions for handling API errors with a standardized format:
- `handleApiError()` - Main error handler that processes errors and shows toasts
- `getErrorMessage()` - Extract user-friendly error messages
- `getValidationErrors()` - Extract validation errors from API responses
- `isAuthError()` - Check if error is authentication-related
- `isForbiddenError()` - Check if error is authorization-related
- `isValidationError()` - Check if error is validation-related
- Toast notification helpers

### axiosConfig.ts
Configures axios with interceptors for:
- Automatic token injection in requests
- Global error handling in responses
- Automatic redirect on authentication errors

## Usage

### Basic API Call with Error Handling

```typescript
import apiClient from '../utils/axiosConfig';
import { handleApiError } from '../utils/errorHandler';

try {
  const response = await apiClient.get('/api/endpoint');
  // Handle success
} catch (error) {
  const apiError = handleApiError(error, {
    showToast: true,  // Show toast notification
    logError: true,   // Log to console
    rethrow: false    // Don't rethrow error
  });
  // Handle error
}
```

### Using Toast Notifications

```typescript
import { useToast } from '../contexts/ToastContext';

const MyComponent = () => {
  const { showSuccess, showError, showInfo } = useToast();
  
  const handleAction = async () => {
    try {
      await apiClient.post('/api/action');
      showSuccess('Action completed successfully!');
    } catch (error) {
      // Error toast is shown automatically by interceptor
    }
  };
};
```

### Error Boundary

The `ErrorBoundary` component catches React errors and displays a fallback UI:

```typescript
import ErrorBoundary from './components/ErrorBoundary';

<ErrorBoundary>
  <YourComponent />
</ErrorBoundary>
```

## Error Response Format

All API errors follow this standardized format:

```typescript
{
  status: 'error',
  message: 'Human-readable error message',
  errors?: {
    field_name: ['Error 1', 'Error 2']
  },
  code?: 'ERROR_CODE'
}
```

## Error Codes

- `VALIDATION_ERROR` - Validation failed
- `UNAUTHORIZED` - Authentication required
- `FORBIDDEN` - Access denied
- `NOT_FOUND` - Resource not found
- `SERVER_ERROR` - Internal server error
- `NETWORK_ERROR` - Network connection issue
