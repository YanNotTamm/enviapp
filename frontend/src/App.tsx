import React from 'react';
import { BrowserRouter as Router, Routes, Route, useLocation } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import { ToastProvider } from './contexts/ToastContext';
import ErrorBoundary from './components/ErrorBoundary';
import ProtectedRoute from './components/ProtectedRoute';
import Navigation from './components/Navigation';
import Login from './components/Login';
import Register from './components/Register';
import EmailVerification from './components/EmailVerification';
import Dashboard from './components/Dashboard';
import ServiceList from './components/Services/ServiceList';
import ServiceSubscription from './components/Services/ServiceSubscription';
import MyServices from './components/Services/MyServices';
import TransactionList from './components/Transactions/TransactionList';
import TransactionDetail from './components/Transactions/TransactionDetail';
import InvoiceList from './components/Invoices/InvoiceList';
import InvoiceDetail from './components/Invoices/InvoiceDetail';
import './App.css';

// Layout wrapper to conditionally show Navigation
const Layout: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const location = useLocation();
  const publicRoutes = ['/login', '/register', '/verify-email'];
  const showNavigation = !publicRoutes.includes(location.pathname) && !location.pathname.startsWith('/verify-email/');

  return (
    <>
      {showNavigation && <Navigation />}
      {children}
    </>
  );
};

function App() {
  return (
    <ErrorBoundary>
      <ToastProvider>
        <AuthProvider>
          <Router>
            <Layout>
              <div className="App">
                <Routes>
              <Route path="/login" element={<Login />} />
              <Route path="/register" element={<Register />} />
              <Route path="/verify-email/:token" element={<EmailVerification />} />
              <Route path="/" element={
                <ProtectedRoute>
                  <Dashboard />
                </ProtectedRoute>
              } />
              <Route path="/dashboard" element={
                <ProtectedRoute>
                  <Dashboard />
                </ProtectedRoute>
              } />
              <Route path="/services" element={
                <ProtectedRoute>
                  <ServiceList />
                </ProtectedRoute>
              } />
              <Route path="/services/subscribe/:id" element={
                <ProtectedRoute>
                  <ServiceSubscription />
                </ProtectedRoute>
              } />
              <Route path="/services/my-services" element={
                <ProtectedRoute>
                  <MyServices />
                </ProtectedRoute>
              } />
              <Route path="/transactions" element={
                <ProtectedRoute>
                  <TransactionList />
                </ProtectedRoute>
              } />
              <Route path="/transactions/:id" element={
                <ProtectedRoute>
                  <TransactionDetail />
                </ProtectedRoute>
              } />
              <Route path="/invoices" element={
                <ProtectedRoute>
                  <InvoiceList />
                </ProtectedRoute>
              } />
              <Route path="/invoices/:id" element={
                <ProtectedRoute>
                  <InvoiceDetail />
                </ProtectedRoute>
              } />
              <Route path="/unauthorized" element={
                <div className="min-h-screen flex items-center justify-center bg-gray-50">
                  <div className="text-center">
                    <h1 className="text-3xl font-bold text-gray-900 mb-4">Akses Ditolak</h1>
                    <p className="text-gray-600 mb-4">Anda tidak memiliki izin untuk mengakses halaman ini.</p>
                    <a href="/" className="text-green-600 hover:text-green-700 font-medium">
                      Kembali ke Dashboard
                    </a>
                  </div>
                </div>
              } />
                </Routes>
              </div>
            </Layout>
          </Router>
        </AuthProvider>
      </ToastProvider>
    </ErrorBoundary>
  );
}

export default App;
