import React, { useState, useEffect } from 'react';
import { useAuth } from '../contexts/AuthContext';
import { useToast } from '../contexts/ToastContext';
import { Link } from 'react-router-dom';
import apiClient from '../utils/axiosConfig';
import { handleApiError } from '../utils/errorHandler';
import { 
  BarChart3, 
  FileText, 
  Truck, 
  CreditCard, 
  Award,
  AlertCircle,
  Plus,
  Eye,
  ArrowRight
} from 'lucide-react';

interface Transaksi {
  id: number;
  layanan: string;
  tanggal: string;
  status: string;
  total: number;
}

interface Service {
  id: number;
  nama_layanan: string;
  status: string;
}

interface DashboardStats {
  envipoin: number;
  active_services: number;
  total_transactions: number;
  pending_invoices: number;
  recent_transactions: Transaksi[];
  active_services_list: Service[];
}

const Dashboard: React.FC = () => {
  const { user } = useAuth();
  const { showError } = useToast();
  const [dashboardData, setDashboardData] = useState<DashboardStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const response = await apiClient.get('/dashboard/user');
      
      if (response.data.status === 'success') {
        setDashboardData(response.data.data);
      } else {
        throw new Error('Failed to fetch dashboard data');
      }
    } catch (error: any) {
      console.error('Failed to fetch dashboard data:', error);
      const apiError = handleApiError(error, { showToast: false, logError: true });
      setError(apiError.message);
      
      // Fallback to mock data for development
      setDashboardData({
        envipoin: user?.envipoin || 0,
        active_services: 2,
        total_transactions: 12,
        pending_invoices: 3,
        recent_transactions: [
          { id: 1, layanan: 'EnviReg - Registrasi Lingkungan', tanggal: '2025-11-01', status: 'selesai', total: 1500000 },
          { id: 2, layanan: 'EnviDoc - Dokumentasi', tanggal: '2025-10-28', status: 'diproses', total: 2500000 },
          { id: 3, layanan: 'EnviWaste - Pengelolaan Limbah', tanggal: '2025-10-25', status: 'menunggu', total: 3000000 }
        ],
        active_services_list: [
          { id: 1, nama_layanan: 'EnviReg', status: 'active' },
          { id: 2, nama_layanan: 'EnviDoc', status: 'active' }
        ]
      });
    } finally {
      setLoading(false);
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'selesai': return 'bg-green-100 text-green-800';
      case 'diproses': return 'bg-yellow-100 text-yellow-800';
      case 'menunggu': return 'bg-blue-100 text-blue-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-screen bg-gray-50">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600 mx-auto mb-4"></div>
          <p className="text-gray-600">Memuat data dashboard...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Error Alert */}
        {error && (
          <div className="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-start">
            <AlertCircle className="h-5 w-5 text-red-600 mt-0.5 mr-3 flex-shrink-0" />
            <div className="flex-1">
              <h3 className="text-sm font-medium text-red-800">Gagal memuat data</h3>
              <p className="text-sm text-red-700 mt-1">{error}</p>
              <button
                onClick={fetchDashboardData}
                className="mt-2 text-sm font-medium text-red-600 hover:text-red-500"
              >
                Coba lagi
              </button>
            </div>
          </div>
        )}

        {/* Welcome Section */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">
            Selamat Datang, {user?.nama_perusahaan}!
          </h1>
          <p className="text-gray-600">
            Kelola layanan lingkungan Anda dengan mudah dan efisien
          </p>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          {/* Envipoin Card */}
          <div className="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-purple-100 text-sm font-medium mb-1">Envipoin Anda</p>
                <p className="text-3xl font-bold">{(dashboardData?.envipoin || 0).toLocaleString()}</p>
                <p className="text-purple-100 text-xs mt-2">Poin reward Anda</p>
              </div>
              <div className="p-3 bg-white bg-opacity-20 rounded-lg">
                <Award className="h-8 w-8" />
              </div>
            </div>
          </div>

          {/* Active Services Card */}
          <div className="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600 mb-1">Layanan Aktif</p>
                <p className="text-3xl font-bold text-gray-900">{dashboardData?.active_services || 0}</p>
                <p className="text-xs text-gray-500 mt-2">Layanan berjalan</p>
              </div>
              <div className="p-3 bg-green-100 rounded-lg">
                <FileText className="h-8 w-8 text-green-600" />
              </div>
            </div>
          </div>

          {/* Total Transactions Card */}
          <div className="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600 mb-1">Total Transaksi</p>
                <p className="text-3xl font-bold text-gray-900">{dashboardData?.total_transactions || 0}</p>
                <p className="text-xs text-gray-500 mt-2">Semua transaksi</p>
              </div>
              <div className="p-3 bg-blue-100 rounded-lg">
                <BarChart3 className="h-8 w-8 text-blue-600" />
              </div>
            </div>
          </div>

          {/* Pending Invoices Card */}
          <div className="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600 mb-1">Invoice Tertunda</p>
                <p className="text-3xl font-bold text-gray-900">{dashboardData?.pending_invoices || 0}</p>
                <p className="text-xs text-gray-500 mt-2">Menunggu pembayaran</p>
              </div>
              <div className="p-3 bg-yellow-100 rounded-lg">
                <CreditCard className="h-8 w-8 text-yellow-600" />
              </div>
            </div>
          </div>
        </div>

        {/* Active Services Section */}
        {dashboardData && dashboardData.active_services_list && dashboardData.active_services_list.length > 0 && (
          <div className="bg-white rounded-lg shadow p-6 mb-8">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-semibold text-gray-900">Layanan Aktif Anda</h3>
              <Link to="/services" className="text-sm text-green-600 hover:text-green-700 font-medium flex items-center">
                Lihat Semua
                <ArrowRight className="h-4 w-4 ml-1" />
              </Link>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {dashboardData.active_services_list.map((service) => (
                <div key={service.id} className="border border-gray-200 rounded-lg p-4 hover:border-green-300 transition-colors">
                  <div className="flex items-center justify-between">
                    <div>
                      <h4 className="font-medium text-gray-900">{service.nama_layanan}</h4>
                      <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 mt-2">
                        {service.status}
                      </span>
                    </div>
                    <Eye className="h-5 w-5 text-gray-400" />
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Recent Transactions and Quick Actions */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
          {/* Recent Transactions */}
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-semibold text-gray-900">Transaksi Terbaru</h3>
              <Link to="/transactions" className="text-sm text-green-600 hover:text-green-700 font-medium flex items-center">
                Lihat Semua
                <ArrowRight className="h-4 w-4 ml-1" />
              </Link>
            </div>
            {dashboardData && dashboardData.recent_transactions && dashboardData.recent_transactions.length > 0 ? (
              <div className="space-y-3">
                {dashboardData.recent_transactions.map((transaksi) => (
                  <div key={transaksi.id} className="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div className="flex-1">
                      <p className="font-medium text-gray-900 text-sm">{transaksi.layanan}</p>
                      <p className="text-xs text-gray-500 mt-1">{transaksi.tanggal}</p>
                    </div>
                    <div className="text-right ml-4">
                      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(transaksi.status)}`}>
                        {transaksi.status}
                      </span>
                      <p className="text-sm font-semibold text-gray-900 mt-1">
                        Rp {(transaksi.total || 0).toLocaleString()}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-8">
                <FileText className="h-12 w-12 text-gray-300 mx-auto mb-3" />
                <p className="text-gray-500 text-sm">Belum ada transaksi</p>
              </div>
            )}
          </div>

          {/* Quick Actions */}
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Aksi Cepat</h3>
            <div className="space-y-3">
              <Link
                to="/services"
                className="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-green-100 rounded-lg hover:from-green-100 hover:to-green-200 transition-colors group"
              >
                <div className="flex items-center">
                  <div className="p-2 bg-green-500 rounded-lg">
                    <Plus className="h-5 w-5 text-white" />
                  </div>
                  <div className="ml-3">
                    <p className="font-medium text-gray-900">Langganan Layanan Baru</p>
                    <p className="text-xs text-gray-600">Jelajahi layanan kami</p>
                  </div>
                </div>
                <ArrowRight className="h-5 w-5 text-green-600 group-hover:translate-x-1 transition-transform" />
              </Link>

              <Link
                to="/transactions"
                className="flex items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg hover:from-blue-100 hover:to-blue-200 transition-colors group"
              >
                <div className="flex items-center">
                  <div className="p-2 bg-blue-500 rounded-lg">
                    <BarChart3 className="h-5 w-5 text-white" />
                  </div>
                  <div className="ml-3">
                    <p className="font-medium text-gray-900">Lihat Transaksi</p>
                    <p className="text-xs text-gray-600">Riwayat transaksi Anda</p>
                  </div>
                </div>
                <ArrowRight className="h-5 w-5 text-blue-600 group-hover:translate-x-1 transition-transform" />
              </Link>

              <Link
                to="/invoices"
                className="flex items-center justify-between p-4 bg-gradient-to-r from-yellow-50 to-yellow-100 rounded-lg hover:from-yellow-100 hover:to-yellow-200 transition-colors group"
              >
                <div className="flex items-center">
                  <div className="p-2 bg-yellow-500 rounded-lg">
                    <CreditCard className="h-5 w-5 text-white" />
                  </div>
                  <div className="ml-3">
                    <p className="font-medium text-gray-900">Bayar Invoice</p>
                    <p className="text-xs text-gray-600">
                      {dashboardData?.pending_invoices || 0} invoice tertunda
                    </p>
                  </div>
                </div>
                <ArrowRight className="h-5 w-5 text-yellow-600 group-hover:translate-x-1 transition-transform" />
              </Link>

              <Link
                to="/documents"
                className="flex items-center justify-between p-4 bg-gradient-to-r from-purple-50 to-purple-100 rounded-lg hover:from-purple-100 hover:to-purple-200 transition-colors group"
              >
                <div className="flex items-center">
                  <div className="p-2 bg-purple-500 rounded-lg">
                    <FileText className="h-5 w-5 text-white" />
                  </div>
                  <div className="ml-3">
                    <p className="font-medium text-gray-900">Kelola Dokumen</p>
                    <p className="text-xs text-gray-600">Upload dan lihat dokumen</p>
                  </div>
                </div>
                <ArrowRight className="h-5 w-5 text-purple-600 group-hover:translate-x-1 transition-transform" />
              </Link>
            </div>
          </div>
        </div>

        {/* Navigation Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <Link to="/services" className="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow border-t-4 border-green-500">
            <div className="flex items-start">
              <div className="p-3 bg-green-100 rounded-lg">
                <FileText className="h-8 w-8 text-green-600" />
              </div>
              <div className="ml-4 flex-1">
                <h3 className="text-lg font-semibold text-gray-900 mb-1">Layanan</h3>
                <p className="text-sm text-gray-600">Jelajahi dan langganan layanan pengelolaan lingkungan</p>
              </div>
            </div>
          </Link>

          <Link to="/waste-collection" className="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow border-t-4 border-blue-500">
            <div className="flex items-start">
              <div className="p-3 bg-blue-100 rounded-lg">
                <Truck className="h-8 w-8 text-blue-600" />
              </div>
              <div className="ml-4 flex-1">
                <h3 className="text-lg font-semibold text-gray-900 mb-1">Pengangkutan</h3>
                <p className="text-sm text-gray-600">Jadwalkan pengangkutan limbah Anda</p>
              </div>
            </div>
          </Link>

          <Link to="/manifests" className="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow border-t-4 border-yellow-500">
            <div className="flex items-start">
              <div className="p-3 bg-yellow-100 rounded-lg">
                <FileText className="h-8 w-8 text-yellow-600" />
              </div>
              <div className="ml-4 flex-1">
                <h3 className="text-lg font-semibold text-gray-900 mb-1">Manifest</h3>
                <p className="text-sm text-gray-600">Kelola manifest elektronik limbah</p>
              </div>
            </div>
          </Link>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;