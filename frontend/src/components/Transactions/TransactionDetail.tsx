import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import axios from 'axios';
import { ArrowLeft, Calendar, DollarSign, Package, Loader2, AlertCircle } from 'lucide-react';
import TransactionStatus from './TransactionStatus';

const API_URL = 'http://localhost:8080/api';

interface Transaction {
  id: number;
  user_id: number;
  layanan_id: number;
  nama_layanan: string;
  tanggal_mulai: string;
  tanggal_selesai: string;
  status: string;
  total_harga: number;
  created_at: string;
  updated_at: string;
}

const TransactionDetail: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [transaction, setTransaction] = useState<Transaction | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (id) {
      fetchTransactionDetail();
    }
  }, [id]);

  const fetchTransactionDetail = async () => {
    try {
      setLoading(true);
      setError(null);

      const response = await axios.get(`${API_URL}/transactions/${id}`);

      if (response.data.status === 'success') {
        setTransaction(response.data.data);
      } else {
        throw new Error('Failed to fetch transaction details');
      }
    } catch (error: any) {
      console.error('Failed to fetch transaction details:', error);
      setError(error.response?.data?.message || 'Gagal memuat detail transaksi');
    } finally {
      setLoading(false);
    }
  };

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(amount);
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <Loader2 className="h-12 w-12 text-green-600 animate-spin mx-auto mb-4" />
          <p className="text-gray-600">Memuat detail transaksi...</p>
        </div>
      </div>
    );
  }

  if (error || !transaction) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="max-w-md w-full mx-4">
          <div className="bg-white rounded-lg shadow-lg p-6">
            <div className="flex items-center justify-center mb-4">
              <AlertCircle className="h-12 w-12 text-red-600" />
            </div>
            <h2 className="text-xl font-bold text-gray-900 text-center mb-2">
              Gagal Memuat Transaksi
            </h2>
            <p className="text-gray-600 text-center mb-6">{error}</p>
            <div className="flex gap-3">
              <button
                onClick={() => navigate('/transactions')}
                className="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
              >
                Kembali
              </button>
              <button
                onClick={fetchTransactionDetail}
                className="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
              >
                Coba Lagi
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Back Button */}
        <button
          onClick={() => navigate('/transactions')}
          className="flex items-center text-gray-600 hover:text-gray-900 mb-6 transition-colors"
        >
          <ArrowLeft className="h-5 w-5 mr-2" />
          Kembali ke Daftar Transaksi
        </button>

        {/* Transaction Header */}
        <div className="bg-white rounded-lg shadow-lg p-6 mb-6">
          <div className="flex items-start justify-between mb-4">
            <div>
              <h1 className="text-2xl font-bold text-gray-900 mb-2">
                Detail Transaksi
              </h1>
              <p className="text-gray-600">ID Transaksi: #{transaction.id}</p>
            </div>
            <TransactionStatus status={transaction.status} size="lg" />
          </div>

          <div className="border-t pt-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {/* Service Information */}
              <div className="space-y-4">
                <h3 className="text-lg font-semibold text-gray-900 mb-3">
                  Informasi Layanan
                </h3>
                
                <div className="flex items-start">
                  <Package className="h-5 w-5 text-gray-400 mt-0.5 mr-3 flex-shrink-0" />
                  <div>
                    <p className="text-sm text-gray-500">Nama Layanan</p>
                    <p className="text-base font-medium text-gray-900">
                      {transaction.nama_layanan}
                    </p>
                  </div>
                </div>

                <div className="flex items-start">
                  <DollarSign className="h-5 w-5 text-gray-400 mt-0.5 mr-3 flex-shrink-0" />
                  <div>
                    <p className="text-sm text-gray-500">Total Harga</p>
                    <p className="text-base font-medium text-gray-900">
                      {formatCurrency(transaction.total_harga)}
                    </p>
                  </div>
                </div>
              </div>

              {/* Date Information */}
              <div className="space-y-4">
                <h3 className="text-lg font-semibold text-gray-900 mb-3">
                  Informasi Periode
                </h3>
                
                <div className="flex items-start">
                  <Calendar className="h-5 w-5 text-gray-400 mt-0.5 mr-3 flex-shrink-0" />
                  <div>
                    <p className="text-sm text-gray-500">Tanggal Mulai</p>
                    <p className="text-base font-medium text-gray-900">
                      {formatDate(transaction.tanggal_mulai)}
                    </p>
                  </div>
                </div>

                <div className="flex items-start">
                  <Calendar className="h-5 w-5 text-gray-400 mt-0.5 mr-3 flex-shrink-0" />
                  <div>
                    <p className="text-sm text-gray-500">Tanggal Selesai</p>
                    <p className="text-base font-medium text-gray-900">
                      {formatDate(transaction.tanggal_selesai)}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Transaction Timeline */}
        <div className="bg-white rounded-lg shadow-lg p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">
            Riwayat Transaksi
          </h3>
          
          <div className="space-y-4">
            <div className="flex items-start">
              <div className="flex-shrink-0">
                <div className="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                  <Calendar className="h-4 w-4 text-green-600" />
                </div>
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-900">Transaksi Dibuat</p>
                <p className="text-sm text-gray-500">
                  {formatDate(transaction.created_at)}
                </p>
              </div>
            </div>

            {transaction.updated_at !== transaction.created_at && (
              <div className="flex items-start">
                <div className="flex-shrink-0">
                  <div className="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                    <Calendar className="h-4 w-4 text-blue-600" />
                  </div>
                </div>
                <div className="ml-4">
                  <p className="text-sm font-medium text-gray-900">Terakhir Diperbarui</p>
                  <p className="text-sm text-gray-500">
                    {formatDate(transaction.updated_at)}
                  </p>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Action Buttons */}
        <div className="mt-6 flex gap-3">
          <Link
            to="/transactions"
            className="flex-1 px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-center font-medium"
          >
            Kembali ke Daftar
          </Link>
          <Link
            to={`/services/${transaction.layanan_id}`}
            className="flex-1 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-center font-medium"
          >
            Lihat Layanan
          </Link>
        </div>
      </div>
    </div>
  );
};

export default TransactionDetail;
