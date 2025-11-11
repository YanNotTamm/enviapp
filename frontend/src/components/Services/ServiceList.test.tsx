import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import ServiceList from './ServiceList';
import axios from 'axios';

// Mock axios
jest.mock('axios');
const mockedAxios = axios as jest.Mocked<typeof axios>;

const renderServiceList = () => {
  return render(
    <BrowserRouter>
      <ServiceList />
    </BrowserRouter>
  );
};

const mockServices = [
  {
    id: 1,
    nama_layanan: 'EnviReg',
    deskripsi: 'Layanan registrasi lingkungan',
    harga: 1000000,
    durasi_hari: 365,
    status: 'active',
    fitur: 'Registrasi, Monitoring'
  },
  {
    id: 2,
    nama_layanan: 'EnviDoc',
    deskripsi: 'Layanan dokumentasi lingkungan',
    harga: 500000,
    durasi_hari: 180,
    status: 'active',
    fitur: 'Dokumentasi, Arsip'
  }
];

describe('ServiceList Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('renders loading state initially', () => {
    mockedAxios.get.mockImplementation(() => new Promise(() => {}));
    
    renderServiceList();
    
    expect(screen.getByText(/memuat layanan/i)).toBeInTheDocument();
  });

  test('displays services after loading', async () => {
    mockedAxios.get.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: mockServices
      }
    });

    renderServiceList();

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
          message: 'Failed to fetch services'
        }
      }
    });

    renderServiceList();

    await waitFor(() => {
      expect(screen.getByText(/gagal memuat layanan/i)).toBeInTheDocument();
    });
  });

  test('allows retry after error', async () => {
    mockedAxios.get.mockRejectedValueOnce({
      response: {
        data: {
          status: 'error',
          message: 'Network error'
        }
      }
    });

    renderServiceList();

    await waitFor(() => {
      expect(screen.getByText(/gagal memuat layanan/i)).toBeInTheDocument();
    });

    // Mock successful retry
    mockedAxios.get.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: mockServices
      }
    });

    const retryButton = screen.getByText(/coba lagi/i);
    fireEvent.click(retryButton);

    await waitFor(() => {
      expect(screen.getByText(/EnviReg/i)).toBeInTheDocument();
    });
  });

  test('displays empty state when no services available', async () => {
    mockedAxios.get.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: []
      }
    });

    renderServiceList();

    await waitFor(() => {
      expect(screen.getByText(/tidak ada layanan tersedia/i)).toBeInTheDocument();
    });
  });

  test('displays link to my services', async () => {
    mockedAxios.get.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: mockServices
      }
    });

    renderServiceList();

    await waitFor(() => {
      expect(screen.getByText(/lihat layanan saya/i)).toBeInTheDocument();
    });
  });

  test('renders service cards in grid layout', async () => {
    mockedAxios.get.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: mockServices
      }
    });

    renderServiceList();

    await waitFor(() => {
      const serviceCards = screen.getAllByText(/Rp/i);
      expect(serviceCards.length).toBeGreaterThan(0);
    });
  });
});
