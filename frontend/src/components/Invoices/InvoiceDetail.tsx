import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import axios from 'axios';
import { 
  AlertCircle, 
  Loader2, 
  ArrowLeft, 
  FileText, 
  Calendar, 
  DollarSign,
  Download,
  CheckCircle,
  Clock
} from 'lucide-react';
import PaymentButton from './PaymentButton';

const API_URL = 'http://localhost:8080/api';

interface Invoice {
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

const InvoiceDetail: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [invoice, setInvoice] = useState<Invoice | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [downloading, setDownloading] = useState(false);

  useEffect(() => {
    if (id) {
      fetchInvoiceDetail();
    }
  }, [id]);

  const fetchInvoiceDetail = async () => {
    try {
      setLoading(true);
      setError(null);

      const response = await axios.get(`${API_URL}/invoices/${id}`);

      if (response.data.status === 'success') {
        setInvoice(response.data.data);
      } else {
        throw new Error('Failed to fetch invoice details');
      }
    } catch (error: any) {
      console.error('Failed to fetch invoice details:', error);
      setError(error.response?.data?.message || 'Gagal memuat detail invoice');
    } finally {
      setLoading(false);
    }
  };

  const handleDownload = async () => {
    if (!invoice) return;

    try {
      setDownloading(true);
      const response = await axios.get(`${API_URL}/invoices/${invoice.id}/download`, {
        responseType: 'blob',
      });

      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `invoice-${invoice.nomor_invoice}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (error: any) {
      console.error('Failed to download invoice:', error);
      alert('Gagal mengunduh invoice');
    } finally {
      setDownloading(false);
    }
  };

  const handlePaymentSuccess = () => {
    // Refresh invoice data after successful payment
    fetchInvoiceDetail();
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

  const getStatusBadge = (status: string) => {
    const statusConfig: Record<string, { bg: string; text: string; label: string; icon: React.ReactNode }> = {
      pending: { 
        bg: 'bg-yellow-100', 
        text: 'text-yellow-800', 
        label: 'Menunggu Pembayaran',
        icon: <Clock className="h-5 w-5" />
      },
      paid: { 
        bg: 'bg-green-100', 
        text: 'text-green-800', 
        label: 'Lunas',
        icon: <CheckCircle className="h-5 w-5" />
      },
      overdue: { 
        bg: 'bg-red-100', 
        text: 'text-red-800', 
        label: 'Jatuh Tempo',
        icon: <AlertCircle className="h-5 w-5" />
      },
      cancelled: { 
        bg: 'bg-gray-100', 
        text: 'text-gray-800', 
        label: 'Dibatalkan',
        icon: <AlertCircle className="h-5 w-5" />
      },
    };

    const config = statusConfig[status.toLowerCase()] || statusConfig.pending;

    return (
      <div className={`inline-flex items-center px-4 py-2 rounded-lg ${config.bg} ${config.text}`}>
        {config.icon}
        <span className="ml-2 font-medium">{config.label}</span>
      </div>
    );
  };

  const isOverdue = () => {
    if (!invoice) return false;
    if (invoice.status_pembayaran.toLowerCase() === 'paid') return false;
    return new Date(invoice.tanggal_jatuh_tempo) < new Date();
  };

  const getDaysUntilDue = () => {
    if (!invoice) return 0;
    const today = new Date();
    const dueDate = new Date(invoice.tanggal_jatuh_tempo);
    const diffTime = dueDate.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <Loader2 className="h-12 w-12 text-green-600 animate-spin mx-auto mb-4" />
          <p className="text-gray-600">Memuat detail invoice...</p>
        </div>
      </div>
    );
  }

  if (error || !invoice) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center max-w-md">
          <AlertCircle className="h-16 w-16 text-red-600 mx-auto mb-4" />
          <h2 className="text-2xl font-bold text-gray-900 mb-2">Gagal Memuat Invoice</h2>
          <p className="text-gray-600 mb-6">{error || 'Invoice tidak ditemukan'}</p>
          <div className="flex space-x-4 justify-center">
            <button
              onClick={() => navigate('/invoices')}
              className="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
            >
              Kembali ke Daftar
            </button>
            <button
              onClick={fetchInvoiceDetail}
              className="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
            >
              Coba Lagi
            </button>
          </div>
        </div>
      </div>
    );
  }

  const daysUntilDue = getDaysUntilDue();
  const showOverdueWarning = isOverdue();
  const showDueSoonWarning = !showOverdueWarning && daysUntilDue <= 7 && daysUntilDue > 0;

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Back Button */}
        <Link
          to="/invoices"
          className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-6"
        >
          <ArrowLeft className="h-5 w-5 mr-2" />
          Kembali ke Daftar Invoice
        </Link>

        {/* Header */}
        <div className="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
          <div className="bg-gradient-to-r from-green-600 to-green-700 px-6 py-8 text-white">
            <div className="flex items-start justify-between">
              <div>
                <div className="flex items-center mb-2">
                  <FileText className="h-8 w-8 mr-3" />
                  <h1 className="text-3xl font-bold">Invoice</h1>
                </div>
                <p className="text-green-100 text-lg">{invoice.nomor_invoice}</p>
              </div>
              <div className="text-right">
                {getStatusBadge(invoice.status_pembayaran)}
              </div>
            </div>
          </div>

          {/* Warning Banners */}
          {showOverdueWarning && (
            <div className="bg-red-50 border-l-4 border-red-600 px-6 py-4">
              <div className="flex items-start">
                <AlertCircle className="h-5 w-5 text-red-600 mt-0.5 mr-3 flex-shrink-0" />
                <div>
                  <h3 className="text-sm font-medium text-red-800">Invoice Jatuh Tempo</h3>
                  <p className="text-sm text-red-700 mt-1">
                    Invoice ini telah melewati tanggal jatuh tempo. Segera lakukan pembayaran untuk menghindari denda.
                  </p>
                </div>
              </div>
            </div>
          )}

          {showDueSoonWarning && (
            <div className="bg-yellow-50 border-l-4 border-yellow-600 px-6 py-4">
              <div className="flex items-start">
                <Clock className="h-5 w-5 text-yellow-600 mt-0.5 mr-3 flex-shrink-0" />
                <div>
                  <h3 className="text-sm font-medium text-yellow-800">Jatuh Tempo Segera</h3>
                  <p className="text-sm text-yellow-700 mt-1">
                    Invoice ini akan jatuh tempo dalam {daysUntilDue} hari. Segera lakukan pembayaran.
                  </p>
                </div>
              </div>
            </div>
          )}

          {/* Invoice Details */}
          <div className="px-6 py-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              {/* Left Column */}
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-500 mb-1">
                    Nomor Invoice
                  </label>
                  <p className="text-lg font-semibold text-gray-900">{invoice.nomor_invoice}</p>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-500 mb-1">
                    Tanggal Invoice
                  </label>
                  <div className="flex items-center text-gray-900">
                    <Calendar className="h-5 w-5 mr-2 text-gray-400" />
                    {formatDate(invoice.tanggal_invoice)}
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-500 mb-1">
                    Tanggal Jatuh Tempo
                  </label>
                  <div className={`flex items-center ${showOverdueWarning ? 'text-red-600 font-medium' : 'text-gray-900'}`}>
                    <Calendar className="h-5 w-5 mr-2" />
                    {formatDate(invoice.tanggal_jatuh_tempo)}
                  </div>
                </div>
              </div>

              {/* Right Column */}
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-500 mb-1">
                    ID Transaksi
                  </label>
                  <Link
                    to={`/transactions/${invoice.transaksi_id}`}
                    className="text-lg font-semibold text-green-600 hover:text-green-700"
                  >
                    #{invoice.transaksi_id}
                  </Link>
                </div>

                {invoice.tanggal_pembayaran && (
                  <div>
                    <label className="block text-sm font-medium text-gray-500 mb-1">
                      Tanggal Pembayaran
                    </label>
                    <div className="flex items-center text-gray-900">
                      <CheckCircle className="h-5 w-5 mr-2 text-green-600" />
                      {formatDate(invoice.tanggal_pembayaran)}
                    </div>
                  </div>
                )}

                <div>
                  <label className="block text-sm font-medium text-gray-500 mb-1">
                    Total Tagihan
                  </label>
                  <div className="flex items-center">
                    <DollarSign className="h-6 w-6 mr-2 text-gray-400" />
                    <span className="text-2xl font-bold text-gray-900">
                      {formatCurrency(invoice.total_tagihan)}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            {/* Action Buttons */}
            <div className="border-t pt-6 flex flex-col sm:flex-row gap-3">
              <button
                onClick={handleDownload}
                disabled={downloading}
                className="flex-1 inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {downloading ? (
                  <>
                    <Loader2 className="h-5 w-5 mr-2 animate-spin" />
                    Mengunduh...
                  </>
                ) : (
                  <>
                    <Download className="h-5 w-5 mr-2" />
                    Unduh PDF
                  </>
                )}
              </button>

              {invoice.status_pembayaran.toLowerCase() === 'pending' && (
                <div className="flex-1">
                  <PaymentButton 
                    invoiceId={invoice.id} 
                    amount={invoice.total_tagihan}
                    onSuccess={handlePaymentSuccess}
                  />
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Additional Information */}
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">Informasi Tambahan</h2>
          <div className="space-y-3 text-sm text-gray-600">
            <p>
              • Invoice ini dibuat pada {formatDate(invoice.created_at)}
            </p>
            <p>
              • Untuk pertanyaan terkait invoice ini, silakan hubungi customer service kami
            </p>
            <p>
              • Pembayaran dapat dilakukan melalui transfer bank atau metode pembayaran lainnya
            </p>
            {invoice.status_pembayaran.toLowerCase() === 'pending' && (
              <p className="text-yellow-700 font-medium">
                • Harap lakukan pembayaran sebelum tanggal jatuh tempo untuk menghindari denda
              </p>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default InvoiceDetail;
