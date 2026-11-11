<!DOCTYPE html>
<html lang="en">
<body style="font-family: Arial, sans-serif; color: #1e293b; line-height: 1.6;">
    <h2 style="margin-bottom: 4px;">Verify your email</h2>
    <p>Hi {{ $user->name }},</p>
    <p>Your {{ \App\Models\Setting::get('site_name', 'WatchEarn') }} verification code is:</p>
    <p style="font-size: 32px; font-weight: bold; letter-spacing: 8px; margin: 16px 0;">{{ $code }}</p>
    <p>Enter this code on the verification page within 15 minutes. If you did not create an account, you can ignore this email.</p>
</body>
</html>
