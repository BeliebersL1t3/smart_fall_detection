<!DOCTYPE html>
<html>
<head>
    <style>
        .container { font-family: sans-serif; padding: 20px; border: 1px solid #eee; border-radius: 10px; max-width: 600px; margin: auto; }
        .header { background: #3B82F6; color: white; padding: 15px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 20px; line-height: 1.6; }
        .data-box { background: #EFF6FF; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 5px solid #3B82F6; }
        .btn { background: #3B82F6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold; }
        .warning-text { color: #D97706; font-weight: bold; font-size: 1.1em; text-align: center;}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚨 PANGGILAN SOS MANUAL</h1>
        </div>
        <div class="content">
            <p>Halo Caregiver,</p>
            <p class="warning-text">Lansia yang Anda pantau baru saja menekan tombol panik (SOS) pada perangkat mereka.</p>
            
            <div class="data-box">
                <p><strong>Lokasi Perangkat:</strong> {{ $location }}</p>
                <p><strong>Waktu Ditekan:</strong> {{ $event->occurred_at->format('d M Y, H:i:s') }}</p>
                <p><strong>Keterangan:</strong> Tidak ada benturan yang terdeteksi, ini adalah panggilan sadar dari pengguna.</p>
            </div>

            <p>Mohon segera hubungi pengguna atau periksa kondisi mereka di lokasi.</p>
            <div style="text-align: center; margin-top: 25px;">
                <a href="{{ route('dashboard') }}" class="btn">Buka Dashboard Sekarang</a>
            </div>
        </div>
    </div>
</body>
</html>