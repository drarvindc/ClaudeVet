{{-- resources/views/patient/letterhead.blade.php - Updated to use stored images --}}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Letterhead - {{ $pet->unique_id }}</title>
    <style>
        @page {
            size: A4;
            margin: 20mm;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        
        .letterhead-header {
            text-align: center;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .clinic-name {
            font-size: 24px;
            font-weight: bold;
            color: #0066cc;
            margin-bottom: 5px;
        }
        
        .clinic-details {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .codes-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
        
        .qr-code, .barcode {
            text-align: center;
        }
        
        .qr-code img, .barcode img {
            max-width: 120px;
            max-height: 120px;
            border: 1px solid #ccc;
            padding: 5px;
            background: white;
        }
        
        .barcode img {
            max-width: 200px;
            max-height: 60px;
        }
        
        .code-label {
            font-size: 10px;
            margin-top: 5px;
            color: #666;
        }
        
        .patient-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .info-item {
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: bold;
            color: #333;
            margin-right: 10px;
        }
        
        .info-value {
            color: #666;
        }
        
        .provisional-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .prescription-area {
            min-height: 400px;
            border: 2px dashed #ccc;
            padding: 20px;
            margin-bottom: 30px;
            background: #fafafa;
        }
        
        .prescription-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .vital-signs {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .vital-item {
            font-size: 12px;
            color: #666;
        }
        
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        
        .signature-box {
            text-align: center;
            min-width: 200px;
        }
        
        .signature-line {
            border-bottom: 1px solid #333;
            margin-bottom: 5px;
            height: 30px;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            
            body {
                background: white;
            }
        }
    </style>
</head>
<body>
    <!-- Letterhead Header -->
    <div class="letterhead-header">
        <div class="clinic-name">MetroVet Clinic</div>
        <div class="clinic-details">
            304, Popular Nagar Shopping Complex, Warje, Pune<br>
            Phone: 7020241565 | Email: info@metrovet.in
        </div>
        <div class="clinic-details" style="font-size: 10px; margin-top: 10px;">
            Date: {{ date('d/m/Y') }} | Time: {{ date('H:i') }}
        </div>
    </div>

    <!-- QR Code and Barcode Section -->
    <div class="codes-section">
        <div class="qr-code">
            @if($qr_success && $qr_url)
                <img src="{{ asset('storage/' . ltrim($qr_url, '/storage/')) }}" alt="QR Code">
            @else
                <div style="width: 120px; height: 120px; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                    <span style="font-size: 10px; color: #666;">QR Code<br>{{ $pet->unique_id }}</span>
                </div>
            @endif
            <div class="code-label">QR Code</div>
        </div>
        
        <div class="patient-summary" style="flex: 1; text-align: center; padding: 0 30px;">
            @if($pet->isProvisional())
                <div class="provisional-notice">
                    <strong>PROVISIONAL RECORD</strong><br>
                    <small>Complete details in admin panel</small>
                </div>
            @endif
            <h2 style="margin: 0; color: #0066cc;">Patient ID: {{ $pet->unique_id }}</h2>
        </div>
        
        <div class="barcode">
            @if($qr_success && $barcode_url)
                <img src="{{ asset('storage/' . ltrim($barcode_url, '/storage/')) }}" alt="Barcode">
            @else
                <div style="width: 200px; height: 60px; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                    <span style="font-size: 10px; color: #666;">{{ $pet->unique_id }}</span>
                </div>
            @endif
            <div class="code-label">{{ $pet->unique_id }}</div>
        </div>
    </div>

    <!-- Patient Information -->
    <div class="patient-info">
        <div class="info-grid">
            <div>
                <div class="info-item">
                    <span class="info-label">Pet Name:</span>
                    <span class="info-value">{{ $pet->name ?? '_______________' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Species:</span>
                    <span class="info-value">{{ $pet->species->name ?? '_______________' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Breed:</span>
                    <span class="info-value">{{ $pet->breed->name ?? '_______________' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Gender:</span>
                    <span class="info-value">{{ $pet->gender ?? '_______________' }}</span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Owner Name:</span>
                    <span class="info-value">{{ $pet->owner->name ?? '_______________' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Mobile:</span>
                    <span class="info-value">{{ $pet->owner->primary_mobile ?? '_______________' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Address:</span>
                    <span class="info-value">{{ $pet->owner->address ?? '_______________' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Registration:</span>
                    <span class="info-value">{{ $pet->unique_id }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Vital Signs -->
    <div class="vital-signs">
        <div class="vital-item">
            <strong>Temperature:</strong> _______________
        </div>
        <div class="vital-item">
            <strong>Weight:</strong> _______________
        </div>
        <div class="vital-item">
            <strong>Heart Rate:</strong> _______________
        </div>
        <div class="vital-item">
            <strong>Respiratory Rate:</strong> _______________
        </div>
        <div class="vital-item">
            <strong>Blood Pressure:</strong> _______________
        </div>
        <div class="vital-item">
            <strong>Body Condition:</strong> _______________
        </div>
    </div>

    <!-- Prescription Area -->
    <div class="prescription-area">
        <div class="prescription-title">Clinical Findings & Prescription</div>
        <div style="margin-bottom: 20px;">
            <strong>Clinical Findings:</strong>
            <div style="height: 80px; border-bottom: 1px solid #ddd; margin-top: 5px;"></div>
        </div>
        <div>
            <strong>Prescription:</strong>
            <div style="height: 200px; border-bottom: 1px solid #ddd; margin-top: 5px;"></div>
        </div>
    </div>

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div style="font-size: 12px; font-weight: bold;">Doctor Signature</div>
            <div style="font-size: 10px; margin-top: 5px;">Dr. _______________</div>
        </div>
        
        <div class="signature-box">
            <div class="signature-line"></div>
            <div style="font-size: 12px; font-weight: bold;">Next Visit</div>
            <div style="font-size: 10px; margin-top: 5px;">Date: _______________</div>
        </div>
    </div>

    <!-- Print Button -->
    <div class="no-print" style="text-align: center; margin-top: 30px;">
        <button onclick="window.print()" style="background: #0066cc; color: white; padding: 10px 20px; border: none; border-radius: 5px; font-size: 14px; cursor: pointer;">
            Print Letterhead
        </button>
    </div>
</body>
</html>