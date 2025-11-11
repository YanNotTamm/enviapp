import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import axios from 'axios';
import { 
  AlertCircle, 
  Loader2, 
  Calendar, 
  Clock, 
  CheckCircle,
  XCircle,
  Plus,
  Eye
} from 'lucide-react';

const API_URL = 'http://localhost:8080/api';

interface UserService {
  id: number;
  layanan_id: number;
  nama_layanan: string;
  tanggal_mulai: string;
  tanggal_selesai: string;
  status: string;
  total_harga: number;
}

const MyServices: React.FC = () => {
  const [services, setServices] = useState<UserService[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchMyServices();
  }, []);

  const fetchMyServices = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const response = await axios.get(`${API_URL}/services/my-services`);
      
      if (response.data.status === 'success') {
        setServices(response.data.data);
      } else {
        throw new Error('Failed to fetch services');
      }
    } catch (error: any) {
      console.error('Failed to fetch my services:', error);
      setError(error.response?.data?.message || 'Gagal memuat layanan Anda');
    } finally {
      setLoading(false);
    }
  };

  const getStatusColor = (status: string) => {
    switch (status.toLowerCase()) {
      case 'active':
      case 'aktif':
        return 'bg-green-100 text-green-800';
      case 'expired':
      case 'kadaluarsa':
        return 'bg-red-100 text-red-800';
      case 'pending':
      case 'menunggu':
        return 'bg-yellow-100 text-yellow-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status.toLowerCase()) {
      case 'active':
      case 'aktif':
        return <CheckCircle className="h-5 w-5 text-green-600" />;
      case 'expired':
      case 'kadaluarsa':
        return <XCircle className="h-5 w-5 text-red-600" />;
      case 'pending':
      case 'menunggu':
        return <Clock className="h-5 w-5 text-yellow-600" />;
      default:
        return <Clock className="h-5 w-5 text-gray-600" />;
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  const getDaysRemaining = (endDate: string) => {
    const end = new Date(endDate);
    const today = new Date();
    const diffTime = end.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <Loader2 className="h-12 w-12 text-green-600 animate-spin mx-auto mb-4" />
          <p className="text-gray-600">Memuat layanan Anda...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-8 flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 mb-2">Layanan Saya</h1>
            <p className="text-gray-600">
              Kelola dan pantau layanan yang Anda gunakan
            </p>
          </div>
          <Link
            to="/services"
            className="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium"
          >
            <Plus className="h-5 w-5 mr-2" />
            Langganan Layanan Baru
          </Link>
        </div>

        {/* Error Alert */}
        {error && (
          <div className="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-start">
            <AlertCircle className="h-5 w-5 text-red-600 mt-0.5 mr-3 flex-shrink-0" />
            <div className="flex-1">
              <h3 className="text-sm font-medium text-red-800">Gagal memuat layanan</h3>
              <p className="text-sm text-red-700 mt-1">{error}</p>
              <button
                onClick={fetchMyServices}
                className="mt-2 text-sm font-medium text-red-600 hover:text-red-500"
              >
                Coba lagi
              </button>
            </div>
          </div>
        )}

        {/* Services List */}
        {services.length > 0 ? (
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {services.map((service) => {
              const daysRemaining = getDaysRemaining(service.tanggal_selesai);
              const isExpiringSoon = daysRemaining > 0 && daysRemaining <= 7;
              
              return (
                <div
                  key={service.id}
                  className="bg-white rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200"
                >
                  {/* Header */}
                  <div className="p-6 border-b border-gray-200">
                    <div className="flex items-start justify-between mb-3">
                      <div className="flex-1">
                        <h3 className="text-xl font-bold text-gray-900 mb-2">
                          {service.nama_layanan}
                        </h3>
                        <span
                          className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${getStatusColor(
                            service.status
                          )}`}
                        >
                          {service.status}
                        </span>
                      </div>
                      <div className="ml-4">
                        {getStatusIcon(service.status)}
                      </div>
                    </div>
                  </div>

                  {/* Body */}
                  <div className="p-6">
                    {/* Date Information */}
                    <div className="space-y-3 mb-4">
                      <div className="flex items-center text-sm text-gray-600">
                        <Calendar className="h-4 w-4 mr-3 text-gray-400" />
                        <div>
                          <span className="font-medium">Tanggal Mulai:</span>{' '}
                          {formatDate(service.tanggal_mulai)}
                        </div>
                      </div>
                      <div className="flex items-center text-sm text-gray-600">
                        <Calendar className="h-4 w-4 mr-3 text-gray-400" />
                        <div>
                          <span className="font-medium">Tanggal Berakhir:</span>{' '}
                          {formatDate(service.tanggal_selesai)}
                        </div>
                      </div>
                    </div>

                    {/* Days Remaining Alert */}
                    {service.status.toLowerCase() === 'active' || service.status.toLowerCase() === 'aktif' ? (
                      daysRemaining > 0 ? (
                        <div
                          className={`p-3 rounded-lg mb-4 ${
                            isExpiringSoon
                              ? 'bg-yellow-50 border border-yellow-200'
                              : 'bg-blue-50 border border-blue-200'
                          }`}
                        >
                          <div className="flex items-center">
                            <Clock
                              className={`h-4 w-4 mr-2 ${
                                isExpiringSoon ? 'text-yellow-600' : 'text-blue-600'
                              }`}
                            />
                            <span
                              className={`text-sm font-medium ${
                                isExpiringSoon ? 'text-yellow-800' : 'text-blue-800'
                              }`}
                            >
                              {daysRemaining} hari tersisa
                            </span>
                          </div>
                          {isExpiringSoon && (
                            <p className="text-xs text-yellow-700 mt-1 ml-6">
                              Layanan akan segera berakhir
                            </p>
                          )}
                        </div>
                      ) : (
                        <div className="p-3 rounded-lg mb-4 bg-red-50 border border-red-200">
                          <div className="flex items-center">
                            <XCircle className="h-4 w-4 mr-2 text-red-600" />
                            <span className="text-sm font-medium text-red-800">
                              Layanan telah berakhir
                            </span>
                          </div>
                        </div>
                      )
                    ) : null}

                    {/* Price */}
                    <div className="flex items-center justify-between pt-4 border-t border-gray-200">
                      <div>
                        <p className="text-xs text-gray-500 mb-1">Total Pembayaran</p>
                        <p className="text-lg font-bold text-gray-900">
                          Rp {service.total_harga.toLocaleString('id-ID')}
                        </p>
                      </div>
                      <Link
                        to={`/transactions/${service.id}`}
                        className="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium"
                      >
                        <Eye className="h-4 w-4 mr-2" />
                        Detail
                      </Link>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        ) : (
          <div className="text-center py-12 bg-white rounded-lg shadow">
            <div className="max-w-md mx-auto">
              <div className="mb-4">
                <div className="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full">
                  <CheckCircle className="h-8 w-8 text-gray-400" />
                </div>
              </div>
              <h3 className="text-lg font-semibold text-gray-900 mb-2">
                Belum Ada Layanan
              </h3>
              <p className="text-gray-500 mb-6">
                Anda belum berlangganan layanan apapun. Mulai jelajahi layanan kami sekarang!
              </p>
              <Link
                to="/services"
                className="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium"
              >
                <Plus className="h-5 w-5 mr-2" />
                Jelajahi Layanan
              </Link>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default MyServices;
