<!DOCTYPE html>
<html>
<head>
    <style>
        .container { font-family: sans-serif; padding: 20px; border: 1px solid #eee; border-radius: 10px; }
        .header { background: #FF4444; color: white; padding: 15px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 20px; line-height: 1.6; }
        .data-box { background: #f9f9f9; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .btn { background: #8A2BE2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>PERINGATAN DARURAT</h1>
        </div>
        <div class="content">
            <p>Halo Caregiver,</p>
            <p>Sistem kami mendeteksi adanya kejadian darurat pada perangkat yang Anda monitor.</p>
            
            <div class="data-box">
                <p><strong>Jenis Kejadian:</strong> {{ $event->type === 'auto_fall' ? 'Deteksi Jatuh Otomatis' : 'Sinyal SOS Manual' }}</p>
                <p><strong>Lokasi:</strong> {{ $location }}</p>
                <p><strong>Waktu:</strong> {{ $event->occurred_at->format('d M Y, H:i:s') }}</p>
                @if($event->acceleration_peak)
                    <p><strong>Magnitude Dampak:</strong> {{ number_format($event->acceleration_peak, 2) }} G</p>
                @endif
            </div>

            <p>Segera periksa kondisi lansia di lokasi tersebut.</p>
            <a href="{{ route('dashboard') }}" class="btn">Buka Dashboard</a>
        </div>
    </div>
</body>
</html>