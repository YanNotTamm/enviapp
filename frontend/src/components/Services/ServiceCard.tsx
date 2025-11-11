import React from 'react';
import { Link } from 'react-router-dom';
import { Clock, DollarSign, CheckCircle, ArrowRight } from 'lucide-react';
import { Service } from './ServiceList';

interface ServiceCardProps {
  service: Service;
}

const ServiceCard: React.FC<ServiceCardProps> = ({ service }) => {
  const features = service.fitur ? service.fitur.split(',').map(f => f.trim()) : [];

  return (
    <div className="bg-white rounded-lg shadow hover:shadow-lg transition-shadow border border-gray-200 overflow-hidden">
      {/* Header */}
      <div className="bg-gradient-to-r from-green-500 to-green-600 p-6 text-white">
        <h3 className="text-xl font-bold mb-2">{service.nama_layanan}</h3>
        <div className="flex items-center justify-between">
          <div className="flex items-center text-green-50">
            <DollarSign className="h-4 w-4 mr-1" />
            <span className="text-2xl font-bold">
              {service.harga.toLocaleString('id-ID')}
            </span>
          </div>
          <div className="flex items-center text-green-50 text-sm">
            <Clock className="h-4 w-4 mr-1" />
            <span>{service.durasi_hari} hari</span>
          </div>
        </div>
      </div>

      {/* Body */}
      <div className="p-6">
        <p className="text-gray-600 text-sm mb-4 line-clamp-3">
          {service.deskripsi}
        </p>

        {/* Features */}
        {features.length > 0 && (
          <div className="mb-4">
            <h4 className="text-sm font-semibold text-gray-900 mb-2">Fitur:</h4>
            <ul className="space-y-2">
              {features.slice(0, 3).map((feature, index) => (
                <li key={index} className="flex items-start text-sm text-gray-600">
                  <CheckCircle className="h-4 w-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" />
                  <span>{feature}</span>
                </li>
              ))}
              {features.length > 3 && (
                <li className="text-sm text-gray-500 italic">
                  +{features.length - 3} fitur lainnya
                </li>
              )}
            </ul>
          </div>
        )}

        {/* Status Badge */}
        <div className="mb-4">
          <span
            className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${
              service.status === 'active'
                ? 'bg-green-100 text-green-800'
                : 'bg-gray-100 text-gray-800'
            }`}
          >
            {service.status === 'active' ? 'Tersedia' : 'Tidak Tersedia'}
          </span>
        </div>

        {/* Action Button */}
        <Link
          to={`/services/subscribe/${service.id}`}
          className={`w-full flex items-center justify-center px-4 py-2 rounded-lg font-medium transition-colors ${
            service.status === 'active'
              ? 'bg-green-600 text-white hover:bg-green-700'
              : 'bg-gray-300 text-gray-500 cursor-not-allowed'
          }`}
          onClick={(e) => service.status !== 'active' && e.preventDefault()}
        >
          {service.status === 'active' ? (
            <>
              Langganan Sekarang
              <ArrowRight className="h-4 w-4 ml-2" />
            </>
          ) : (
            'Tidak Tersedia'
          )}
        </Link>
      </div>
    </div>
  );
};

export default ServiceCard;
