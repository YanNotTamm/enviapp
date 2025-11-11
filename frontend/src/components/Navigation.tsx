import React, { useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import {
  Home,
  FileText,
  Truck,
  CreditCard,
  File,
  BarChart3,
  Users,
  Settings,
  LogOut,
  Menu,
  X,
  ChevronDown,
  User,
  Award
} from 'lucide-react';

interface NavItem {
  name: string;
  path: string;
  icon: React.ReactNode;
  roles?: string[];
}

const Navigation: React.FC = () => {
  const { user, logout } = useAuth();
  const location = useLocation();
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [isProfileDropdownOpen, setIsProfileDropdownOpen] = useState(false);

  // Define navigation items with role-based access
  const navItems: NavItem[] = [
    {
      name: 'Dashboard',
      path: '/dashboard',
      icon: <Home className="h-5 w-5" />,
      roles: ['user', 'admin', 'superadmin']
    },
    {
      name: 'Layanan',
      path: '/services',
      icon: <FileText className="h-5 w-5" />,
      roles: ['user', 'admin', 'superadmin']
    },
    {
      name: 'Transaksi',
      path: '/transactions',
      icon: <BarChart3 className="h-5 w-5" />,
      roles: ['user', 'admin', 'superadmin']
    },
    {
      name: 'Invoice',
      path: '/invoices',
      icon: <CreditCard className="h-5 w-5" />,
      roles: ['user', 'admin', 'superadmin']
    },
    {
      name: 'Dokumen',
      path: '/documents',
      icon: <File className="h-5 w-5" />,
      roles: ['user', 'admin', 'superadmin']
    },
    {
      name: 'Pengangkutan',
      path: '/waste-collection',
      icon: <Truck className="h-5 w-5" />,
      roles: ['user', 'admin', 'superadmin']
    },
    {
      name: 'Manifest',
      path: '/manifests',
      icon: <FileText className="h-5 w-5" />,
      roles: ['user', 'admin', 'superadmin']
    },
    {
      name: 'Kelola Pengguna',
      path: '/admin/users',
      icon: <Users className="h-5 w-5" />,
      roles: ['admin', 'superadmin']
    },
    {
      name: 'Kelola Layanan',
      path: '/admin/services',
      icon: <Settings className="h-5 w-5" />,
      roles: ['superadmin']
    }
  ];

  // Filter navigation items based on user role
  const filteredNavItems = navItems.filter(item => {
    if (!item.roles) return true;
    return user && item.roles.includes(user.role);
  });

  const isActive = (path: string) => {
    return location.pathname === path;
  };

  const handleLogout = () => {
    logout();
    setIsProfileDropdownOpen(false);
    setIsMobileMenuOpen(false);
  };

  const toggleMobileMenu = () => {
    setIsMobileMenuOpen(!isMobileMenuOpen);
  };

  const toggleProfileDropdown = () => {
    setIsProfileDropdownOpen(!isProfileDropdownOpen);
  };

  return (
    <nav className="bg-white shadow-sm border-b sticky top-0 z-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between h-16">
          {/* Logo and Desktop Navigation */}
          <div className="flex">
            {/* Logo */}
            <div className="flex-shrink-0 flex items-center">
              <Link to="/dashboard" className="flex items-center">
                <h1 className="text-2xl font-bold text-green-600">Envipoin</h1>
              </Link>
            </div>

            {/* Desktop Navigation Links */}
            <div className="hidden md:ml-8 md:flex md:space-x-1">
              {filteredNavItems.map((item) => (
                <Link
                  key={item.path}
                  to={item.path}
                  className={`inline-flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                    isActive(item.path)
                      ? 'bg-green-50 text-green-700 border-b-2 border-green-600'
                      : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                  }`}
                >
                  <span className="mr-2">{item.icon}</span>
                  {item.name}
                </Link>
              ))}
            </div>
          </div>

          {/* Right side - User Profile and Mobile Menu Button */}
          <div className="flex items-center">
            {/* User Profile Dropdown - Desktop */}
            <div className="hidden md:block relative">
              <button
                onClick={toggleProfileDropdown}
                className="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <div className="text-right">
                  <div className="text-sm font-medium text-gray-900">{user?.username}</div>
                  <div className="text-xs text-gray-500 capitalize flex items-center justify-end">
                    <Award className="h-3 w-3 mr-1" />
                    {user?.envipoin || 0} poin
                  </div>
                </div>
                <div className="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                  <User className="h-6 w-6 text-green-600" />
                </div>
                <ChevronDown className={`h-4 w-4 text-gray-500 transition-transform ${isProfileDropdownOpen ? 'rotate-180' : ''}`} />
              </button>

              {/* Dropdown Menu */}
              {isProfileDropdownOpen && (
                <div className="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg border border-gray-200 py-2">
                  <div className="px-4 py-3 border-b border-gray-200">
                    <p className="text-sm font-medium text-gray-900">{user?.nama_perusahaan}</p>
                    <p className="text-xs text-gray-500 mt-1">{user?.email}</p>
                    <div className="mt-2 flex items-center text-xs text-gray-600">
                      <span className="px-2 py-1 bg-green-100 text-green-700 rounded-full font-medium capitalize">
                        {user?.role?.replace('_', ' ')}
                      </span>
                    </div>
                  </div>
                  
                  <div className="py-2">
                    <Link
                      to="/profile"
                      className="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                      onClick={() => setIsProfileDropdownOpen(false)}
                    >
                      <User className="h-4 w-4 mr-3" />
                      Profil Saya
                    </Link>
                    <Link
                      to="/settings"
                      className="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                      onClick={() => setIsProfileDropdownOpen(false)}
                    >
                      <Settings className="h-4 w-4 mr-3" />
                      Pengaturan
                    </Link>
                  </div>

                  <div className="border-t border-gray-200 py-2">
                    <button
                      onClick={handleLogout}
                      className="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                    >
                      <LogOut className="h-4 w-4 mr-3" />
                      Keluar
                    </button>
                  </div>
                </div>
              )}
            </div>

            {/* Mobile Menu Button */}
            <button
              onClick={toggleMobileMenu}
              className="md:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-50"
            >
              {isMobileMenuOpen ? (
                <X className="h-6 w-6" />
              ) : (
                <Menu className="h-6 w-6" />
              )}
            </button>
          </div>
        </div>
      </div>

      {/* Mobile Menu */}
      {isMobileMenuOpen && (
        <div className="md:hidden border-t border-gray-200 bg-white">
          {/* User Info */}
          <div className="px-4 py-4 border-b border-gray-200 bg-gray-50">
            <div className="flex items-center space-x-3">
              <div className="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                <User className="h-7 w-7 text-green-600" />
              </div>
              <div className="flex-1">
                <p className="text-sm font-medium text-gray-900">{user?.username}</p>
                <p className="text-xs text-gray-500">{user?.email}</p>
                <div className="mt-1 flex items-center text-xs">
                  <span className="px-2 py-0.5 bg-green-100 text-green-700 rounded-full font-medium capitalize">
                    {user?.role?.replace('_', ' ')}
                  </span>
                  <span className="ml-2 text-gray-600 flex items-center">
                    <Award className="h-3 w-3 mr-1" />
                    {user?.envipoin || 0} poin
                  </span>
                </div>
              </div>
            </div>
          </div>

          {/* Navigation Links */}
          <div className="px-2 py-3 space-y-1">
            {filteredNavItems.map((item) => (
              <Link
                key={item.path}
                to={item.path}
                onClick={() => setIsMobileMenuOpen(false)}
                className={`flex items-center px-3 py-3 text-base font-medium rounded-md transition-colors ${
                  isActive(item.path)
                    ? 'bg-green-50 text-green-700 border-l-4 border-green-600'
                    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                }`}
              >
                <span className="mr-3">{item.icon}</span>
                {item.name}
              </Link>
            ))}
          </div>

          {/* Mobile Profile Actions */}
          <div className="border-t border-gray-200 px-2 py-3 space-y-1">
            <Link
              to="/profile"
              onClick={() => setIsMobileMenuOpen(false)}
              className="flex items-center px-3 py-3 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md"
            >
              <User className="h-5 w-5 mr-3" />
              Profil Saya
            </Link>
            <Link
              to="/settings"
              onClick={() => setIsMobileMenuOpen(false)}
              className="flex items-center px-3 py-3 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md"
            >
              <Settings className="h-5 w-5 mr-3" />
              Pengaturan
            </Link>
            <button
              onClick={handleLogout}
              className="flex items-center w-full px-3 py-3 text-base font-medium text-red-600 hover:bg-red-50 rounded-md"
            >
              <LogOut className="h-5 w-5 mr-3" />
              Keluar
            </button>
          </div>
        </div>
      )}
    </nav>
  );
};

export default Navigation;
