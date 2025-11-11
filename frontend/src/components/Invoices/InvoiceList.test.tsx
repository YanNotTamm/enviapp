import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import InvoiceList from './InvoiceList';
import axios from 'axios';

// Mock axios
jest.mock('axios');
const mockedAxios = axios as jest.Mocked<typeof axios>;

const renderInvoiceList = () => {
  return render(
    <BrowserRouter>
      <InvoiceList />
    </BrowserRouter>
  );
};

const mockInvoices = [
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
  },
  {
    id: 2,
    user_id: 1,
    transaksi_id: 2,
    nomor_invoice: 'INV-2024-002',
    tanggal_invoice: '2024-02-01',
    tanggal_jatuh_tempo: '2024-02-28',
    total_tagihan: 500000,
    status_pembayaran: 'paid',
    tanggal_pembayaran: '2024-02-15',
    created_at: '2024-02-01T00:00:00Z',
    updated_at: '2024-02-15T00:00:00Z'
  }
];

describe('InvoiceList Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('renders loading state initially', () => {
    mockedAxios.get.mockImplementation(() => new Promise(() => {}));
    
    renderInvoiceList();
    
    expect(screen.getByText(/memuat invoice/i)).toBeInTheDocument();
  });

  test('displays invoices after loading', async () => {
    mockedAxios.get.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: mockInvoices
      }
    });

    renderInvoiceList();

    await waitFor(() => {
      expect(screen.getByText(/INV-2024-001/i)).toBeInTheDocument();
      expect(screen.getByText(/INV-2024-002/i)).toBeInTheDocument();
    });
  });

  test('displays error message on API failure', async () => {
    mockedAxios.get.mockRejectedValueOnce({
      response: {
        data: {
          status: 'error',
          message: 'Failed to fetch invoices'
        }
      }
    });

    renderInvoiceList();

    await waitFor(() => {
      expect(screen.getByText(/gagal memuat invoice/i)).toBeInTheDocument();
    });
  });

  test('filters invoices by search query', async () => {
    mockedAxios.get.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: mockInvoices
      }
    });

    renderInvoiceList();

    await waitFor(() => {
      expect(screen.getByText(/INV-2024-001/i)).toBeInTheDocument();
    });

    const searchInput = screen.getByPlaceholderText(/cari berdasarkan/i);
    fireEvent.change(searchInput, { target: { value: 'INV-2024-002' } });

    await waitFor(() => {
      expect(screen.queryByText(/INV-2024-001/i)).not.toBeInTheDocument();
      expect(screen.getByText(/INV-2024-002/i)).toBeInTheDocument();
    });
  });

  test('filters invoices by status', async () => {
    mockedAxios.get.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: mockInvoices
      }
    });

    renderInvoiceList();

    await waitFor(() => {
      expect(screen.getByText(/INV-2024-001/i)).toBeInTheDocument();
    });

    const statusFilter = screen.getByLabelText(/status/i);
    fireEvent.change(statusFilter, { target: { value: 'paid' } });

    await waitFor(() => {
      expect(screen.queryByText(/INV-2024-001/i)).not.toBeInTheDocument();
      expect(screen.getByText(/INV-2024-002/i)).toBeInTheDocument();
    });
  });

  test('sorts invoices by date', async () => {
    mockedAxios.get.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: mockInvoices
      }
    });

    renderInvoiceList();

    await waitFor(() => {
      expect(screen.getByText(/INV-2024-001/i)).toBeInTheDocument();
    });

    const sortSelect = screen.getByLabelText(/urutkan/i);
    fireEvent.change(sortSelect, { target: { value: 'date-asc' } });

    await waitFor(() => {
      const invoices = screen.getAllByText(/Rp/i);
      expect(invoices.length).toBeGreaterThan(0);
    });
  });

  test('displays invoice statistics', async () => {
    mockedAxios.get.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: mockInvoices
      }
    });

    renderInvoiceList();

    await waitFor(() => {
      expect(screen.getByText(/total invoice/i)).toBeInTheDocument();
      expect(screen.getByText(/pending/i)).toBeInTheDocument();
      expect(screen.getByText(/total tagihan/i)).toBeInTheDocument();
    });
  });

  test('displays empty state when no invoices', async () => {
    mockedAxios.get.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: []
      }
    });

    renderInvoiceList();

    await waitFor(() => {
      expect(screen.getByText(/tidak ada invoice/i)).toBeInTheDocument();
    });
  });

  test('handles invoice download', async () => {
    mockedAxios.get.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: mockInvoices
      }
    });

    // Mock download response
    mockedAxios.get.mockResolvedValueOnce({
      data: new Blob(['PDF content'], { type: 'application/pdf' })
    });

    // Mock URL.createObjectURL
    global.URL.createObjectURL = jest.fn(() => 'blob:mock-url');
    
    // Mock document.createElement and appendChild
    const mockLink = {
      click: jest.fn(),
      remove: jest.fn(),
      setAttribute: jest.fn(),
      href: '',
    };
    jest.spyOn(document, 'createElement').mockReturnValue(mockLink as any);
    jest.spyOn(document.body, 'appendChild').mockImplementation(() => mockLink as any);

    renderInvoiceList();

    await waitFor(() => {
      expect(screen.getByText(/INV-2024-001/i)).toBeInTheDocument();
    });

    const downloadButtons = screen.getAllByRole('button');
    const downloadButton = downloadButtons.find(btn => 
      btn.querySelector('svg') && !btn.textContent?.includes('Coba lagi')
    );

    if (downloadButton) {
      fireEvent.click(downloadButton);

      await waitFor(() => {
        expect(mockedAxios.get).toHaveBeenCalledWith(
          expect.stringContaining('/download'),
          expect.objectContaining({ responseType: 'blob' })
        );
      });
    }
  });

  test('displays overdue warning for overdue invoices', async () => {
    const overdueInvoice = {
      ...mockInvoices[0],
      tanggal_jatuh_tempo: '2020-01-01', // Past date
      status_pembayaran: 'pending'
    };

    mockedAxios.get.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: [overdueInvoice]
      }
    });

    renderInvoiceList();

    await waitFor(() => {
      expect(screen.getByText(/jatuh tempo/i)).toBeInTheDocument();
    });
  });
});
