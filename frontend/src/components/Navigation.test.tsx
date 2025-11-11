import { render, screen, fireEvent } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import Navigation from './Navigation';
import { AuthProvider } from '../contexts/AuthContext';

const mockLogout = jest.fn();

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
    logout: mockLogout,
    loading: false
  })
}));

const renderNavigation = (user: any = null) => {
  return render(
    <BrowserRouter>
      <AuthProvider>
        <Navigation />
      </AuthProvider>
    </BrowserRouter>
  );
};

describe('Navigation Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('renders navigation for regular user', () => {
    const user = {
      id: 1,
      email: 'user@example.com',
      role: 'user',
      nama_lengkap: 'Test User'
    };

    renderNavigation(user);
    
    expect(screen.getByText(/dashboard/i)).toBeInTheDocument();
    expect(screen.getByText(/services/i)).toBeInTheDocument();
    expect(screen.getByText(/transactions/i)).toBeInTheDocument();
    expect(screen.getByText(/invoices/i)).toBeInTheDocument();
  });

  test('renders admin menu items for admin user', () => {
    const adminUser = {
      id: 2,
      email: 'admin@example.com',
      role: 'admin_keuangan',
      nama_lengkap: 'Admin User'
    };

    renderNavigation(adminUser);
    
    expect(screen.getByText(/admin/i)).toBeInTheDocument();
  });

  test('renders superadmin menu items for superadmin user', () => {
    const superadminUser = {
      id: 3,
      email: 'superadmin@example.com',
      role: 'superadmin',
      nama_lengkap: 'Super Admin'
    };

    renderNavigation(superadminUser);
    
    expect(screen.getByText(/admin/i)).toBeInTheDocument();
    expect(screen.getByText(/system/i)).toBeInTheDocument();
  });

  test('does not render admin items for regular user', () => {
    const user = {
      id: 1,
      email: 'user@example.com',
      role: 'user',
      nama_lengkap: 'Test User'
    };

    renderNavigation(user);
    
    expect(screen.queryByText(/manage users/i)).not.toBeInTheDocument();
  });

  test('calls logout when logout button is clicked', () => {
    const user = {
      id: 1,
      email: 'user@example.com',
      role: 'user',
      nama_lengkap: 'Test User'
    };

    renderNavigation(user);
    
    const logoutButton = screen.getByText(/logout/i);
    fireEvent.click(logoutButton);

    expect(mockLogout).toHaveBeenCalled();
  });

  test('displays user name in profile section', () => {
    const user = {
      id: 1,
      email: 'user@example.com',
      role: 'user',
      nama_lengkap: 'Test User'
    };

    renderNavigation(user);
    
    expect(screen.getByText(/test user/i)).toBeInTheDocument();
  });

  test('highlights active route', () => {
    const user = {
      id: 1,
      email: 'user@example.com',
      role: 'user',
      nama_lengkap: 'Test User'
    };

    renderNavigation(user);
    
    const dashboardLink = screen.getByText(/dashboard/i).closest('a');
    expect(dashboardLink).toHaveClass('active');
  });
});
