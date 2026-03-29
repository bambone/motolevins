Заявка с маркетингового сайта RentBase
================================

Намерение: {{ $payload['intent_label'] ?? '—' }} ({{ $payload['intent'] ?? '—' }})

Имя: {{ $payload['name'] ?? '—' }}
Телефон: {{ $payload['phone'] ?? '—' }}
Email: {{ $payload['email'] ?? '—' }}

Сообщение:
{{ $payload['message'] ?? '—' }}

---
UTM: source={{ $payload['utm_source'] ?? '—' }} | medium={{ $payload['utm_medium'] ?? '—' }} | campaign={{ $payload['utm_campaign'] ?? '—' }}
Referer: {{ $payload['page_url'] ?? '—' }}
IP: {{ $payload['ip'] ?? '—' }}
