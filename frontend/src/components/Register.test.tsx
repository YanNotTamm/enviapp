import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import Register from './Register';
import { ToastProvider } from '../contexts/ToastContext';
import axios from 'axios';

// Mock axios
jest.mock('axios');
const mockedAxios = axios as jest.Mocked<typeof axios>;

// Mock useNavigate
const mockNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useNavigate: () => mockNavigate,
}));

const renderRegister = () => {
  return render(
    <BrowserRouter>
      <ToastProvider>
        <Register />
      </ToastProvider>
    </BrowserRouter>
  );
};

describe('Register Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('renders registration form', () => {
    renderRegister();
    
    expect(screen.getByLabelText(/username/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/email/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/^password$/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/confirm password/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /register/i })).toBeInTheDocument();
  });

  test('shows validation errors for empty fields', async () => {
    renderRegister();
    
    const submitButton = screen.getByRole('button', { name: /register/i });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(screen.getByText(/username is required/i)).toBeInTheDocument();
    });
  });

  test('shows error for weak password', async () => {
    renderRegister();
    
    const passwordInput = screen.getByLabelText(/^password$/i);
    fireEvent.change(passwordInput, { target: { value: 'weak' } });
    fireEvent.blur(passwordInput);

    await waitFor(() => {
      expect(screen.getByText(/password must be at least 8 characters/i)).toBeInTheDocument();
    });
  });

  test('shows error when passwords do not match', async () => {
    renderRegister();
    
    const passwordInput = screen.getByLabelText(/^password$/i);
    const confirmPasswordInput = screen.getByLabelText(/confirm password/i);
    
    fireEvent.change(passwordInput, { target: { value: 'Test@1234' } });
    fireEvent.change(confirmPasswordInput, { target: { value: 'Different@1234' } });
    fireEvent.blur(confirmPasswordInput);

    await waitFor(() => {
      expect(screen.getByText(/passwords must match/i)).toBeInTheDocument();
    });
  });

  test('submits form with valid data', async () => {
    mockedAxios.post.mockResolvedValueOnce({
      data: {
        status: 'success',
        message: 'Registration successful'
      }
    });

    renderRegister();
    
    fireEvent.change(screen.getByLabelText(/username/i), { target: { value: 'testuser' } });
    fireEvent.change(screen.getByLabelText(/email/i), { target: { value: 'test@example.com' } });
    fireEvent.change(screen.getByLabelText(/^password$/i), { target: { value: 'Test@1234' } });
    fireEvent.change(screen.getByLabelText(/confirm password/i), { target: { value: 'Test@1234' } });
    fireEvent.change(screen.getByLabelText(/nama lengkap/i), { target: { value: 'Test User' } });
    fireEvent.change(screen.getByLabelText(/nama perusahaan/i), { target: { value: 'Test Company' } });
    fireEvent.change(screen.getByLabelText(/alamat perusahaan/i), { target: { value: '123 Test St' } });
    fireEvent.change(screen.getByLabelText(/telepon/i), { target: { value: '1234567890' } });

    const submitButton = screen.getByRole('button', { name: /register/i });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(mockedAxios.post).toHaveBeenCalledWith(
        '/api/auth/register',
        expect.objectContaining({
          username: 'testuser',
          email: 'test@example.com'
        })
      );
    });
  });

  test('displays error message on registration failure', async () => {
    mockedAxios.post.mockRejectedValueOnce({
      response: {
        data: {
          status: 'error',
          message: 'Email already exists'
        }
      }
    });

    renderRegister();
    
    fireEvent.change(screen.getByLabelText(/username/i), { target: { value: 'testuser' } });
    fireEvent.change(screen.getByLabelText(/email/i), { target: { value: 'test@example.com' } });
    fireEvent.change(screen.getByLabelText(/^password$/i), { target: { value: 'Test@1234' } });
    fireEvent.change(screen.getByLabelText(/confirm password/i), { target: { value: 'Test@1234' } });

    const submitButton = screen.getByRole('button', { name: /register/i });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(screen.getByText(/email already exists/i)).toBeInTheDocument();
    });
  });
});
