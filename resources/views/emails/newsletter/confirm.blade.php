<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; color: #333;">
  <h2 style="margin-bottom: 8px;">Confirm your subscription</h2>
  <p>Hi{{ $subscriber->name ? ' ' . $subscriber->name : '' }},</p>
  <p>Click the button below to confirm your newsletter subscription.</p>
  <p style="margin: 24px 0;">
    <a href="{{ url('/newsletter/confirm/' . $subscriber->token) }}"
       style="display: inline-block; background: #5e81ac; color: #fff; padding: 10px 24px; border-radius: 6px; text-decoration: none; font-weight: 600;">
      Confirm subscription
    </a>
  </p>
  <p style="color: #888; font-size: 13px;">If you did not request this, you can safely ignore this email.</p>
</body>
</html>
