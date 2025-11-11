<?php

namespace App\Models;

use CodeIgniter\Model;

class InvoiceModel extends Model
{
    protected $table            = 'invoice';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'transaksi_id',
        'nomor_invoice',
        'tanggal_invoice',
        'tanggal_jatuh_tempo',
        'total_tagihan',
        'status_pembayaran',
        'tanggal_pembayaran',
        'metode_pembayaran',
        'bukti_pembayaran'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules    = [];
    protected $validationMessages = [];
    protected $skipValidation     = false;

    /**
     * Get user invoices with transaction details
     */
    public function getUserInvoices($userId, $status = null)
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table)
            ->select('invoice.*, transaksi_layanan.kode_transaksi, layanan.nama_layanan')
            ->join('transaksi_layanan', 'transaksi_layanan.id = invoice.transaksi_id')
            ->join('layanan', 'layanan.id = transaksi_layanan.layanan_id')
            ->where('invoice.user_id', $userId);
        
        if ($status) {
            $builder->where('invoice.status_pembayaran', $status);
        }
        
        return $builder->orderBy('invoice.created_at', 'DESC')
                      ->get()
                      ->getResultArray();
    }

    /**
     * Get invoice by ID with details
     */
    public function getInvoiceById($id)
    {
        $db = \Config\Database::connect();
        
        return $db->table($this->table)
            ->select('invoice.*, transaksi_layanan.*, layanan.nama_layanan, layanan.tipe_layanan, users.nama_lengkap, users.nama_perusahaan, users.alamat_perusahaan, users.telepon, users.email')
            ->join('transaksi_layanan', 'transaksi_layanan.id = invoice.transaksi_id')
            ->join('layanan', 'layanan.id = transaksi_layanan.layanan_id')
            ->join('users', 'users.id = invoice.user_id')
            ->where('invoice.id', $id)
            ->get()
            ->getRowArray();
    }

    /**
     * Get pending invoices for user
     */
    public function getPendingInvoices($userId)
    {
        return $this->where('user_id', $userId)
                    ->where('status_pembayaran', 'belum_bayar')
                    ->orderBy('tanggal_jatuh_tempo', 'ASC')
                    ->findAll();
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid($id, $metodePembayaran = null, $buktiPembayaran = null)
    {
        $data = [
            'status_pembayaran' => 'lunas',
            'tanggal_pembayaran' => date('Y-m-d H:i:s')
        ];
        
        if ($metodePembayaran) {
            $data['metode_pembayaran'] = $metodePembayaran;
        }
        
        if ($buktiPembayaran) {
            $data['bukti_pembayaran'] = $buktiPembayaran;
        }
        
        return $this->update($id, $data);
    }

    /**
     * Generate unique invoice number
     */
    public function generateInvoiceNumber()
    {
        do {
            $number = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        } while ($this->where('nomor_invoice', $number)->countAllResults() > 0);
        
        return $number;
    }

    /**
     * Get all invoices (for admin)
     */
    public function getAllInvoices($limit = null, $offset = null)
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table)
            ->select('invoice.*, users.nama_lengkap, users.nama_perusahaan, transaksi_layanan.kode_transaksi')
            ->join('users', 'users.id = invoice.user_id')
            ->join('transaksi_layanan', 'transaksi_layanan.id = invoice.transaksi_id')
            ->orderBy('invoice.created_at', 'DESC');
        
        if ($limit) {
            $builder->limit($limit, $offset ?? 0);
        }
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get overdue invoices
     */
    public function getOverdueInvoices()
    {
        return $this->where('status_pembayaran', 'belum_bayar')
                    ->where('tanggal_jatuh_tempo <', date('Y-m-d'))
                    ->orderBy('tanggal_jatuh_tempo', 'ASC')
                    ->findAll();
    }
}
