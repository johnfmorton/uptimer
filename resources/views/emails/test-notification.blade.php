<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Notification</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff;">
    <div style="background-color: #3b82f6; color: #ffffff; padding: 20px; border-radius: 8px 8px 0 0; text-align: center;">
        <h1 style="margin: 0; font-size: 24px; font-weight: 600;">🔔 Test Notification</h1>
    </div>
    
    <div style="background-color: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">
        <div style="background-color: #dbeafe; border: 1px solid #93c5fd; border-radius: 4px; padding: 15px; margin: 15px 0;">
            <div style="font-weight: 600; color: #1e40af; font-size: 16px; margin-bottom: 8px;">This is a test message</div>
            <div style="color: #1e3a8a; font-size: 14px; line-height: 1.5;">
                This email confirms that your email notification settings are configured correctly for 
                <strong>{{ config('app.name', 'Laravel App') }}</strong> ({{ config('app.url', 'localhost') }}). 
                You should receive actual monitor alerts at this email address when your monitors detect issues.
            </div>
        </div>
        
        <div style="margin: 15px 0; padding: 10px; background-color: #ffffff; border-radius: 4px; border-left: 4px solid #3b82f6;">
            <div style="font-weight: 600; color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Email Address</div>
            <div style="color: #111827; font-size: 16px; margin-top: 4px;">{{ $user->email }}</div>
        </div>
        
        <div style="margin: 15px 0; padding: 10px; background-color: #ffffff; border-radius: 4px; border-left: 4px solid #3b82f6;">
            <div style="font-weight: 600; color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Timestamp</div>
            <div style="color: #111827; font-size: 16px; margin-top: 4px;">{{ now()->format('F j, Y g:i A T') }}</div>
        </div>
        
        <div style="margin-top: 25px; text-align: center;">
            <a href="{{ route('notification-settings.edit') }}" style="display: inline-block; background-color: #3b82f6; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px;">
                Manage Notification Settings
            </a>
        </div>
    </div>
    
    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center; color: #6b7280; font-size: 14px;">
        <p style="margin: 0;">This is a test notification from {{ config('app.name', 'Laravel App') }} ({{ config('app.url', 'localhost') }}).</p>
    </div>
</body>
</html>
