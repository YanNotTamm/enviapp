import React, { useEffect, useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { CheckCircle, XCircle, Loader2, Mail } from 'lucide-react';
import axios from 'axios';

const EmailVerification: React.FC = () => {
  const { token } = useParams<{ token: string }>();
  const navigate = useNavigate();
  const [status, setStatus] = useState<'loading' | 'success' | 'error'>('loading');
  const [message, setMessage] = useState('');

  useEffect(() => {
    const verifyEmail = async () => {
      if (!token) {
        setStatus('error');
        setMessage('Invalid verification link');
        return;
      }

      try {
        const apiUrl = process.env.REACT_APP_API_URL || 'http://localhost:8080/api';
        const response = await axios.get(`${apiUrl}/auth/verify/${token}`);
        
        if (response.data.status === 'success') {
          setStatus('success');
          setMessage(response.data.message || 'Email verified successfully!');
          
          // Redirect to login after 3 seconds
          setTimeout(() => {
            navigate('/login');
          }, 3000);
        } else {
          setStatus('error');
          setMessage(response.data.message || 'Verification failed');
        }
      } catch (error: any) {
        setStatus('error');
        if (error.response?.data?.message) {
          setMessage(error.response.data.message);
        } else {
          setMessage('Failed to verify email. Please try again or contact support.');
        }
      }
    };

    verifyEmail();
  }, [token, navigate]);

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-green-50 to-blue-50 py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-md w-full space-y-8">
        <div className="text-center">
          <div className="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-white shadow-lg mb-4">
            <Mail className="h-8 w-8 text-green-600" />
          </div>
          <h2 className="text-3xl font-bold text-gray-900 mb-2">
            Email Verification
          </h2>
        </div>

        <div className="bg-white rounded-lg shadow-lg p-8">
          {status === 'loading' && (
            <div className="text-center">
              <Loader2 className="h-12 w-12 text-green-600 animate-spin mx-auto mb-4" />
              <p className="text-gray-600">Verifying your email address...</p>
              <p className="text-sm text-gray-500 mt-2">Please wait a moment</p>
            </div>
          )}

          {status === 'success' && (
            <div className="text-center">
              <div className="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-green-100 mb-4">
                <CheckCircle className="h-10 w-10 text-green-600" />
              </div>
              <h3 className="text-xl font-semibold text-gray-900 mb-2">
                Verification Successful!
              </h3>
              <p className="text-gray-600 mb-4">{message}</p>
              <p className="text-sm text-gray-500 mb-6">
                Redirecting to login page in 3 seconds...
              </p>
              <Link
                to="/login"
                className="inline-block px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
              >
                Go to Login Now
              </Link>
            </div>
          )}

          {status === 'error' && (
            <div className="text-center">
              <div className="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-red-100 mb-4">
                <XCircle className="h-10 w-10 text-red-600" />
              </div>
              <h3 className="text-xl font-semibold text-gray-900 mb-2">
                Verification Failed
              </h3>
              <p className="text-gray-600 mb-6">{message}</p>
              <div className="space-y-3">
                <Link
                  to="/login"
                  className="block w-full px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                >
                  Go to Login
                </Link>
                <Link
                  to="/register"
                  className="block w-full px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
                >
                  Register Again
                </Link>
              </div>
            </div>
          )}
        </div>

        <p className="text-center text-sm text-gray-600">
          Need help?{' '}
          <a href="mailto:support@envirometrolestari.com" className="text-green-600 hover:text-green-500 font-medium">
            Contact Support
          </a>
        </p>
      </div>
    </div>
  );
};

export default EmailVerification;
 