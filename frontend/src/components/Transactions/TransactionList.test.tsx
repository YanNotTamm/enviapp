import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import TransactionList from './TransactionList';
import axios from 'axios';

// Mock axios
jest.mock('axios');
const mockedAxios = axios as jest.Mocked<typeof axios>;

const renderTransactionList = () => {
  return render(
    <BrowserRouter>
      <TransactionList />
    </BrowserRouter>
  );
};

const mockTransactions = [
  {
    id: 1,
    user_id: 1,
    layanan_id: 1,
    nama_layanan: 'EnviReg',
    tanggal_mulai: '2024-01-01',
    tanggal_selesai: '2024-12-31',
    status: 'active',
    total_harga: 1000000,
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z'
  },
  {
    id: 2,
    user_id: 1,
    layanan_id: 2,
    nama_layanan: 'EnviDoc',
    tanggal_mulai: '2024-02-01',
    tanggal_selesai: '2024-08-01',
    status: 'completed',
    total_harga: 500000,
    created_at: '2024-02-01T00:00:00Z',
    updated_at: '2024-08-01T00:00:00Z'
  }
];

describe('TransactionList Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('renders loading state initially', () => {
    mockedAxios.get.mockImplementation(() => new Promise(() => {}));
    
    renderTransactionList();
    
    expect(screen.getByText(/memuat transaksi/i)).toBeInTheDocument();
  });

  test('displays transactions after loading', async () => {
    mockedAxios.get.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: mockTransactions
      }
    });

    renderTransactionList();

    await waitFor(() => {
      expect(screen.getByText(/EnviReg/i)).toBeInTheDocument();
      expect(screen.getByText(/EnviDoc/i)).toBeInTheDocument();
    });
  });

  test('displays error message on API failure', async () => {
    mockedAxios.get.mockRejectedValueOnce({
      response: {
        data: {
          status: 'error',
          message: 'Failed to fetch transactions'
        }
      }
    });

    renderTransactionList();

    await waitFor(() => {
      expect(screen.getByText(/gagal memuat transaksi/i)).toBeInTheDocument();
    });
  });

  test('filters transactions by search query', async () => {
    mockedAxios.get.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: mockTransactions
      }
    });

    renderTransactionList();

    await waitFor(() => {
      expect(screen.getByText(/EnviReg/i)).toBeInTheDocument();
    });

    const searchInput = screen.getByPlaceholderText(/cari berdasarkan/i);
    fireEvent.change(searchInput, { target: { value: 'EnviDoc' } });

    await waitFor(() => {
      expect(screen.queryByText(/EnviReg/i)).not.toBeInTheDocument();
      expect(screen.getByText(/EnviDoc/i)).toBeInTheDocument();
    });
  });

  test('filters transactions by status', async () => {
    mockedAxios.get.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: mockTransactions
      }
    });

    renderTransactionList();

    await waitFor(() => {
      expect(screen.getByText(/EnviReg/i)).toBeInTheDocument();
    });

    const statusFilter = screen.getByLabelText(/status/i);
    fireEvent.change(statusFilter, { target: { value: 'completed' } });

    await waitFor(() => {
      expect(screen.queryByText(/EnviReg/i)).not.toBeInTheDocument();
      expect(screen.getByText(/EnviDoc/i)).toBeInTheDocument();
    });
  });

  test('sorts transactions by date', async () => {
    mockedAxios.get.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: mockTransactions
      }
    });

    renderTransactionList();

    await waitFor(() => {
      expect(screen.getByText(/EnviReg/i)).toBeInTheDocument();
    });

    const sortSelect = screen.getByLabelText(/urutkan/i);
    fireEvent.change(sortSelect, { target: { value: 'date-asc' } });

    await waitFor(() => {
      const transactions = screen.getAllByText(/Rp/i);
      expect(transactions.length).toBeGreaterThan(0);
    });
  });

  test('displays transaction statistics', async () => {
    mockedAxios.get.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: mockTransactions
      }
    });

    renderTransactionList();

    await waitFor(() => {
      expect(screen.getByText(/total transaksi/i)).toBeInTheDocument();
      expect(screen.getByText(/total pengeluaran/i)).toBeInTheDocument();
    });
  });

  test('displays empty state when no transactions', async () => {
    mockedAxios.get.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: []
      }
    });

    renderTransactionList();

    await waitFor(() => {
      expect(screen.getByText(/tidak ada transaksi/i)).toBeInTheDocument();
    });
  });

  test('shows link to services when no transactions', async () => {
    mockedAxios.get.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: []
      }
    });

    renderTransactionList();

    await waitFor(() => {
      expect(screen.getByText(/lihat layanan/i)).toBeInTheDocument();
    });
  });

  test('displays filtered results count', async () => {
    mockedAxios.get.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: mockTransactions
      }
    });

    renderTransactionList();

    await waitFor(() => {
      expect(screen.getByText(/hasil filter/i)).toBeInTheDocument();
    });
  });
});
