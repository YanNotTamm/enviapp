# 📧 Setup Email Verification

## Masalah
Email verifikasi tidak terkirim setelah register karena SMTP belum dikonfigurasi.

## Solusi

### Opsi 1: Disable Email Verification (RECOMMENDED untuk Development)

Untuk development, lebih mudah disable email verification dulu:

1. **Auto-verify user saat register**
2. **Skip email sending**
3. **User langsung bisa login**

### Opsi 2: Setup Gmail SMTP (untuk Production)

Jika ingin email verification benar-benar jalan:

#### Step 1: Buat Gmail App Password

1. Login ke Gmail account yang akan digunakan
2. Buka: https://myaccount.google.com/security
3. Enable "2-Step Verification" (jika belum)
4. Buka: https://myaccount.google.com/apppasswords
5. Pilih "Mail" dan "Other (Custom name)"
6. Ketik: "Envindo System"
7. Klik "Generate"
8. Copy 16-digit password yang muncul

#### Step 2: Update .env.production

Edit file: `backend/.env.production`

```env
#--------------------------------------------------------------------
# EMAIL
#--------------------------------------------------------------------
email.fromEmail = noreply@envirometrolestari.com
email.fromName = Envindo System
email.SMTPHost = smtp.gmail.com
email.SMTPUser = your-actual-gmail@gmail.com
email.SMTPPass = your-16-digit-app-password
email.SMTPPort = 587
email.SMTPCrypto = tls
```

Ganti:
- `your-actual-gmail@gmail.com` → Email Gmail Anda
- `your-16-digit-app-password` → App Password dari Step 1

#### Step 3: Copy ke deployment-ready

```bash
copy backend\.env.production deployment-ready\backend\.env.production
```

#### Step 4: Test Email

Akses: https://dev.envirometrolestari.com/api/test-email

Jika berhasil, akan muncul: "Email sent successfully"

---

## Implementasi Opsi 1 (Auto-Verify)

Saya akan implementasikan Opsi 1 untuk Anda karena lebih praktis untuk development.

### Perubahan yang Akan Dilakukan:

1. ✅ Set `email_verified = true` saat register
2. ✅ Skip email sending (tidak error jika gagal)
3. ✅ User langsung bisa login tanpa verifikasi
4. ✅ Tetap simpan verification token (untuk future use)

### Cara Aktifkan Email Nanti:

Ketika sudah siap production:
1. Setup Gmail App Password (Opsi 2)
2. Update `.env.production`
3. Ubah `email_verified = false` di AuthController
4. Email verification akan aktif otomatis

---

## Testing

### Setelah Implementasi Opsi 1:

1. Register user baru
2. Tidak perlu cek email
3. Langsung login dengan email & password
4. Berhasil masuk dashboard

### Setelah Setup Opsi 2:

1. Register user baru
2. Cek inbox email
3. Klik link verifikasi
4. Baru bisa login

---

## Troubleshooting Email (Opsi 2)

### Error: "Authentication failed"
- Pastikan 2-Step Verification aktif
- Gunakan App Password, bukan password Gmail biasa
- Pastikan tidak ada spasi di App Password

### Error: "Could not connect to SMTP host"
- Cek firewall/antivirus
- Pastikan port 587 tidak diblok
- Coba ganti ke port 465 dengan `SMTPCrypto = ssl`

### Email masuk ke Spam
- Tambahkan SPF record di DNS
- Gunakan domain email yang sama dengan website
- Atau gunakan email service seperti SendGrid/Mailgun

---

## Rekomendasi

**Untuk Development (Sekarang):**
- ✅ Gunakan Opsi 1 (Auto-verify)
- ✅ Cepat dan mudah testing
- ✅ Tidak perlu setup email

**Untuk Production (Nanti):**
- ✅ Gunakan Opsi 2 (Gmail SMTP)
- ✅ Atau gunakan email service profesional
- ✅ Setup SPF/DKIM untuk deliverability

---

## Status

🔄 **Implementing Opsi 1** - Auto-verify untuk development
 