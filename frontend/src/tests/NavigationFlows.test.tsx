import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { BrowserRouter, MemoryRouter } from 'react-router-dom';
import App from '../App';
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

describe('Navigation Flow Tests', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorage.clear();
  });

  describe('Unauthenticated User Flows', () => {
    test('navigates from login to register', async () => {
      render(<App />);

      // Should be on login page
      expect(screen.getByLabelText(/email/i)).toBeInTheDocument();

      // Click register link
      const registerLink = screen.getByText(/don't have an account/i) || screen.getByText(/register/i);
      fireEvent.click(registerLink);

      await waitFor(() => {
        expect(screen.getByLabelText(/username/i)).toBeInTheDocument();
      });
    });

    test('navigates from register to login', async () => {
      render(
        <MemoryRouter initialEntries={['/register']}>
          <App />
        </MemoryRouter>
      );

      await waitFor(() => {
        expect(screen.getByLabelText(/username/i)).toBeInTheDocument();
      });

      // Click login link
      const loginLink = screen.getByText(/already have an account/i) || screen.getByText(/login/i);
      fireEvent.click(loginLink);

      await waitFor(() => {
        expect(screen.getByLabelText(/email/i)).toBeInTheDocument();
        expect(screen.queryByLabelText(/username/i)).not.toBeInTheDocument();
      });
    });

    test('redirects to login when accessing protected route', async () => {
      render(
        <MemoryRouter initialEntries={['/dashboard']}>
          <App />
        </MemoryRouter>
      );

      await waitFor(() => {
        expect(screen.getByLabelText(/email/i)).toBeInTheDocument();
      });
    });
  });

  describe('Authenticated User Flows', () => {
    beforeEach(() => {
      localStorage.setItem('token', 'fake-jwt-token');
      localStorage.setItem('user', JSON.stringify({
        id: 1,
        email: 'test@example.com',
        role: 'user',
        nama_lengkap: 'Test User'
      }));
    });

    test('navigates from dashboard to services', async () => {
      mockedAxios.get.mockResolvedValue({
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

      render(
        <MemoryRouter initialEntries={['/dashboard']}>
          <App />
        </MemoryRouter>
      );

      await waitFor(() => {
        expect(screen.getByText(/welcome/i)).toBeInTheDocument();
      });

      // Click services link in navigation
      const servicesLink = screen.getByText(/services/i);
      fireEvent.click(servicesLink);

      await waitFor(() => {
        expect(window.location.pathname).toBe('/services');
      });
    });

    test('navigates from dashboard to transactions', async () => {
      mockedAxios.get.mockResolvedValue({
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

      render(
        <MemoryRouter initialEntries={['/dashboard']}>
          <App />
        </MemoryRouter>
      );

      await waitFor(() => {
        expect(screen.getByText(/welcome/i)).toBeInTheDocument();
      });

      // Click transactions link in navigation
      const transactionsLink = screen.getByText(/transactions/i);
      fireEvent.click(transactionsLink);

      await waitFor(() => {
        expect(window.location.pathname).toBe('/transactions');
      });
    });

    test('navigates from dashboard to invoices', async () => {
      mockedAxios.get.mockResolvedValue({
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

      render(
        <MemoryRouter initialEntries={['/dashboard']}>
          <App />
        </MemoryRouter>
      );

      await waitFor(() => {
        expect(screen.getByText(/welcome/i)).toBeInTheDocument();
      });

      // Click invoices link in navigation
      const invoicesLink = screen.getByText(/invoices/i);
      fireEvent.click(invoicesLink);

      await waitFor(() => {
        expect(window.location.pathname).toBe('/invoices');
      });
    });

    test('navigates to transaction detail from transaction list', async () => {
      mockedAxios.get.mockResolvedValue({
        data: {
          status: 'success',
          data: [
            {
              id: 1,
              user_id: 1,
              layanan_id: 1,
              nama_layanan: 'Test Service',
              tanggal_mulai: '2024-01-01',
              tanggal_selesai: '2024-12-31',
              status: 'active',
              total_harga: 1000000,
              created_at: '2024-01-01T00:00:00Z',
              updated_at: '2024-01-01T00:00:00Z'
            }
          ]
        }
      });

      render(
        <MemoryRouter initialEntries={['/transactions']}>
          <App />
        </MemoryRouter>
      );

      await waitFor(() => {
        expect(screen.getByText(/test service/i)).toBeInTheDocument();
      });

      // Click detail button
      const detailButton = screen.getByText(/detail/i);
      fireEvent.click(detailButton);

      await waitFor(() => {
        expect(window.location.pathname).toContain('/transactions/');
      });
    });

    test('navigates to invoice detail from invoice list', async () => {
      mockedAxios.get.mockResolvedValue({
        data: {
          status: 'success',
          data: [
            {
              id: 1,
              user_id: 1,
              transaksi_id: 1,
              nomor_invoice: 'INV-2024-001',
              tanggal_invoice: '2024-01-01',
              tanggal_jatuh_tempo: '2024-01-31',
              total_tagihan: 1000000,
              status_pembayaran: 'pending',
              tanggal_pembayaran: null,
              created_at: '2024-01-01T00:00:00Z',
              updated_at: '2024-01-01T00:00:00Z'
            }
          ]
        }
      });

      render(
        <MemoryRouter initialEntries={['/invoices']}>
          <App />
        </MemoryRouter>
      );

      await waitFor(() => {
        expect(screen.getByText(/INV-2024-001/i)).toBeInTheDocument();
      });

      // Click detail button
      const detailButtons = screen.getAllByText(/detail/i);
      fireEvent.click(detailButtons[0]);

      await waitFor(() => {
        expect(window.location.pathname).toContain('/invoices/');
      });
    });

    test('navigates back to dashboard from any page', async () => {
      mockedAxios.get.mockResolvedValue({
        data: {
          status: 'success',
          data: []
        }
      });

      render(
        <MemoryRouter initialEntries={['/services']}>
          <App />
        </MemoryRouter>
      );

      await waitFor(() => {
        expect(screen.getByText(/layanan kami/i)).toBeInTheDocument();
      });

      // Click dashboard link in navigation
      const dashboardLink = screen.getByText(/dashboard/i);
      fireEvent.click(dashboardLink);

      await waitFor(() => {
        expect(window.location.pathname).toBe('/dashboard');
      });
    });
  });

  describe('Logout Flow', () => {
    beforeEach(() => {
      localStorage.setItem('token', 'fake-jwt-token');
      localStorage.setItem('user', JSON.stringify({
        id: 1,
        email: 'test@example.com',
        role: 'user',
        nama_lengkap: 'Test User'
      }));
    });

    test('logs out and redirects to login', async () => {
      mockedAxios.get.mockResolvedValue({
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

      render(
        <MemoryRouter initialEntries={['/dashboard']}>
          <App />
        </MemoryRouter>
      );

      await waitFor(() => {
        expect(screen.getByText(/welcome/i)).toBeInTheDocument();
      });

      // Click logout button
      const logoutButton = screen.getByText(/logout/i);
      fireEvent.click(logoutButton);

      await waitFor(() => {
        expect(localStorage.getItem('token')).toBeNull();
        expect(screen.getByLabelText(/email/i)).toBeInTheDocument();
      });
    });
  });

  describe('Breadcrumb Navigation', () => {
    beforeEach(() => {
      localStorage.setItem('token', 'fake-jwt-token');
      localStorage.setItem('user', JSON.stringify({
        id: 1,
        email: 'test@example.com',
        role: 'user',
        nama_lengkap: 'Test User'
      }));
    });

    test('maintains navigation state across page transitions', async () => {
      mockedAxios.get.mockResolvedValue({
        data: {
          status: 'success',
          data: []
        }
      });

      render(
        <MemoryRouter initialEntries={['/dashboard']}>
          <App />
        </MemoryRouter>
      );

      await waitFor(() => {
        expect(screen.getByText(/welcome/i)).toBeInTheDocument();
      });

      // Navigate to services
      const servicesLink = screen.getByText(/services/i);
      fireEvent.click(servicesLink);

      await waitFor(() => {
        expect(window.location.pathname).toBe('/services');
      });

      // Navigate back to dashboard
      const dashboardLink = screen.getByText(/dashboard/i);
      fireEvent.click(dashboardLink);

      await waitFor(() => {
        expect(window.location.pathname).toBe('/dashboard');
      });
    });
  });

  describe('Error Page Navigation', () => {
    test('navigates back from unauthorized page', async () => {
      render(
        <MemoryRouter initialEntries={['/unauthorized']}>
          <App />
        </MemoryRouter>
      );

      await waitFor(() => {
        expect(screen.getByText(/akses ditolak/i)).toBeInTheDocument();
      });

      // Click back to dashboard link
      const backLink = screen.getByText(/kembali ke dashboard/i);
      fireEvent.click(backLink);

      await waitFor(() => {
        expect(window.location.pathname).toBe('/');
      });
    });
  });
});
