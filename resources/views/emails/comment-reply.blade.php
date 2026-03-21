<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; color: #333;">
  <h2 style="margin-bottom: 8px;">Someone replied to your comment</h2>
  <p style="color: #666;">Your comment on <strong>{{ $postTitle }}</strong>:</p>
  <blockquote style="border-left: 3px solid #ddd; margin: 8px 0; padding: 8px 16px; color: #555;">
    {{ $parent->body }}
  </blockquote>
  <p style="color: #666;">Reply from <strong>{{ $reply->author_name }}</strong>:</p>
  <blockquote style="border-left: 3px solid #5e81ac; margin: 8px 0; padding: 8px 16px; color: #333;">
    {{ $reply->body }}
  </blockquote>
</body>
</html>
