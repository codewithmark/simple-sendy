# SimpleSendy

A lightweight PHP wrapper for SendGrid's email API that simplifies sending transactional, broadcast, and multi-recipient emails with support for dynamic content, scheduling, and email tracking.

## Features

- 📧 **Transactional Emails** - Send personalized emails with dynamic data to individual recipients
- 📢 **Broadcast Emails** - Send bulk emails to multiple subscribers with per-recipient customization
- 👥 **Multi-Recipient Emails** - Send the same email to multiple recipients
- 🕐 **Scheduled Delivery** - Schedule emails to be sent at a specific time
- 📊 **Email Tracking** - Track opens and clicks with custom tracking IDs
- 🏷️ **Categories** - Organize emails by category for better analytics
- 🎯 **Dynamic Content** - Use placeholders like `{{user_name}}` for personalization
- 🔧 **Custom Arguments** - Attach custom metadata to emails for tracking and reporting
- ⏱️ **Timezone Support** - Handle different timezones for scheduled sends
- 🚀 **Batching** - Automatically batch broadcast emails with random chunk sizes for better delivery

## Requirements

- PHP 7.4+
- cURL extension
- SendGrid API key

## Installation

1. Download `class_SimpleSendy_v3.php` to your project
2. Include the class in your code:

```php
require_once 'class_SimpleSendy_v3.php';
```

3. Initialize with your SendGrid API key:

```php
$sendy = new SimpleSendy('your_sendgrid_api_key');
```

## Usage Examples

### Basic Transactional Email

```php
$sendy = new SimpleSendy('your_sendgrid_api_key');

$response = $sendy
    ->from('Support', 'support@example.com')
    ->replyTo('noreply@example.com')
    ->subject('Password Reset')
    ->body('Hello {{user_name}}, reset link: {{reset_link}}')
    ->transactional('jane@example.com', [
        'user_name' => 'Jane',
        'reset_link' => 'https://example.com/reset?token=abc'
    ])
    ->send();

print_r($response);
```

### Scheduled Transactional Email

```php
$sendy = new SimpleSendy('your_sendgrid_api_key');

$response = $sendy
    ->setDefaultTimezone('America/New_York')
    ->from('Support', 'support@example.com')
    ->replyTo('noreply@example.com')
    ->subject('Scheduled Email for {{user_name}}')
    ->body('<p>Hello {{user_name}}, your report is ready.</p>')
    ->sendAt('2025-07-05 09:00:00')
    ->transactional('alice@example.com', [
        'user_name' => 'Alice',
        'user_id' => 123,
        'plan' => 'pro'
    ])
    ->transactional('jane@example.com', ['user_name' => 'Jane'])
    ->send();
```

### Multi-Recipient Email

```php
$sendy = new SimpleSendy('your_sendgrid_api_key');

$recipients = [
    'alice@example.com',
    'bob@example.com',
    'carol@example.com'
];

$response = $sendy
    ->from('MyApp Team', 'newsletter@myapp.com')
    ->replyTo('support@myapp.com')
    ->subject('Important Update')
    ->body('<p>We have an important announcement for you.</p>')
    ->emails($recipients)
    ->send();
```

### Broadcast Email (One-to-Many with Personalization)

```php
$sendy = new SimpleSendy('your_sendgrid_api_key');

$subscribers = [
    ['email' => 'alice@example.com', 'user_name' => 'Alice', 'user_id' => 101],
    ['email' => 'bob@example.com', 'user_name' => 'Bob', 'user_id' => 102],
    ['email' => 'carol@example.com', 'user_name' => 'Carol', 'user_id' => 103],
];

$tracking_id = 'TrackingId_' . uniqid();
$category_id = 'newsletter_' . uniqid();

$response = $sendy
    ->setDefaultTimezone('America/New_York')
    ->setCategory($category_id)
    ->setTrackingId($tracking_id)
    ->from('MyApp Team', 'newsletter@myapp.com')
    ->replyTo('support@myapp.com')
    ->subject('Hello {{user_name}}, check out what\'s new!')
    ->body('<p>Hi {{user_name}},</p><p>Here\'s our latest news!</p>')
    ->track(true, true)  // Enable open and click tracking
    ->sendAt('2025-07-06 09:00:00')
    ->broadcast($subscribers, ['user_name'], ['user_name' => 'Subscriber'])
    ->send();

print_r($response);
```

