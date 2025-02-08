@aware(['title', 'message'])

Title: {{ $title }}
Message: {{ $message ?? 'Nope' }}