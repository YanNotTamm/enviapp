import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import axios from 'axios';
import { AlertCircle, Loader2, Search, Filter, Calendar, Package, DollarSign, Eye } from 'lucide-react';
import TransactionStatus from './TransactionStatus';

const API_URL = 'http://localhost:8080/api';

export interface Transaction {
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

const TransactionList: React.FC = () => {
  const [transactions, setTransactions] = useState<Transaction[]>([]);
  const [filteredTransactions, setFilteredTransactions] = useState<Transaction[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  
  // Filter and sort states
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [sortBy, setSortBy] = useState<'date' | 'amount'>('date');
  const [sortOrder, setSortOrder] = useState<'asc' | 'desc'>('desc');

  useEffect(() => {
    fetchTransactions();
  }, []);

  useEffect(() => {
    applyFiltersAndSort();
  }, [transactions, searchQuery, statusFilter, sortBy, sortOrder]);

  const fetchTransactions = async () => {
    try {
      setLoading(true);
      setError(null);

      const response = await axios.get(`${API_URL}/transactions`);

      if (response.data.status === 'success') {
        setTransactions(response.data.data);
      } else {
        throw new Error('Failed to fetch transactions');
      }
    } catch (error: any) {
      console.error('Failed to fetch transactions:', error);
      setError(error.response?.data?.message || 'Gagal memuat transaksi');
    } finally {
      setLoading(false);
    }
  };

  const applyFiltersAndSort = () => {
    let filtered = [...transactions];

    // Apply search filter
    if (searchQuery) {
      filtered = filtered.filter(
        (transaction) =>
          transaction.nama_layanan.toLowerCase().includes(searchQuery.toLowerCase()) ||
          transaction.id.toString().includes(searchQuery)
      );
    }

    // Apply status filter
    if (statusFilter !== 'all') {
      filtered = filtered.filter(
        (transaction) => transaction.status.toLowerCase() === statusFilter.toLowerCase()
      );
    }

    // Apply sorting
    filtered.sort((a, b) => {
      let comparison = 0;

      if (sortBy === 'date') {
        comparison = new Date(a.created_at).getTime() - new Date(b.created_at).getTime();
      } else if (sortBy === 'amount') {
        comparison = a.total_harga - b.total_harga;
      }

      return sortOrder === 'asc' ? comparison : -comparison;
    });

    setFilteredTransactions(filtered);
  };

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'short',
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

  const getUniqueStatuses = () => {
    const statuses = transactions.map((t) => t.status);
    return Array.from(new Set(statuses));
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <Loader2 className="h-12 w-12 text-green-600 animate-spin mx-auto mb-4" />
          <p className="text-gray-600">Memuat transaksi...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Riwayat Transaksi</h1>
          <p className="text-gray-600">
            Lihat dan kelola semua transaksi layanan Anda
          </p>
        </div>

        {/* Error Alert */}
        {error && (
          <div className="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-start">
            <AlertCircle className="h-5 w-5 text-red-600 mt-0.5 mr-3 flex-shrink-0" />
            <div className="flex-1">
              <h3 className="text-sm font-medium text-red-800">Gagal memuat transaksi</h3>
              <p className="text-sm text-red-700 mt-1">{error}</p>
              <button
                onClick={fetchTransactions}
                className="mt-2 text-sm font-medium text-red-600 hover:text-red-500"
              >
                Coba lagi
              </button>
            </div>
          </div>
        )}

        {/* Filters and Search */}
        <div className="bg-white rounded-lg shadow p-4 mb-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            {/* Search */}
            <div className="md:col-span-2">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Cari Transaksi
              </label>
              <div className="relative">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                <input
                  type="text"
                  placeholder="Cari berdasarkan layanan atau ID..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                />
              </div>
            </div>

            {/* Status Filter */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Status
              </label>
              <div className="relative">
                <Filter className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                <select
                  value={statusFilter}
                  onChange={(e) => setStatusFilter(e.target.value)}
                  className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent appearance-none"
                >
                  <option value="all">Semua Status</option>
                  {getUniqueStatuses().map((status) => (
                    <option key={status} value={status}>
                      {status}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            {/* Sort */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Urutkan
              </label>
              <select
                value={`${sortBy}-${sortOrder}`}
                onChange={(e) => {
                  const [newSortBy, newSortOrder] = e.target.value.split('-') as [
                    'date' | 'amount',
                    'asc' | 'desc'
                  ];
                  setSortBy(newSortBy);
                  setSortOrder(newSortOrder);
                }}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
              >
                <option value="date-desc">Terbaru</option>
                <option value="date-asc">Terlama</option>
                <option value="amount-desc">Harga Tertinggi</option>
                <option value="amount-asc">Harga Terendah</option>
              </select>
            </div>
          </div>
        </div>

        {/* Transaction Stats */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div className="bg-white rounded-lg shadow p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Total Transaksi</p>
                <p className="text-2xl font-bold text-gray-900">{transactions.length}</p>
              </div>
              <Package className="h-10 w-10 text-green-600" />
            </div>
          </div>

          <div className="bg-white rounded-lg shadow p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Total Pengeluaran</p>
                <p className="text-2xl font-bold text-gray-900">
                  {formatCurrency(
                    transactions.reduce((sum, t) => sum + t.total_harga, 0)
                  )}
                </p>
              </div>
              <DollarSign className="h-10 w-10 text-green-600" />
            </div>
          </div>

          <div className="bg-white rounded-lg shadow p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Hasil Filter</p>
                <p className="text-2xl font-bold text-gray-900">
                  {filteredTransactions.length}
                </p>
              </div>
              <Filter className="h-10 w-10 text-green-600" />
            </div>
          </div>
        </div>

        {/* Transactions List */}
        {filteredTransactions.length > 0 ? (
          <div className="bg-white rounded-lg shadow overflow-hidden">
            {/* Desktop View */}
            <div className="hidden md:block overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      ID
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Layanan
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Tanggal
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Total Harga
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Status
                    </th>
                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Aksi
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {filteredTransactions.map((transaction) => (
                    <tr key={transaction.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        #{transaction.id}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm font-medium text-gray-900">
                          {transaction.nama_layanan}
                        </div>
                        <div className="text-sm text-gray-500">
                          {formatDate(transaction.tanggal_mulai)} - {formatDate(transaction.tanggal_selesai)}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {formatDate(transaction.created_at)}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {formatCurrency(transaction.total_harga)}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <TransactionStatus status={transaction.status} size="sm" />
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <Link
                          to={`/transactions/${transaction.id}`}
                          className="inline-flex items-center px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                        >
                          <Eye className="h-4 w-4 mr-1" />
                          Detail
                        </Link>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* Mobile View */}
            <div className="md:hidden divide-y divide-gray-200">
              {filteredTransactions.map((transaction) => (
                <div key={transaction.id} className="p-4">
                  <div className="flex items-start justify-between mb-3">
                    <div>
                      <p className="text-sm font-medium text-gray-900">
                        {transaction.nama_layanan}
                      </p>
                      <p className="text-xs text-gray-500 mt-1">
                        ID: #{transaction.id}
                      </p>
                    </div>
                    <TransactionStatus status={transaction.status} size="sm" />
                  </div>

                  <div className="space-y-2 mb-3">
                    <div className="flex items-center text-sm text-gray-600">
                      <Calendar className="h-4 w-4 mr-2" />
                      {formatDate(transaction.created_at)}
                    </div>
                    <div className="flex items-center text-sm font-medium text-gray-900">
                      <DollarSign className="h-4 w-4 mr-2" />
                      {formatCurrency(transaction.total_harga)}
                    </div>
                  </div>

                  <Link
                    to={`/transactions/${transaction.id}`}
                    className="block w-full text-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                  >
                    Lihat Detail
                  </Link>
                </div>
              ))}
            </div>
          </div>
        ) : (
          <div className="bg-white rounded-lg shadow p-12 text-center">
            <Package className="h-16 w-16 text-gray-400 mx-auto mb-4" />
            <h3 className="text-lg font-medium text-gray-900 mb-2">
              Tidak ada transaksi
            </h3>
            <p className="text-gray-600 mb-6">
              {searchQuery || statusFilter !== 'all'
                ? 'Tidak ada transaksi yang sesuai dengan filter Anda'
                : 'Anda belum memiliki transaksi. Mulai berlangganan layanan sekarang!'}
            </p>
            {!searchQuery && statusFilter === 'all' && (
              <Link
                to="/services"
                className="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium"
              >
                Lihat Layanan
              </Link>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

export default TransactionList;
