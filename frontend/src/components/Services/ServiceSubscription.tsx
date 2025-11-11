import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import axios from 'axios';
import { useAuth } from '../../contexts/AuthContext';
import { 
  AlertCircle, 
  Loader2, 
  CheckCircle, 
  Clock, 
  DollarSign,
  ArrowLeft,
  Calendar
} from 'lucide-react';
import { Service } from './ServiceList';

const API_URL = 'http://localhost:8080/api';

const ServiceSubscription: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { user } = useAuth();
  
  const [service, setService] = useState<Service | null>(null);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);
  const [startDate, setStartDate] = useState('');

  useEffect(() => {
    if (id) {
      fetchServiceDetails();
    }
    // Set default start date to today
    const today = new Date().toISOString().split('T')[0];
    setStartDate(today);
  }, [id]);

  const fetchServiceDetails = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const response = await axios.get(`${API_URL}/services/${id}`);
      
      if (response.data.status === 'success') {
        setService(response.data.data);
      } else {
        throw new Error('Failed to fetch service details');
      }
    } catch (error: any) {
      console.error('Failed to fetch service details:', error);
      setError(error.response?.data?.message || 'Gagal memuat detail layanan');
    } finally {
      setLoading(false);
    }
  };

  const handleSubscribe = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!service || !startDate) {
      setError('Mohon lengkapi semua data');
      return;
    }

    try {
      setSubmitting(true);
      setError(null);
      
      const response = await axios.post(`${API_URL}/services/subscribe`, {
        layanan_id: service.id,
        tanggal_mulai: startDate
      });
      
      if (response.data.status === 'success') {
        setSuccess(true);
        setTimeout(() => {
          navigate('/services/my-services');
        }, 2000);
      } else {
        throw new Error('Failed to subscribe to service');
      }
    } catch (error: any) {
      console.error('Failed to subscribe:', error);
      setError(error.response?.data?.message || 'Gagal berlangganan layanan');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <Loader2 className="h-12 w-12 text-green-600 animate-spin mx-auto mb-4" />
          <p className="text-gray-600">Memuat detail layanan...</p>
        </div>
      </div>
    );
  }

  if (!service) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <AlertCircle className="h-12 w-12 text-red-600 mx-auto mb-4" />
          <p className="text-gray-600 mb-4">Layanan tidak ditemukan</p>
          <Link to="/services" className="text-green-600 hover:text-green-700 font-medium">
            Kembali ke Daftar Layanan
          </Link>
        </div>
      </div>
    );
  }

  const features = service.fitur ? service.fitur.split(',').map(f => f.trim()) : [];
  const endDate = startDate ? new Date(new Date(startDate).getTime() + service.durasi_hari * 24 * 60 * 60 * 1000).toISOString().split('T')[0] : '';

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Back Button */}
        <Link
          to="/services"
          className="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-6"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke Daftar Layanan
        </Link>

        {/* Success Message */}
        {success && (
          <div className="mb-6 bg-green-50 border border-green-200 rounded-lg p-4 flex items-start">
            <CheckCircle className="h-5 w-5 text-green-600 mt-0.5 mr-3 flex-shrink-0" />
            <div>
              <h3 className="text-sm font-medium text-green-800">Berhasil!</h3>
              <p className="text-sm text-green-700 mt-1">
                Anda berhasil berlangganan layanan. Mengarahkan ke halaman layanan saya...
              </p>
            </div>
          </div>
        )}

        {/* Error Alert */}
        {error && (
          <div className="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-start">
            <AlertCircle className="h-5 w-5 text-red-600 mt-0.5 mr-3 flex-shrink-0" />
            <div>
              <h3 className="text-sm font-medium text-red-800">Gagal berlangganan</h3>
              <p className="text-sm text-red-700 mt-1">{error}</p>
            </div>
          </div>
        )}

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Service Details */}
          <div className="lg:col-span-2">
            <div className="bg-white rounded-lg shadow p-6">
              <h1 className="text-2xl font-bold text-gray-900 mb-4">
                {service.nama_layanan}
              </h1>
              
              <div className="flex items-center space-x-6 mb-6 pb-6 border-b">
                <div className="flex items-center text-gray-600">
                  <DollarSign className="h-5 w-5 mr-2 text-green-600" />
                  <div>
                    <p className="text-xs text-gray-500">Harga</p>
                    <p className="text-lg font-bold text-gray-900">
                      Rp {service.harga.toLocaleString('id-ID')}
                    </p>
                  </div>
                </div>
                <div className="flex items-center text-gray-600">
                  <Clock className="h-5 w-5 mr-2 text-blue-600" />
                  <div>
                    <p className="text-xs text-gray-500">Durasi</p>
                    <p className="text-lg font-bold text-gray-900">
                      {service.durasi_hari} hari
                    </p>
                  </div>
                </div>
              </div>

              <div className="mb-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-3">Deskripsi</h3>
                <p className="text-gray-600">{service.deskripsi}</p>
              </div>

              {features.length > 0 && (
                <div>
                  <h3 className="text-lg font-semibold text-gray-900 mb-3">Fitur Layanan</h3>
                  <ul className="space-y-2">
                    {features.map((feature, index) => (
                      <li key={index} className="flex items-start text-gray-600">
                        <CheckCircle className="h-5 w-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" />
                        <span>{feature}</span>
                      </li>
                    ))}
                  </ul>
                </div>
              )}
            </div>
          </div>

          {/* Subscription Form */}
          <div className="lg:col-span-1">
            <div className="bg-white rounded-lg shadow p-6 sticky top-24">
              <h2 className="text-xl font-bold text-gray-900 mb-4">
                Langganan Layanan
              </h2>

              <form onSubmit={handleSubscribe} className="space-y-4">
                {/* User Info */}
                <div className="bg-gray-50 rounded-lg p-4">
                  <p className="text-sm text-gray-600 mb-1">Perusahaan</p>
                  <p className="font-medium text-gray-900">{user?.nama_perusahaan}</p>
                </div>

                {/* Start Date */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    <Calendar className="h-4 w-4 inline mr-1" />
                    Tanggal Mulai
                  </label>
                  <input
                    type="date"
                    value={startDate}
                    onChange={(e) => setStartDate(e.target.value)}
                    min={new Date().toISOString().split('T')[0]}
                    required
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                  />
                </div>

                {/* End Date Display */}
                {endDate && (
                  <div className="bg-blue-50 rounded-lg p-4">
                    <p className="text-sm text-blue-600 mb-1">Tanggal Berakhir</p>
                    <p className="font-medium text-blue-900">
                      {new Date(endDate).toLocaleDateString('id-ID', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                      })}
                    </p>
                  </div>
                )}

                {/* Total Price */}
                <div className="bg-green-50 rounded-lg p-4">
                  <p className="text-sm text-green-600 mb-1">Total Pembayaran</p>
                  <p className="text-2xl font-bold text-green-900">
                    Rp {service.harga.toLocaleString('id-ID')}
                  </p>
                </div>

                {/* Submit Button */}
                <button
                  type="submit"
                  disabled={submitting || success}
                  className="w-full bg-green-600 text-white py-3 rounded-lg font-medium hover:bg-green-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed flex items-center justify-center"
                >
                  {submitting ? (
                    <>
                      <Loader2 className="h-5 w-5 mr-2 animate-spin" />
                      Memproses...
                    </>
                  ) : success ? (
                    <>
                      <CheckCircle className="h-5 w-5 mr-2" />
                      Berhasil
                    </>
                  ) : (
                    'Konfirmasi Langganan'
                  )}
                </button>

                <p className="text-xs text-gray-500 text-center">
                  Dengan berlangganan, Anda menyetujui syarat dan ketentuan layanan
                </p>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ServiceSubscription;