### With Custom Tracking and Categories

```php
$sendy = new SimpleSendy('your_sendgrid_api_key');

$response = $sendy
    ->from('Support', 'support@example.com')
    ->replyTo('noreply@example.com')
    ->subject('Welcome {{first_name}}!')
    ->body('<p>Welcome to our platform, {{first_name}}!</p>')
    ->setCategory('welcome_emails')
    ->setTrackingId('welcome_' . time())
    ->transactional('newuser@example.com', ['first_name' => 'John'])
    ->send();
```

## API Reference

### Configuration Methods

#### `__construct(string $apiKey)`
Initialize the SimpleSendy instance with your SendGrid API key.

#### `setDefaultTimezone(string $timezone): self`
Set the default timezone for scheduled sends. Pass a valid PHP timezone identifier (e.g., 'America/New_York').

#### `setCategory(string $category): self`
Assign a category to the email for analytics and organization.

#### `setTrackingId(string $id = ''): self`
Set a custom tracking ID. If empty, one is auto-generated. Use this to correlate emails with user actions.

#### `setCustomArgs(array $customArgs): self`
Attach custom metadata to emails for tracking and reporting.

### Sender Methods

#### `from(string $name, string $email): self`
Set the sender's name and email address.

#### `replyTo(string $email): self`
Set the reply-to email address.

#### `subject(string $subject): self`
Set the email subject. Supports placeholders like `{{variable_name}}`.

#### `body(string $content): self`
Set the email body content. Automatically detects HTML vs. plain text.

### Tracking Methods

#### `track(bool $opens = true, bool $clicks = true): self`
Enable/disable open and click tracking.

### Scheduling Methods

#### `sendAt(string $datetime): self`
Schedule the email to be sent at a specific time (must be in the future).
- Respects the timezone set via `setDefaultTimezone()`
- Throws `InvalidArgumentException` if the time is in the past

### Recipient Methods

#### `transactional(string $email, array $dynamicData = []): self`
Send a personalized email to a single recipient. Each call adds another recipient to the batch.
- `$email`: Recipient email address
- `$dynamicData`: Key-value pairs for placeholder replacement

#### `emails(array $emailList): self`
Send the same email to multiple recipients at once.
- Accepts an array of email strings or email objects

#### `broadcast(array $subscribers, array $dynamicKeys = [], array $fallbacks = []): self`
Send personalized emails to multiple subscribers.
- `$subscribers`: Array of subscriber objects with email and other properties
- `$dynamicKeys`: Array of keys to extract from each subscriber for personalization
- `$fallbacks`: Default values if a key is missing in a subscriber

### Send Method

#### `send(): array`
Send all queued emails and return an array of responses. Each response contains:
```php
[
    'status' => 'success' | 'error',
    'code' => HTTP_STATUS_CODE,
    'message' => 'Error message (if applicable)',
    'response' => array // SendGrid API response (if applicable)
]
```

## Response Format

Successful response:
```php
['status' => 'success', 'code' => 202]
```

Error response:
```php
['status' => 'error', 'message' => 'cURL error message']
// or
['status' => 'error', 'code' => 400, 'response' => [...SendGrid API error...]]
```

## Tips & Best Practices

1. **Placeholders**: Use double curly braces `{{key_name}}` in your subject and body for dynamic content
2. **Batch Sends**: Broadcast emails are automatically chunked with random sizes (800-950) to avoid SendGrid rate limits, with 5-second delays between batches
3. **Tracking IDs**: Use custom tracking IDs to correlate emails with user actions for analytics
4. **Fallbacks**: When using broadcast, provide fallback values for missing dynamic keys
5. **Error Handling**: Always check the response status to handle failures

## Error Handling

```php
$responses = $sendy
    ->from('Support', 'support@example.com')
    ->subject('Test Email')
    ->body('Test content')
    ->transactional('user@example.com', [])
    ->send();

foreach ($responses as $response) {
    if ($response['status'] === 'error') {
        echo "Error: " . $response['message'];
    } else {
        echo "Email sent successfully!";
    }
}
```

## License

MIT License - Feel free to use this in your projects!

## Support

For issues or questions, please refer to the SendGrid API documentation at https://docs.sendgrid.com/
