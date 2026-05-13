<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; color: #333;">
  <h2 style="margin-bottom: 8px;">{{ $campaign->subject }}</h2>
  <div style="line-height: 1.7;">
    {!! nl2br(e($campaign->body)) !!}
  </div>
  <hr style="border: none; border-top: 1px solid #eee; margin: 32px 0;">
  <p style="color: #999; font-size: 12px;">
    You received this because you subscribed to our newsletter.
    <a href="{{ url('/newsletter/unsubscribe/' . $subscriber->token) }}" style="color: #999;">Unsubscribe</a>
  </p>
</body>
</html>
