import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import axios from 'axios';
import { AlertCircle, Loader2, Search, Filter, FileText, DollarSign, Eye, Download } from 'lucide-react';

const API_URL = 'http://localhost:8080/api';

export interface Invoice {
  id: number;
  user_id: number;
  transaksi_id: number;
  nomor_invoice: string;
  tanggal_invoice: string;
  tanggal_jatuh_tempo: string;
  total_tagihan: number;
  status_pembayaran: string;
  tanggal_pembayaran: string | null;
  created_at: string;
  updated_at: string;
}

const InvoiceList: React.FC = () => {
  const [invoices, setInvoices] = useState<Invoice[]>([]);
  const [filteredInvoices, setFilteredInvoices] = useState<Invoice[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  
  // Filter and sort states
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [sortBy, setSortBy] = useState<'date' | 'amount' | 'due'>('date');
  const [sortOrder, setSortOrder] = useState<'asc' | 'desc'>('desc');

  useEffect(() => {
    fetchInvoices();
  }, []);

  useEffect(() => {
    applyFiltersAndSort();
  }, [invoices, searchQuery, statusFilter, sortBy, sortOrder]);

  const fetchInvoices = async () => {
    try {
      setLoading(true);
      setError(null);

      const response = await axios.get(`${API_URL}/invoices`);

      if (response.data.status === 'success') {
        setInvoices(response.data.data);
      } else {
        throw new Error('Failed to fetch invoices');
      }
    } catch (error: any) {
      console.error('Failed to fetch invoices:', error);
      setError(error.response?.data?.message || 'Gagal memuat invoice');
    } finally {
      setLoading(false);
    }
  };

  const applyFiltersAndSort = () => {
    let filtered = [...invoices];

    // Apply search filter
    if (searchQuery) {
      filtered = filtered.filter(
        (invoice) =>
          invoice.nomor_invoice.toLowerCase().includes(searchQuery.toLowerCase()) ||
          invoice.id.toString().includes(searchQuery)
      );
    }

    // Apply status filter
    if (statusFilter !== 'all') {
      filtered = filtered.filter(
        (invoice) => invoice.status_pembayaran.toLowerCase() === statusFilter.toLowerCase()
      );
    }

    // Apply sorting
    filtered.sort((a, b) => {
      let comparison = 0;

      if (sortBy === 'date') {
        comparison = new Date(a.tanggal_invoice).getTime() - new Date(b.tanggal_invoice).getTime();
      } else if (sortBy === 'amount') {
        comparison = a.total_tagihan - b.total_tagihan;
      } else if (sortBy === 'due') {
        comparison = new Date(a.tanggal_jatuh_tempo).getTime() - new Date(b.tanggal_jatuh_tempo).getTime();
      }

      return sortOrder === 'asc' ? comparison : -comparison;
    });

    setFilteredInvoices(filtered);
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

  const getStatusBadge = (status: string) => {
    const statusConfig: Record<string, { bg: string; text: string; label: string }> = {
      pending: { bg: 'bg-yellow-100', text: 'text-yellow-800', label: 'Pending' },
      paid: { bg: 'bg-green-100', text: 'text-green-800', label: 'Lunas' },
      overdue: { bg: 'bg-red-100', text: 'text-red-800', label: 'Jatuh Tempo' },
      cancelled: { bg: 'bg-gray-100', text: 'text-gray-800', label: 'Dibatalkan' },
    };

    const config = statusConfig[status.toLowerCase()] || statusConfig.pending;

    return (
      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${config.bg} ${config.text}`}>
        {config.label}
      </span>
    );
  };

  const isOverdue = (invoice: Invoice) => {
    if (invoice.status_pembayaran.toLowerCase() === 'paid') return false;
    return new Date(invoice.tanggal_jatuh_tempo) < new Date();
  };

  const handleDownload = async (invoiceId: number) => {
    try {
      const response = await axios.get(`${API_URL}/invoices/${invoiceId}/download`, {
        responseType: 'blob',
      });

      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `invoice-${invoiceId}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (error: any) {
      console.error('Failed to download invoice:', error);
      alert('Gagal mengunduh invoice');
    }
  };

  const getUniqueStatuses = () => {
    const statuses = invoices.map((i) => i.status_pembayaran);
    return Array.from(new Set(statuses));
  };

  const getPendingCount = () => {
    return invoices.filter((i) => i.status_pembayaran.toLowerCase() === 'pending').length;
  };

  const getOverdueCount = () => {
    return invoices.filter((i) => isOverdue(i)).length;
  };

  const getTotalAmount = () => {
    return invoices.reduce((sum, i) => sum + i.total_tagihan, 0);
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <Loader2 className="h-12 w-12 text-green-600 animate-spin mx-auto mb-4" />
          <p className="text-gray-600">Memuat invoice...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Invoice</h1>
          <p className="text-gray-600">
            Kelola dan lihat semua invoice Anda
          </p>
        </div>

        {/* Error Alert */}
        {error && (
          <div className="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-start">
            <AlertCircle className="h-5 w-5 text-red-600 mt-0.5 mr-3 flex-shrink-0" />
            <div className="flex-1">
              <h3 className="text-sm font-medium text-red-800">Gagal memuat invoice</h3>
              <p className="text-sm text-red-700 mt-1">{error}</p>
              <button
                onClick={fetchInvoices}
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
                Cari Invoice
              </label>
              <div className="relative">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                <input
                  type="text"
                  placeholder="Cari berdasarkan nomor invoice atau ID..."
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
                    'date' | 'amount' | 'due',
                    'asc' | 'desc'
                  ];
                  setSortBy(newSortBy);
                  setSortOrder(newSortOrder);
                }}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
              >
                <option value="date-desc">Terbaru</option>
                <option value="date-asc">Terlama</option>
                <option value="due-asc">Jatuh Tempo Terdekat</option>
                <option value="due-desc">Jatuh Tempo Terjauh</option>
                <option value="amount-desc">Jumlah Tertinggi</option>
                <option value="amount-asc">Jumlah Terendah</option>
              </select>
            </div>
          </div>
        </div>

        {/* Invoice Stats */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div className="bg-white rounded-lg shadow p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Total Invoice</p>
                <p className="text-2xl font-bold text-gray-900">{invoices.length}</p>
              </div>
              <FileText className="h-10 w-10 text-green-600" />
            </div>
          </div>

          <div className="bg-white rounded-lg shadow p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Pending</p>
                <p className="text-2xl font-bold text-yellow-600">{getPendingCount()}</p>
              </div>
              <AlertCircle className="h-10 w-10 text-yellow-600" />
            </div>
          </div>

          <div className="bg-white rounded-lg shadow p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Jatuh Tempo</p>
                <p className="text-2xl font-bold text-red-600">{getOverdueCount()}</p>
              </div>
              <AlertCircle className="h-10 w-10 text-red-600" />
            </div>
          </div>

          <div className="bg-white rounded-lg shadow p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Total Tagihan</p>
                <p className="text-xl font-bold text-gray-900">
                  {formatCurrency(getTotalAmount())}
                </p>
              </div>
              <DollarSign className="h-10 w-10 text-green-600" />
            </div>
          </div>
        </div>

        {/* Invoices List */}
        {filteredInvoices.length > 0 ? (
          <div className="bg-white rounded-lg shadow overflow-hidden">
            {/* Desktop View */}
            <div className="hidden md:block overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Nomor Invoice
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Tanggal
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Jatuh Tempo
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Total Tagihan
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
                  {filteredInvoices.map((invoice) => (
                    <tr 
                      key={invoice.id} 
                      className={`hover:bg-gray-50 ${isOverdue(invoice) ? 'bg-red-50' : ''}`}
                    >
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm font-medium text-gray-900">
                          {invoice.nomor_invoice}
                        </div>
                        <div className="text-sm text-gray-500">
                          ID: #{invoice.id}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {formatDate(invoice.tanggal_invoice)}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm text-gray-900">
                          {formatDate(invoice.tanggal_jatuh_tempo)}
                        </div>
                        {isOverdue(invoice) && (
                          <div className="text-xs text-red-600 font-medium">
                            Lewat jatuh tempo
                          </div>
                        )}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {formatCurrency(invoice.total_tagihan)}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        {getStatusBadge(invoice.status_pembayaran)}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div className="flex items-center justify-end space-x-2">
                          <Link
                            to={`/invoices/${invoice.id}`}
                            className="inline-flex items-center px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                          >
                            <Eye className="h-4 w-4 mr-1" />
                            Detail
                          </Link>
                          <button
                            onClick={() => handleDownload(invoice.id)}
                            className="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                          >
                            <Download className="h-4 w-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* Mobile View */}
            <div className="md:hidden divide-y divide-gray-200">
              {filteredInvoices.map((invoice) => (
                <div 
                  key={invoice.id} 
                  className={`p-4 ${isOverdue(invoice) ? 'bg-red-50' : ''}`}
                >
                  <div className="flex items-start justify-between mb-3">
                    <div>
                      <p className="text-sm font-medium text-gray-900">
                        {invoice.nomor_invoice}
                      </p>
                      <p className="text-xs text-gray-500 mt-1">
                        ID: #{invoice.id}
                      </p>
                    </div>
                    {getStatusBadge(invoice.status_pembayaran)}
                  </div>

                  <div className="space-y-2 mb-3">
                    <div className="flex items-center justify-between text-sm">
                      <span className="text-gray-600">Tanggal:</span>
                      <span className="text-gray-900">{formatDate(invoice.tanggal_invoice)}</span>
                    </div>
                    <div className="flex items-center justify-between text-sm">
                      <span className="text-gray-600">Jatuh Tempo:</span>
                      <span className={isOverdue(invoice) ? 'text-red-600 font-medium' : 'text-gray-900'}>
                        {formatDate(invoice.tanggal_jatuh_tempo)}
                      </span>
                    </div>
                    <div className="flex items-center justify-between text-sm font-medium">
                      <span className="text-gray-600">Total:</span>
                      <span className="text-gray-900">{formatCurrency(invoice.total_tagihan)}</span>
                    </div>
                  </div>

                  <div className="flex space-x-2">
                    <Link
                      to={`/invoices/${invoice.id}`}
                      className="flex-1 text-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                    >
                      Lihat Detail
                    </Link>
                    <button
                      onClick={() => handleDownload(invoice.id)}
                      className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                    >
                      <Download className="h-5 w-5" />
                    </button>
                  </div>
                </div>
              ))}
            </div>
          </div>
        ) : (
          <div className="bg-white rounded-lg shadow p-12 text-center">
            <FileText className="h-16 w-16 text-gray-400 mx-auto mb-4" />
            <h3 className="text-lg font-medium text-gray-900 mb-2">
              Tidak ada invoice
            </h3>
            <p className="text-gray-600 mb-6">
              {searchQuery || statusFilter !== 'all'
                ? 'Tidak ada invoice yang sesuai dengan filter Anda'
                : 'Anda belum memiliki invoice. Invoice akan muncul setelah Anda melakukan transaksi.'}
            </p>
          </div>
        )}
      </div>
    </div>
  );
};

export default InvoiceList;
