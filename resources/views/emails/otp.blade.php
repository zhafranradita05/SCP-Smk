<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kode OTP Reset Password</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #FAF7F2; margin: 0; padding: 30px; color: #2C1D11;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 520px; background-color: #ffffff; border-radius: 20px; border: 1px solid #E8E0D5; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <!-- Header -->
        <tr>
            <td align="center" style="background-color: #1E3A8A; padding: 25px 20px;">
                <img src="{{ asset('images/logo.png') }}" alt="Logo SCP" width="60" height="60" style="display: block; margin: 0 auto 12px; border-radius: 50%; background: #ffffff;">
                <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: bold; letter-spacing: 1px;">SISTEM PRESENSI SCP</h1>
                <p style="color: #DBEAFE; margin: 5px 0 0 0; font-size: 12px;">School Class Progress (SCP)</p>
            </td>
        </tr>
        
        <!-- Content -->
        <tr>
            <td style="padding: 30px 25px;">
                <h2 style="font-size: 18px; color: #2C1D11; margin-top: 0;">Halo, {{ $name }}!</h2>
                <p style="font-size: 14px; line-height: 1.6; color: #5D4037;">
                    Kami menerima permintaan untuk mereset kata sandi akun Anda. Gunakan kode verifikasi (OTP) berikut untuk melanjutkan:
                </p>

                <!-- Kotak Kode OTP -->
                <div style="background-color: #FAF7F2; border: 2px dashed #B57338; border-radius: 14px; text-align: center; padding: 18px; margin: 25px 0;">
                    <span style="font-size: 32px; font-weight: 800; letter-spacing: 10px; color: #5D4037; font-family: monospace;">{{ $otp }}</span>
                </div>

                <p style="font-size: 13px; line-height: 1.5; color: #8D6E63; margin-bottom: 0;">
                    ⏱️ Kode ini hanya berlaku selama <strong>10 menit</strong>.<br>
                    ⚠️ Jangan berikan kode ini kepada siapa pun, termasuk pihak sekolah.
                </p>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="background-color: #FAF7F2; padding: 15px; text-align: center; border-top: 1px solid #E8E0D5;">
                <p style="font-size: 11px; color: #A89F91; margin: 0;">
                    Jika Anda tidak merasa meminta reset password, abaikan email ini.<br>
                    &copy; {{ date('Y') }} Sistem Presensi SCP. Seluruh hak cipta dilindungi.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
