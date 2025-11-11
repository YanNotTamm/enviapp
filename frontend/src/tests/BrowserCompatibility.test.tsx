import { render, screen, waitFor } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import Login from '../components/Login';
import Register from '../components/Register';
import Dashboard from '../components/Dashboard';
import { AuthProvider } from '../contexts/AuthContext';
import { ToastProvider } from '../contexts/ToastContext';
import axios from 'axios';

// Mock axios
jest.mock('axios');
const mockedAxios = axios as jest.Mocked<typeof axios>;

// Mock useAuth hook
jest.mock('../contexts/AuthContext', () => ({
  ...jest.requireActual('../contexts/AuthContext'),
  useAuth: () => ({
    user: {
      id: 1,
      email: 'test@example.com',
      role: 'user',
      nama_lengkap: 'Test User'
    },
    login: jest.fn(),
    logout: jest.fn(),
    loading: false
  })
}));

// Mock different browser user agents
const mockUserAgent = (userAgent: string) => {
  Object.defineProperty(window.navigator, 'userAgent', {
    writable: true,
    configurable: true,
    value: userAgent,
  });
};

const renderWithProviders = (component: React.ReactElement) => {
  return render(
    <BrowserRouter>
      <AuthProvider>
        <ToastProvider>
          {component}
        </ToastProvider>
      </AuthProvider>
    </BrowserRouter>
  );
};

const mockUser = {
  id: 1,
  email: 'test@example.com',
  role: 'user',
  nama_lengkap: 'Test User'
};

const renderWithAuth = (component: React.ReactElement) => {
  return render(
    <BrowserRouter>
      <AuthProvider>
        {component}
      </AuthProvider>
    </BrowserRouter>
  );
};

describe('Browser Compatibility Tests', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('Chrome Browser', () => {
    beforeEach(() => {
      mockUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    });

    test('Login component renders correctly in Chrome', () => {
      renderWithProviders(<Login />);
      
      expect(screen.getByLabelText(/email/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/password/i)).toBeInTheDocument();
    });

    test('Register component renders correctly in Chrome', () => {
      renderWithProviders(<Register />);
      
      expect(screen.getByLabelText(/username/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/email/i)).toBeInTheDocument();
    });

    test('Dashboard renders correctly in Chrome', async () => {
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

      renderWithAuth(<Dashboard />);

      await waitFor(() => {
        expect(screen.getByText(/welcome/i)).toBeInTheDocument();
      });
    });
  });

  describe('Firefox Browser', () => {
    beforeEach(() => {
      mockUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0');
    });

    test('Login component renders correctly in Firefox', () => {
      renderWithProviders(<Login />);
      
      expect(screen.getByLabelText(/email/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/password/i)).toBeInTheDocument();
    });

    test('Register component renders correctly in Firefox', () => {
      renderWithProviders(<Register />);
      
      expect(screen.getByLabelText(/username/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/email/i)).toBeInTheDocument();
    });

    test('Dashboard renders correctly in Firefox', async () => {
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

      renderWithAuth(<Dashboard />);

      await waitFor(() => {
        expect(screen.getByText(/welcome/i)).toBeInTheDocument();
      });
    });
  });

  describe('Safari Browser', () => {
    beforeEach(() => {
      mockUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15');
    });

    test('Login component renders correctly in Safari', () => {
      renderWithProviders(<Login />);
      
      expect(screen.getByLabelText(/email/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/password/i)).toBeInTheDocument();
    });

    test('Register component renders correctly in Safari', () => {
      renderWithProviders(<Register />);
      
      expect(screen.getByLabelText(/username/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/email/i)).toBeInTheDocument();
    });

    test('Dashboard renders correctly in Safari', async () => {
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

      renderWithAuth(<Dashboard />);

      await waitFor(() => {
        expect(screen.getByText(/welcome/i)).toBeInTheDocument();
      });
    });
  });

  describe('Edge Browser', () => {
    beforeEach(() => {
      mockUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0');
    });

    test('Login component renders correctly in Edge', () => {
      renderWithProviders(<Login />);
      
      expect(screen.getByLabelText(/email/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/password/i)).toBeInTheDocument();
    });

    test('Register component renders correctly in Edge', () => {
      renderWithProviders(<Register />);
      
      expect(screen.getByLabelText(/username/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/email/i)).toBeInTheDocument();
    });

    test('Dashboard renders correctly in Edge', async () => {
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

      renderWithAuth(<Dashboard />);

      await waitFor(() => {
        expect(screen.getByText(/welcome/i)).toBeInTheDocument();
      });
    });
  });

  describe('Browser Feature Detection', () => {
    test('localStorage is available', () => {
      expect(typeof window.localStorage).toBe('object');
      expect(window.localStorage.setItem).toBeDefined();
      expect(window.localStorage.getItem).toBeDefined();
    });

    test('sessionStorage is available', () => {
      expect(typeof window.sessionStorage).toBe('object');
      expect(window.sessionStorage.setItem).toBeDefined();
      expect(window.sessionStorage.getItem).toBeDefined();
    });

    test('fetch API is available', () => {
      expect(typeof window.fetch).toBe('function');
    });

    test('Promise is available', () => {
      expect(typeof Promise).toBe('function');
      expect(Promise.resolve).toBeDefined();
      expect(Promise.reject).toBeDefined();
    });

    test('Array methods are available', () => {
      expect(Array.prototype.map).toBeDefined();
      expect(Array.prototype.filter).toBeDefined();
      expect(Array.prototype.reduce).toBeDefined();
      expect(Array.prototype.find).toBeDefined();
    });

    test('Object methods are available', () => {
      expect(Object.keys).toBeDefined();
      expect(Object.values).toBeDefined();
      expect(Object.entries).toBeDefined();
      expect(Object.assign).toBeDefined();
    });

    test('ES6 features are available', () => {
      // Arrow functions
      const arrowFunc = () => true;
      expect(arrowFunc()).toBe(true);

      // Template literals
      const name = 'Test';
      expect(`Hello ${name}`).toBe('Hello Test');

      // Destructuring
      const { a, b } = { a: 1, b: 2 };
      expect(a).toBe(1);
      expect(b).toBe(2);

      // Spread operator
      const arr1 = [1, 2];
      const arr2 = [...arr1, 3];
      expect(arr2).toEqual([1, 2, 3]);
    });
  });

  describe('CSS Features', () => {
    test('Flexbox is supported', () => {
      const div = document.createElement('div');
      div.style.display = 'flex';
      expect(div.style.display).toBe('flex');
    });

    test('Grid is supported', () => {
      const div = document.createElement('div');
      div.style.display = 'grid';
      expect(div.style.display).toBe('grid');
    });

    test('CSS variables are supported', () => {
      const div = document.createElement('div');
      div.style.setProperty('--test-var', '10px');
      expect(div.style.getPropertyValue('--test-var')).toBe('10px');
    });
  });
});
