import React, { useState } from 'react';
import axios from 'axios';
import { CreditCard, Loader2, CheckCircle, AlertCircle } from 'lucide-react';

const API_URL = 'http://localhost:8080/api';

interface PaymentButtonProps {
  invoiceId: number;
  amount: number;
  onSuccess?: () => void;
}

const PaymentButton: React.FC<PaymentButtonProps> = ({ invoiceId, amount, onSuccess }) => {
  const [processing, setProcessing] = useState(false);
  const [showModal, setShowModal] = useState(false);
  const [paymentMethod, setPaymentMethod] = useState<string>('bank_transfer');
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(value);
  };

  const handlePayment = async () => {
    try {
      setProcessing(true);
      setError(null);

      const response = await axios.post(`${API_URL}/invoices/${invoiceId}/pay`, {
        payment_method: paymentMethod,
      });

      if (response.data.status === 'success') {
        setSuccess(true);
        setTimeout(() => {
          setShowModal(false);
          setSuccess(false);
          if (onSuccess) {
            onSuccess();
          }
        }, 2000);
      } else {
        throw new Error('Payment failed');
      }
    } catch (error: any) {
      console.error('Payment failed:', error);
      setError(error.response?.data?.message || 'Gagal memproses pembayaran');
    } finally {
      setProcessing(false);
    }
  };

  const openModal = () => {
    setShowModal(true);
    setError(null);
    setSuccess(false);
  };

  const closeModal = () => {
    if (!processing) {
      setShowModal(false);
      setError(null);
      setSuccess(false);
    }
  };

  return (
    <>
      {/* Payment Button */}
      <button
        onClick={openModal}
        className="w-full inline-flex items-center justify-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium"
      >
        <CreditCard className="h-5 w-5 mr-2" />
        Bayar Sekarang
      </button>

      {/* Payment Modal */}
      {showModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg shadow-xl max-w-md w-full">
            {/* Modal Header */}
            <div className="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 rounded-t-lg">
              <h2 className="text-xl font-bold text-white">Pembayaran Invoice</h2>
            </div>

            {/* Modal Content */}
            <div className="p-6">
              {success ? (
                // Success State
                <div className="text-center py-8">
                  <CheckCircle className="h-16 w-16 text-green-600 mx-auto mb-4" />
                  <h3 className="text-xl font-bold text-gray-900 mb-2">
                    Pembayaran Berhasil!
                  </h3>
                  <p className="text-gray-600">
                    Invoice Anda telah dibayar. Terima kasih!
                  </p>
                </div>
              ) : (
                <>
                  {/* Amount Display */}
                  <div className="bg-gray-50 rounded-lg p-4 mb-6">
                    <p className="text-sm text-gray-600 mb-1">Total Pembayaran</p>
                    <p className="text-3xl font-bold text-gray-900">
                      {formatCurrency(amount)}
                    </p>
                  </div>

                  {/* Payment Method Selection */}
                  <div className="mb-6">
                    <label className="block text-sm font-medium text-gray-700 mb-3">
                      Pilih Metode Pembayaran
                    </label>
                    <div className="space-y-3">
                      {/* Bank Transfer */}
                      <label className="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                        <input
                          type="radio"
                          name="payment_method"
                          value="bank_transfer"
                          checked={paymentMethod === 'bank_transfer'}
                          onChange={(e) => setPaymentMethod(e.target.value)}
                          className="h-4 w-4 text-green-600 focus:ring-green-500"
                        />
                        <div className="ml-3 flex-1">
                          <p className="font-medium text-gray-900">Transfer Bank</p>
                          <p className="text-sm text-gray-500">
                            Transfer ke rekening bank kami
                          </p>
                        </div>
                      </label>

                      {/* E-Wallet */}
                      <label className="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                        <input
                          type="radio"
                          name="payment_method"
                          value="e_wallet"
                          checked={paymentMethod === 'e_wallet'}
                          onChange={(e) => setPaymentMethod(e.target.value)}
                          className="h-4 w-4 text-green-600 focus:ring-green-500"
                        />
                        <div className="ml-3 flex-1">
                          <p className="font-medium text-gray-900">E-Wallet</p>
                          <p className="text-sm text-gray-500">
                            GoPay, OVO, Dana, LinkAja
                          </p>
                        </div>
                      </label>

                      {/* Credit Card */}
                      <label className="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                        <input
                          type="radio"
                          name="payment_method"
                          value="credit_card"
                          checked={paymentMethod === 'credit_card'}
                          onChange={(e) => setPaymentMethod(e.target.value)}
                          className="h-4 w-4 text-green-600 focus:ring-green-500"
                        />
                        <div className="ml-3 flex-1">
                          <p className="font-medium text-gray-900">Kartu Kredit/Debit</p>
                          <p className="text-sm text-gray-500">
                            Visa, Mastercard, JCB
                          </p>
                        </div>
                      </label>

                      {/* Virtual Account */}
                      <label className="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                        <input
                          type="radio"
                          name="payment_method"
                          value="virtual_account"
                          checked={paymentMethod === 'virtual_account'}
                          onChange={(e) => setPaymentMethod(e.target.value)}
                          className="h-4 w-4 text-green-600 focus:ring-green-500"
                        />
                        <div className="ml-3 flex-1">
                          <p className="font-medium text-gray-900">Virtual Account</p>
                          <p className="text-sm text-gray-500">
                            BCA, Mandiri, BNI, BRI
                          </p>
                        </div>
                      </label>
                    </div>
                  </div>

                  {/* Error Message */}
                  {error && (
                    <div className="mb-4 bg-red-50 border border-red-200 rounded-lg p-4 flex items-start">
                      <AlertCircle className="h-5 w-5 text-red-600 mt-0.5 mr-3 flex-shrink-0" />
                      <div className="flex-1">
                        <h3 className="text-sm font-medium text-red-800">Pembayaran Gagal</h3>
                        <p className="text-sm text-red-700 mt-1">{error}</p>
                      </div>
                    </div>
                  )}

                  {/* Payment Info */}
                  <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <p className="text-sm text-blue-800">
                      <strong>Catatan:</strong> Setelah melakukan pembayaran, invoice Anda akan 
                      diverifikasi dalam 1x24 jam. Anda akan menerima konfirmasi melalui email.
                    </p>
                  </div>

                  {/* Action Buttons */}
                  <div className="flex space-x-3">
                    <button
                      onClick={closeModal}
                      disabled={processing}
                      className="flex-1 px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      Batal
                    </button>
                    <button
                      onClick={handlePayment}
                      disabled={processing}
                      className="flex-1 inline-flex items-center justify-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      {processing ? (
                        <>
                          <Loader2 className="h-5 w-5 mr-2 animate-spin" />
                          Memproses...
                        </>
                      ) : (
                        <>
                          <CreditCard className="h-5 w-5 mr-2" />
                          Konfirmasi Pembayaran
                        </>
                      )}
                    </button>
                  </div>
                </>
              )}
            </div>
          </div>
        </div>
      )}
    </>
  );
};

export default PaymentButton;
