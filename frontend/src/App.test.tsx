import { render, screen, waitFor } from '@testing-library/react';
import App from './App';
import axios from 'axios';

// Mock axios
jest.mock('axios');
const mockedAxios = axios as jest.Mocked<typeof axios>;

// Mock localStorage
const localStorageMock = (() => {
  let store: Record<string, string> = {};
  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => {
      store[key] = value.toString();
    },
    removeItem: (key: string) => {
      delete store[key];
    },
    clear: () => {
      store = {};
    },
  };
})();

Object.defineProperty(window, 'localStorage', {
  value: localStorageMock,
});

describe('App Component - Navigation Flows', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorage.clear();
  });

  test('renders login page by default when not authenticated', () => {
    render(<App />);
    
    expect(screen.getByLabelText(/email/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/password/i)).toBeInTheDocument();
  });

  test('does not show navigation on login page', () => {
    render(<App />);
    
    // Navigation should not be visible on login page
    expect(screen.queryByText(/dashboard/i)).not.toBeInTheDocument();
  });

  test('does not show navigation on register page', async () => {
    window.history.pushState({}, 'Register', '/register');
    render(<App />);
    
    await waitFor(() => {
      expect(screen.getByLabelText(/username/i)).toBeInTheDocument();
    });
    
    // Navigation should not be visible on register page
    expect(screen.queryByRole('navigation')).not.toBeInTheDocument();
  });

  test('redirects to login when accessing protected route without authentication', async () => {
    window.history.pushState({}, 'Dashboard', '/dashboard');
    render(<App />);
    
    await waitFor(() => {
      // Should redirect to login
      expect(screen.getByLabelText(/email/i)).toBeInTheDocument();
    });
  });

  test('shows navigation when authenticated', async () => {
    // Mock authenticated user
    localStorage.setItem('token', 'fake-jwt-token');
    localStorage.setItem('user', JSON.stringify({
      id: 1,
      email: 'test@example.com',
      role: 'user',
      nama_lengkap: 'Test User'
    }));

    mockedAxios.get.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: {
          envipoin: 150,
          active_services: 2,
          pending_invoices: 1,
          recent_transactions: []
        }
      }
    });

    window.history.pushState({}, 'Dashboard', '/dashboard');
    render(<App />);
    
    await waitFor(() => {
      expect(screen.getByText(/dashboard/i)).toBeInTheDocument();
    });
  });

  test('handles unauthorized access gracefully', async () => {
    window.history.pushState({}, 'Unauthorized', '/unauthorized');
    render(<App />);
    
    await waitFor(() => {
      expect(screen.getByText(/akses ditolak/i)).toBeInTheDocument();
    });
  });
});
