import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import axios from 'axios';
import ServiceCard from './ServiceCard';
import { AlertCircle, Loader2 } from 'lucide-react';

const API_URL = 'http://localhost:8080/api';

export interface Service {
  id: number;
  nama_layanan: string;
  deskripsi: string;
  harga: number;
  durasi_hari: number;
  status: string;
  fitur: string;
}

const ServiceList: React.FC = () => {
  const [services, setServices] = useState<Service[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchServices();
  }, []);

  const fetchServices = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const response = await axios.get(`${API_URL}/services`);
      
      if (response.data.status === 'success') {
        setServices(response.data.data);
      } else {
        throw new Error('Failed to fetch services');
      }
    } catch (error: any) {
      console.error('Failed to fetch services:', error);
      setError(error.response?.data?.message || 'Gagal memuat layanan');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <Loader2 className="h-12 w-12 text-green-600 animate-spin mx-auto mb-4" />
          <p className="text-gray-600">Memuat layanan...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Layanan Kami</h1>
          <p className="text-gray-600">
            Pilih layanan pengelolaan lingkungan yang sesuai dengan kebutuhan Anda
          </p>
        </div>

        {/* Error Alert */}
        {error && (
          <div className="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-start">
            <AlertCircle className="h-5 w-5 text-red-600 mt-0.5 mr-3 flex-shrink-0" />
            <div className="flex-1">
              <h3 className="text-sm font-medium text-red-800">Gagal memuat layanan</h3>
              <p className="text-sm text-red-700 mt-1">{error}</p>
              <button
                onClick={fetchServices}
                className="mt-2 text-sm font-medium text-red-600 hover:text-red-500"
              >
                Coba lagi
              </button>
            </div>
          </div>
        )}

        {/* Quick Links */}
        <div className="mb-6 flex items-center justify-between">
          <Link
            to="/services/my-services"
            className="text-sm font-medium text-green-600 hover:text-green-700"
          >
            Lihat Layanan Saya →
          </Link>
        </div>

        {/* Services Grid */}
        {services.length > 0 ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {services.map((service) => (
              <ServiceCard key={service.id} service={service} />
            ))}
          </div>
        ) : (
          <div className="text-center py-12 bg-white rounded-lg shadow">
            <p className="text-gray-500">Tidak ada layanan tersedia saat ini</p>
          </div>
        )}
      </div>
    </div>
  );
};

export default ServiceList;
