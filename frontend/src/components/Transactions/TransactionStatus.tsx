import React from 'react';
import { CheckCircle, Clock, XCircle, AlertCircle } from 'lucide-react';

interface TransactionStatusProps {
  status: string;
  size?: 'sm' | 'md' | 'lg';
}

const TransactionStatus: React.FC<TransactionStatusProps> = ({ status, size = 'md' }) => {
  const getStatusConfig = (status: string) => {
    const normalizedStatus = status.toLowerCase();
    
    switch (normalizedStatus) {
      case 'completed':
      case 'selesai':
      case 'success':
        return {
          label: 'Selesai',
          icon: CheckCircle,
          bgColor: 'bg-green-100',
          textColor: 'text-green-800',
          iconColor: 'text-green-600',
        };
      case 'pending':
      case 'menunggu':
        return {
          label: 'Menunggu',
          icon: Clock,
          bgColor: 'bg-yellow-100',
          textColor: 'text-yellow-800',
          iconColor: 'text-yellow-600',
        };
      case 'processing':
      case 'diproses':
        return {
          label: 'Diproses',
          icon: AlertCircle,
          bgColor: 'bg-blue-100',
          textColor: 'text-blue-800',
          iconColor: 'text-blue-600',
        };
      case 'cancelled':
      case 'dibatalkan':
      case 'failed':
      case 'gagal':
        return {
          label: 'Dibatalkan',
          icon: XCircle,
          bgColor: 'bg-red-100',
          textColor: 'text-red-800',
          iconColor: 'text-red-600',
        };
      default:
        return {
          label: status,
          icon: AlertCircle,
          bgColor: 'bg-gray-100',
          textColor: 'text-gray-800',
          iconColor: 'text-gray-600',
        };
    }
  };

  const config = getStatusConfig(status);
  const Icon = config.icon;

  const sizeClasses = {
    sm: {
      container: 'px-2 py-1 text-xs',
      icon: 'h-3 w-3',
    },
    md: {
      container: 'px-3 py-1.5 text-sm',
      icon: 'h-4 w-4',
    },
    lg: {
      container: 'px-4 py-2 text-base',
      icon: 'h-5 w-5',
    },
  };

  return (
    <span
      className={`inline-flex items-center gap-1.5 rounded-full font-medium ${config.bgColor} ${config.textColor} ${sizeClasses[size].container}`}
    >
      <Icon className={`${config.iconColor} ${sizeClasses[size].icon}`} />
      {config.label}
    </span>
  );
};

export default TransactionStatus;
