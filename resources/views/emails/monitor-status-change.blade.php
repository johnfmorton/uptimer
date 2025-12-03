<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor Status Change</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: {{ $status === 'down' ? '#dc2626' : '#16a34a' }};
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 8px 8px;
        }
        .info-row {
            margin: 15px 0;
            padding: 10px;
            background: white;
            border-radius: 4px;
            border-left: 4px solid {{ $status === 'down' ? '#dc2626' : '#16a34a' }};
        }
        .label {
            font-weight: 600;
            color: #6b7280;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .value {
            color: #111827;
            font-size: 16px;
            margin-top: 4px;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 14px;
            background: {{ $status === 'down' ? '#dc2626' : '#16a34a' }};
            color: white;
        }
        .error-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
        }
        .error-box .label {
            color: #991b1b;
        }
        .error-box .value {
            color: #7f1d1d;
            font-family: monospace;
            font-size: 14px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $status === 'down' ? '🔴 Monitor Down' : '✅ Monitor Recovered' }}</h1>
    </div>
    
    <div class="content">
        <div class="info-row">
            <div class="label">Monitor Name</div>
            <div class="value">{{ $monitor->name }}</div>
        </div>
        
        <div class="info-row">
            <div class="label">URL</div>
            <div class="value">{{ $monitor->url }}</div>
        </div>
        
        <div class="info-row">
            <div class="label">Status</div>
            <div class="value">
                <span class="status-badge">{{ strtoupper($status) }}</span>
            </div>
        </div>
        
        <div class="info-row">
            <div class="label">Timestamp</div>
            <div class="value">{{ $timestamp->format('F j, Y g:i A T') }}</div>
        </div>
        
        @if($status === 'down')
            <div class="error-box">
                <div class="label">Error Details</div>
                <div class="value">{{ $error_details }}</div>
                
                @if(isset($status_code))
                    <div class="label" style="margin-top: 10px;">Status Code</div>
                    <div class="value">HTTP {{ $status_code }}</div>
                @endif
            </div>
        @else
            @if(isset($downtime_duration))
                <div class="info-row">
                    <div class="label">Downtime Duration</div>
                    <div class="value">{{ $downtime_duration }}</div>
                </div>
            @endif
        @endif
    </div>
    
    <div class="footer">
        <p>This is an automated notification from your uptime monitoring system.</p>
    </div>
</body>
</html>
