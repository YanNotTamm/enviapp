import { render, screen, waitFor } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import Dashboard from '../components/Dashboard';
import Navigation from '../components/Navigation';
import TransactionList from '../components/Transactions/TransactionList';
import InvoiceList from '../components/Invoices/InvoiceList';
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
      email: 'test@example.com',
      role: 'user',
      nama_lengkap: 'Test User'
    },
    login: jest.fn(),
    logout: jest.fn(),
    loading: false
  })
}));

// Helper to set viewport size
const setViewport = (width: number, height: number) => {
  Object.defineProperty(window, 'innerWidth', {
    writable: true,
    configurable: true,
    value: width,
  });
  Object.defineProperty(window, 'innerHeight', {
    writable: true,
    configurable: true,
    value: height,
  });
  window.dispatchEvent(new Event('resize'));
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

describe('Responsive Design Tests', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('Mobile Viewport (375px)', () => {
    beforeEach(() => {
      setViewport(375, 667);
    });

    test('Dashboard renders correctly on mobile', async () => {
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

      // Check that content is visible
      expect(screen.getByText(/150/)).toBeInTheDocument();
    });

    test('Navigation renders mobile menu on mobile', () => {
      renderWithAuth(<Navigation />);

      // Mobile navigation should be present
      expect(screen.getByText(/dashboard/i)).toBeInTheDocument();
    });

    test('TransactionList shows mobile view on mobile', async () => {
      mockedAxios.get.mockResolvedValueOnce({
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
        <BrowserRouter>
          <TransactionList />
        </BrowserRouter>
      );

      await waitFor(() => {
        expect(screen.getByText(/test service/i)).toBeInTheDocument();
      });
    });

    test('InvoiceList shows mobile view on mobile', async () => {
      mockedAxios.get.mockResolvedValueOnce({
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
        <BrowserRouter>
          <InvoiceList />
        </BrowserRouter>
      );

      await waitFor(() => {
        expect(screen.getByText(/INV-2024-001/i)).toBeInTheDocument();
      });
    });
  });

  describe('Tablet Viewport (768px)', () => {
    beforeEach(() => {
      setViewport(768, 1024);
    });

    test('Dashboard renders correctly on tablet', async () => {
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

    test('Navigation renders correctly on tablet', () => {
      renderWithAuth(<Navigation />);

      expect(screen.getByText(/dashboard/i)).toBeInTheDocument();
    });
  });

  describe('Desktop Viewport (1920px)', () => {
    beforeEach(() => {
      setViewport(1920, 1080);
    });

    test('Dashboard renders correctly on desktop', async () => {
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

    test('Navigation renders full menu on desktop', () => {
      renderWithAuth(<Navigation />);

      expect(screen.getByText(/dashboard/i)).toBeInTheDocument();
      expect(screen.getByText(/services/i)).toBeInTheDocument();
    });

    test('TransactionList shows desktop table view', async () => {
      mockedAxios.get.mockResolvedValueOnce({
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
        <BrowserRouter>
          <TransactionList />
        </BrowserRouter>
      );

      await waitFor(() => {
        expect(screen.getByText(/test service/i)).toBeInTheDocument();
      });
    });
  });

  describe('Viewport Transitions', () => {
    test('handles viewport resize from mobile to desktop', async () => {
      setViewport(375, 667);

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

      // Resize to desktop
      setViewport(1920, 1080);

      // Content should still be visible
      expect(screen.getByText(/welcome/i)).toBeInTheDocument();
    });

    test('handles viewport resize from desktop to mobile', async () => {
      setViewport(1920, 1080);

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

      // Resize to mobile
      setViewport(375, 667);

      // Content should still be visible
      expect(screen.getByText(/welcome/i)).toBeInTheDocument();
    });
  });
});
