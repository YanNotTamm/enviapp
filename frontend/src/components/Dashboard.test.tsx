import { render, screen, waitFor } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import Dashboard from './Dashboard';
import { AuthProvider } from '../contexts/AuthContext';
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
      email: 'user@example.com',
      role: 'user',
      nama_lengkap: 'Test User'
    },
    login: jest.fn(),
    logout: jest.fn(),
    loading: false
  })
}));

const renderDashboard = (user: any) => {
  return render(
    <BrowserRouter>
      <AuthProvider>
        <Dashboard />
      </AuthProvider>
    </BrowserRouter>
  );
};

describe('Dashboard Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('renders loading state initially', () => {
    const user = {
      id: 1,
      email: 'user@example.com',
      role: 'user',
      nama_lengkap: 'Test User'
    };

    mockedAxios.get.mockImplementation(() => new Promise(() => {}));

    renderDashboard(user);
    
    expect(screen.getByText(/loading/i)).toBeInTheDocument();
  });

  test('displays user dashboard data', async () => {
    const user = {
      id: 1,
      email: 'user@example.com',
      role: 'user',
      nama_lengkap: 'Test User'
    };

    mockedAxios.get.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: {
          envipoin: 150,
          active_services: 2,
          pending_invoices: 1,
          recent_transactions: [
            {
              id: 1,
              layanan_nama: 'Test Service',
              tanggal_mulai: '2024-01-01',
              status: 'active'
            }
          ]
        }
      }
    });

    renderDashboard(user);

    await waitFor(() => {
      expect(screen.getByText(/150/)).toBeInTheDocument();
      expect(screen.getByText(/2/)).toBeInTheDocument();
    });
  });

  test('displays error message on API failure', async () => {
    const user = {
      id: 1,
      email: 'user@example.com',
      role: 'user',
      nama_lengkap: 'Test User'
    };

    mockedAxios.get.mockRejectedValueOnce({
      response: {
        data: {
          status: 'error',
          message: 'Failed to fetch dashboard data'
        }
      }
    });

    renderDashboard(user);

    await waitFor(() => {
      expect(screen.getByText(/failed to fetch/i)).toBeInTheDocument();
    });
  });

  test('displays welcome message with user name', async () => {
    const user = {
      id: 1,
      email: 'user@example.com',
      role: 'user',
      nama_lengkap: 'Test User'
    };

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

    renderDashboard(user);

    await waitFor(() => {
      expect(screen.getByText(/welcome.*test user/i)).toBeInTheDocument();
    });
  });

  test('displays recent transactions list', async () => {
    const user = {
      id: 1,
      email: 'user@example.com',
      role: 'user',
      nama_lengkap: 'Test User'
    };

    mockedAxios.get.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: {
          envipoin: 150,
          active_services: 2,
          pending_invoices: 1,
          recent_transactions: [
            {
              id: 1,
              layanan_nama: 'Test Service 1',
              tanggal_mulai: '2024-01-01',
              status: 'active'
            },
            {
              id: 2,
              layanan_nama: 'Test Service 2',
              tanggal_mulai: '2024-01-15',
              status: 'completed'
            }
          ]
        }
      }
    });

    renderDashboard(user);

    await waitFor(() => {
      expect(screen.getByText(/test service 1/i)).toBeInTheDocument();
      expect(screen.getByText(/test service 2/i)).toBeInTheDocument();
    });
  });

  test('displays empty state when no transactions', async () => {
    const user = {
      id: 1,
      email: 'user@example.com',
      role: 'user',
      nama_lengkap: 'Test User'
    };

    mockedAxios.get.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: {
          envipoin: 0,
          active_services: 0,
          pending_invoices: 0,
          recent_transactions: []
        }
      }
    });

    renderDashboard(user);

    await waitFor(() => {
      expect(screen.getByText(/no recent transactions/i)).toBeInTheDocument();
    });
  });
});
