import React, { useState } from 'react';
import { useForm } from 'react-hook-form';
import { yupResolver } from '@hookform/resolvers/yup';
import * as yup from 'yup';
import { useAuth } from '../contexts/AuthContext';
import { useNavigate, Link } from 'react-router-dom';
import { Mail, Lock, User, Building, MapPin, Phone, AlertCircle, Loader2, CheckCircle, XCircle } from 'lucide-react';

interface RegisterFormData {
  username: string;
  email: string;
  password: string;
  confirmPassword: string;
  nama_perusahaan: string;
  alamat_perusahaan: string;
  no_telp: string;
  termsAccepted: boolean;
}

const passwordSchema = yup.string()
  .required('Password wajib diisi')
  .min(8, 'Password minimal 8 karakter')
  .matches(/[a-z]/, 'Password harus mengandung huruf kecil')
  .matches(/[A-Z]/, 'Password harus mengandung huruf besar')
  .matches(/[0-9]/, 'Password harus mengandung angka')
  .matches(/[!@#$%^&*(),.?":{}|<>]/, 'Password harus mengandung karakter khusus');

const registerSchema = yup.object().shape({
  username: yup.string()
    .required('Username wajib diisi')
    .min(3, 'Username minimal 3 karakter')
    .max(50, 'Username maksimal 50 karakter'),
  email: yup.string()
    .required('Email wajib diisi')
    .email('Format email tidak valid'),
  password: passwordSchema,
  confirmPassword: yup.string()
    .required('Konfirmasi password wajib diisi')
    .oneOf([yup.ref('password')], 'Password tidak cocok'),
  nama_perusahaan: yup.string()
    .required('Nama perusahaan wajib diisi')
    .min(3, 'Nama perusahaan minimal 3 karakter'),
  alamat_perusahaan: yup.string()
    .required('Alamat perusahaan wajib diisi')
    .min(10, 'Alamat perusahaan minimal 10 karakter'),
  no_telp: yup.string()
    .required('Nomor telepon wajib diisi')
    .matches(/^[0-9+\-() ]+$/, 'Format nomor telepon tidak valid')
    .min(10, 'Nomor telepon minimal 10 digit'),
  termsAccepted: yup.boolean()
    .required('Anda harus menyetujui syarat dan ketentuan')
    .oneOf([true], 'Anda harus menyetujui syarat dan ketentuan')
});

const Register: React.FC = () => {
  const { register: registerUser, error, clearError } = useAuth();
  const navigate = useNavigate();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');
  const [passwordStrength, setPasswordStrength] = useState({ score: 0, label: '', color: '' });

  const { register, handleSubmit, watch, formState: { errors } } = useForm<RegisterFormData>({
    resolver: yupResolver(registerSchema),
    mode: 'onChange'
  });

  const password = watch('password', '');

  React.useEffect(() => {
    calculatePasswordStrength(password);
  }, [password]);

  const calculatePasswordStrength = (pwd: string) => {
    if (!pwd) {
      setPasswordStrength({ score: 0, label: '', color: '' });
      return;
    }

    let score = 0;
    if (pwd.length >= 8) score++;
    if (pwd.length >= 12) score++;
    if (/[a-z]/.test(pwd)) score++;
    if (/[A-Z]/.test(pwd)) score++;
    if (/[0-9]/.test(pwd)) score++;
    if (/[!@#$%^&*(),.?":{}|<>]/.test(pwd)) score++;

    let label = '';
    let color = '';

    if (score <= 2) {
      label = 'Lemah';
      color = 'bg-red-500';
    } else if (score <= 4) {
      label = 'Sedang';
      color = 'bg-yellow-500';
    } else {
      label = 'Kuat';
      color = 'bg-green-500';
    }

    setPasswordStrength({ score, label, color });
  };

  const onSubmit = async (data: RegisterFormData) => {
    try {
      setIsSubmitting(true);
      clearError();
      setSuccessMessage('');

      const { confirmPassword, termsAccepted, ...registerData } = data;
      
      await registerUser(registerData);
      
      setSuccessMessage('Registrasi berhasil! Silakan cek email Anda untuk verifikasi.');
      
      setTimeout(() => {
        navigate('/login');
      }, 2000);
    } catch (error: any) {
      console.error('Registration failed:', error);
      setIsSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-green-50 to-blue-50 py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-2xl w-full space-y-8">
        <div className="text-center">
          <div className="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-green-100 mb-4">
            <User className="h-8 w-8 text-green-600" />
          </div>
          <h2 className="text-3xl font-bold text-gray-900 mb-2">
            Daftar Akun Baru
          </h2>
          <p className="text-gray-600">
            Bergabunglah dengan Sistem Manajemen Limbah Industri
          </p>
        </div>

        <div className="bg-white rounded-lg shadow-lg p-8">
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
            {error && (
              <div className="flex items-center p-4 bg-red-50 border border-red-200 rounded-lg">
                <AlertCircle className="h-5 w-5 text-red-500 mr-2 flex-shrink-0" />
                <p className="text-red-700 text-sm">{error}</p>
              </div>
            )}

            {successMessage && (
              <div className="flex items-center p-4 bg-green-50 border border-green-200 rounded-lg">
                <CheckCircle className="h-5 w-5 text-green-500 mr-2 flex-shrink-0" />
                <p className="text-green-700 text-sm">{successMessage}</p>
              </div>
            )}

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label htmlFor="username" className="block text-sm font-medium text-gray-700 mb-2">
                  Username
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <User className="h-5 w-5 text-gray-400" />
                  </div>
                  <input
                    {...register('username')}
                    type="text"
                    className="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                    placeholder="Masukkan username"
                  />
                </div>
                {errors.username && (
                  <p className="mt-1 text-sm text-red-600">{errors.username.message}</p>
                )}
              </div>

              <div>
                <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-2">
                  Email
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <Mail className="h-5 w-5 text-gray-400" />
                  </div>
                  <input
                    {...register('email')}
                    type="email"
                    className="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                    placeholder="Masukkan email"
                  />
                </div>
                {errors.email && (
                  <p className="mt-1 text-sm text-red-600">{errors.email.message}</p>
                )}
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-2">
                  Password
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <Lock className="h-5 w-5 text-gray-400" />
                  </div>
                  <input
                    {...register('password')}
                    type="password"
                    className="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                    placeholder="Masukkan password"
                  />
                </div>
                {password && (
                  <div className="mt-2">
                    <div className="flex items-center justify-between mb-1">
                      <span className="text-xs text-gray-600">Kekuatan Password:</span>
                      <span className={`text-xs font-medium ${
                        passwordStrength.score <= 2 ? 'text-red-600' : 
                        passwordStrength.score <= 4 ? 'text-yellow-600' : 
                        'text-green-600'
                      }`}>
                        {passwordStrength.label}
                      </span>
                    </div>
                    <div className="w-full bg-gray-200 rounded-full h-2">
                      <div 
                        className={`h-2 rounded-full transition-all ${passwordStrength.color}`}
                        style={{ width: `${(passwordStrength.score / 6) * 100}%` }}
                      />
                    </div>
                  </div>
                )}
                {errors.password && (
                  <p className="mt-1 text-sm text-red-600">{errors.password.message}</p>
                )}
              </div>

              <div>
                <label htmlFor="confirmPassword" className="block text-sm font-medium text-gray-700 mb-2">
                  Konfirmasi Password
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <Lock className="h-5 w-5 text-gray-400" />
                  </div>
                  <input
                    {...register('confirmPassword')}
                    type="password"
                    className="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                    placeholder="Konfirmasi password"
                  />
                </div>
                {errors.confirmPassword && (
                  <p className="mt-1 text-sm text-red-600">{errors.confirmPassword.message}</p>
                )}
              </div>
            </div>

            <div>
              <label htmlFor="nama_perusahaan" className="block text-sm font-medium text-gray-700 mb-2">
                Nama Perusahaan
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Building className="h-5 w-5 text-gray-400" />
                </div>
                <input
                  {...register('nama_perusahaan')}
                  type="text"
                  className="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                  placeholder="Masukkan nama perusahaan"
                />
              </div>
              {errors.nama_perusahaan && (
                <p className="mt-1 text-sm text-red-600">{errors.nama_perusahaan.message}</p>
              )}
            </div>

            <div>
              <label htmlFor="alamat_perusahaan" className="block text-sm font-medium text-gray-700 mb-2">
                Alamat Perusahaan
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-start pt-3 pointer-events-none">
                  <MapPin className="h-5 w-5 text-gray-400" />
                </div>
                <textarea
                  {...register('alamat_perusahaan')}
                  rows={3}
                  className="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                  placeholder="Masukkan alamat lengkap perusahaan"
                />
              </div>
              {errors.alamat_perusahaan && (
                <p className="mt-1 text-sm text-red-600">{errors.alamat_perusahaan.message}</p>
              )}
            </div>

            <div>
              <label htmlFor="no_telp" className="block text-sm font-medium text-gray-700 mb-2">
                Nomor Telepon
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Phone className="h-5 w-5 text-gray-400" />
                </div>
                <input
                  {...register('no_telp')}
                  type="tel"
                  className="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                  placeholder="Contoh: 021-12345678"
                />
              </div>
              {errors.no_telp && (
                <p className="mt-1 text-sm text-red-600">{errors.no_telp.message}</p>
              )}
            </div>

            <div className="flex items-start">
              <div className="flex items-center h-5">
                <input
                  {...register('termsAccepted')}
                  type="checkbox"
                  className="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                />
              </div>
              <div className="ml-3 text-sm">
                <label htmlFor="termsAccepted" className="text-gray-700">
                  Saya menyetujui{' '}
                  <a href="#" className="text-green-600 hover:text-green-500 font-medium">
                    Syarat dan Ketentuan
                  </a>{' '}
                  serta{' '}
                  <a href="#" className="text-green-600 hover:text-green-500 font-medium">
                    Kebijakan Privasi
                  </a>
                </label>
                {errors.termsAccepted && (
                  <p className="mt-1 text-sm text-red-600">{errors.termsAccepted.message}</p>
                )}
              </div>
            </div>

            <div>
              <button
                type="submit"
                disabled={isSubmitting}
                className="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                {isSubmitting ? (
                  <>
                    <Loader2 className="animate-spin -ml-1 mr-2 h-4 w-4" />
                    Memproses...
                  </>
                ) : (
                  'Daftar Sekarang'
                )}
              </button>
            </div>

            <div className="text-center">
              <p className="text-sm text-gray-600">
                Sudah memiliki akun?{' '}
                <Link to="/login" className="font-medium text-green-600 hover:text-green-500">
                  Masuk di sini
                </Link>
              </p>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
};

export default Register;
