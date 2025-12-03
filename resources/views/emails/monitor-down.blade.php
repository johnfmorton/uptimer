<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor Down</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff;">
    <div style="background-color: #dc2626; color: #ffffff; padding: 20px; border-radius: 8px 8px 0 0; text-align: center;">
        <h1 style="margin: 0; font-size: 24px; font-weight: 600;">🔴 Monitor Down</h1>
    </div>
    
    <div style="background-color: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">
        <div style="margin: 15px 0; padding: 10px; background-color: #ffffff; border-radius: 4px; border-left: 4px solid #dc2626;">
            <div style="font-weight: 600; color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Monitor Name</div>
            <div style="color: #111827; font-size: 16px; margin-top: 4px;">{{ $monitor->name }}</div>
        </div>
        
        <div style="margin: 15px 0; padding: 10px; background-color: #ffffff; border-radius: 4px; border-left: 4px solid #dc2626;">
            <div style="font-weight: 600; color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">URL</div>
            <div style="color: #111827; font-size: 16px; margin-top: 4px; word-break: break-all;">{{ $monitor->url }}</div>
        </div>
        
        <div style="margin: 15px 0; padding: 10px; background-color: #ffffff; border-radius: 4px; border-left: 4px solid #dc2626;">
            <div style="font-weight: 600; color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Status</div>
            <div style="color: #111827; font-size: 16px; margin-top: 4px;">
                <span style="display: inline-block; padding: 6px 12px; border-radius: 4px; font-weight: 600; font-size: 14px; background-color: #dc2626; color: #ffffff;">DOWN</span>
            </div>
        </div>
        
        <div style="margin: 15px 0; padding: 10px; background-color: #ffffff; border-radius: 4px; border-left: 4px solid #dc2626;">
            <div style="font-weight: 600; color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Timestamp</div>
            <div style="color: #111827; font-size: 16px; margin-top: 4px;">{{ $timestamp->format('F j, Y g:i A T') }}</div>
        </div>
        
        <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 4px; padding: 15px; margin: 15px 0;">
            <div style="font-weight: 600; color: #991b1b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Error Details</div>
            <div style="color: #7f1d1d; font-family: monospace; font-size: 14px; margin-top: 4px; word-break: break-word;">{{ $error_details }}</div>
            
            @if(isset($status_code))
                <div style="font-weight: 600; color: #991b1b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 10px;">Status Code</div>
                <div style="color: #7f1d1d; font-family: monospace; font-size: 14px; margin-top: 4px;">HTTP {{ $status_code }}</div>
            @endif
        </div>
    </div>
    
    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center; color: #6b7280; font-size: 14px;">
        <p style="margin: 0;">This is an automated notification from your uptime monitoring system.</p>
    </div>
</body>
</html>
