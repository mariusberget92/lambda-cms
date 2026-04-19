New comment awaiting moderation on Lambda CMS.

Post: {{ $comment->post->title }}
URL: {{ url('/blog/' . $comment->post->slug) }}

Author: {{ $comment->author_name }} ({{ $comment->author_email ?? 'no email' }})

Comment:
{{ $comment->body }}

---
To moderate this comment, visit:
{{ url('/comments') }}
