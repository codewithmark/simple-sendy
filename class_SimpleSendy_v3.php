<?php

/*

require_once 'SimpleSendy.php';

✅ Example Usage – Transactional 

$sendy = new SimpleSendy('your_sendgrid_api_key');

$response = $sendy
    ->from('Support', 'support@example.com')
    ->replyTo('noreply@example.com')
    ->subject('Password Reset')
    ->body('Hello {{user_name}}, reset link: {{reset_link}}')
    ->sendAt('2025-07-05 09:00:00')
    ->transactional('jane@example.com', [
        'user_name' => 'Jane',
        'reset_link' => 'https://example.com/reset?token=abc'
    ])
    ->send();

    $response = $sendy
    ->setDefaultTimezone('America/New_York')
    ->from('Support', 'support@example.com')
    ->replyTo('noreply@example.com')
    ->subject('Scheduled Email for {{user_name}}')
    ->body('<p>Hello {{user_name}}, your report is ready.</p>')
    ->sendAt('2025-07-05 09:00:00')
    ->transactional('alice@example.com', [
        'user_name' => 'Alice',
        'user_id' => 123, //need it to keep track of stats > delivered, opened, clicked and etc
        'plan' => 'pro'
    ])
    ->transactional('jane@example.com', ['user_name' => 'Jane'])
    ->send();


print_r($response);



✅ Example Usage – Broadcast

- this will send emails 1 to many( chuck rate will be 1k ) 

// Initialize the Sendy instance with your SendGrid API key
$sendy = new SimpleSendy('your_sendgrid_api_key');

 
// Subscriber list (could be pulled from DB)
//'user_id' => 103  need it to keep track of stats > delivered, opened, clicked and etc
$subscribers = [
    ['email' => 'alice@example.com', 'user_name' => 'Alice', 'user_id' => 101],
    ['email' => 'bob@example.com', 'user_name' => 'Bob', 'user_id' => 102],
    ['email' => 'carol@example.com', 'user_name' => 'Carol', 'user_id' => 103],
];

//broadcast id could be tied to this
$tracking_id  = 'TrackingId_'.uniqid(); // for stats tracking: delivered, opened, clicked, bounced
$category_id  = 'newsletter_category'.uniqid();

// Send the broadcast
$response = $sendy
    ->setDefaultTimezone('America/New_York')                    // Optional: apply timezone for scheduling
    
    ->setCategory(category_id)               //
    ->setTrackingId($tracking_id)                        // for tracking stats 

    ->from('MyApp Team', 'newsletter@myapp.com')                // Sender
    ->replyTo('support@myapp.com')                              // Reply-to
    ->subject('Hello {{user_name}}, check out what’s new!')     // Subject with placeholder
    ->body('<p>Hi {{user_name}},</p><p>Here’s our latest news!</p>') // HTML-only content
    ->track(true, true)                                         // Enable open + click tracking
    ->sendAt('2025-07-06 09:00:00')                             // Optional scheduled delivery
    ->broadcast($subscribers, ['user_name'], ['user_name' => 'Subscriber']) // Broadcast with fallback > if user_name not found, it will put 'Subscriber'
    ->send();

// Output result
print_r($response);

*/
  
 
class SimpleSendy
{
    private string $apiKey;
    private string $endpoint = 'https://api.sendgrid.com/v3/mail/send';

    private array $personalizations = [];
    private array $emailData = [];
    private array $trackingSettings = [
        'open_tracking' => ['enable' => true],
        'click_tracking' => ['enable' => true, 'enable_text' => true],
    ];

    private ?int $sendAtTimestamp = null;
    private ?string $defaultTimezone = null;

    private string $rawHtml = '';
    private string $rawPlain = '';

    private ?string $category = null;
    private ?string $trackingId = null;
    private string $emailType = 'generic';

    public function __construct(string $apiKey)
    {
        if (!function_exists('curl_init')) {
            die('cURL is not installed!');
        }

        $this->apiKey = $apiKey;
    }

    public function setDefaultTimezone(string $timezone): self
    {
        date_default_timezone_set($timezone);
        $this->defaultTimezone = $timezone;
        return $this;
    }

    public function sendAt(string $datetime): self
    {
        $tz = $this->defaultTimezone
            ? new DateTimeZone($this->defaultTimezone)
            : new DateTimeZone(date_default_timezone_get());

        $dt = new DateTime($datetime, $tz);
        $timestamp = $dt->getTimestamp();

        if ($timestamp <= time()) {
            throw new InvalidArgumentException("sendAt() must be a future time.");
        }

        $this->sendAtTimestamp = $timestamp;
        return $this;
    }

    public function from(string $name, string $email): self
    {
        $this->emailData['from'] = ['email' => $email, 'name' => $name];
        return $this;
    }

    public function replyTo(string $email): self
    {
        $this->emailData['reply_to'] = ['email' => $email];
        return $this;
    }

    public function subject(string $subject): self
    {
        $this->emailData['subject'] = $subject;
        return $this;
    }

    public function body(string $content): self
    {
        $isHtml = strip_tags($content) !== $content;
        if ($isHtml) {
            $this->rawHtml = $content;
            $this->rawPlain = '';
        } else {
            $this->rawPlain = $content;
            $this->rawHtml = '';
        }
        return $this;
    }

    public function track(bool $opens = true, bool $clicks = true): self
    {
        $this->trackingSettings['open_tracking']['enable'] = $opens;
        $this->trackingSettings['click_tracking']['enable'] = $clicks;
        return $this;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function setTrackingId(string $id = ''): self
    {
        $this->trackingId = $id ?: uniqid('track_', true);
        return $this;
    }

    public function setCustomArgs(array $customArgs): self
    {
        $this->emailData['custom_args_extra'] = $customArgs;
        return $this;
    }

    public function transactional(string $email, array $dynamicData = []): self
    {
        $this->emailType = 'transactional';

        $html = $this->mergeDynamicData($this->rawHtml, $dynamicData);
        $plain = $this->mergeDynamicData($this->rawPlain, $dynamicData);

        $trackingId = $this->trackingId ?? uniqid('track_', true);
        $customArgs = array_merge(
            [
                'tracking_id' => $trackingId,
                'email_type' => $this->emailType
            ],
            $dynamicData,
            $this->emailData['custom_args_extra'] ?? []
        );

        $personalization = [
            'to' => [['email' => $email]],
            'subject' => $this->emailData['subject'] ?? '',
            'custom_args' => $customArgs,
        ];

        if ($this->category) {
            $personalization['category'] = [$this->category];
        }

        if ($this->sendAtTimestamp) {
            $personalization['send_at'] = $this->sendAtTimestamp;
        }

        $this->personalizations[] = $personalization;

        $this->emailData['per_recipient'][] = [
            'html' => $html,
            'plain' => $plain,
        ];

        return $this;
    }

    public function emails(array $emailList): self
    {
        $this->emailType = 'multi-recipient';

        // Convert email list to array of email objects
        $toRecipients = [];
        foreach ($emailList as $email) {
            if (is_string($email)) {
                $toRecipients[] = ['email' => $email];
            } elseif (is_array($email) && isset($email['email'])) {
                $toRecipients[] = $email;
            }
        }

        if (empty($toRecipients)) {
            throw new InvalidArgumentException("emails() requires a non-empty array of email addresses.");
        }

        $html = $this->rawHtml;
        $plain = $this->rawPlain;

        $trackingId = $this->trackingId ?? uniqid('track_', true);
        $customArgs = array_merge(
            [
                'tracking_id' => $trackingId,
                'email_type' => $this->emailType
            ],
            $this->emailData['custom_args_extra'] ?? []
        );

        $personalization = [
            'to' => $toRecipients,
            'subject' => $this->emailData['subject'] ?? '',
            'custom_args' => $customArgs,
        ];

        if ($this->category) {
            $personalization['category'] = [$this->category];
        }

        if ($this->sendAtTimestamp) {
            $personalization['send_at'] = $this->sendAtTimestamp;
        }

        $this->personalizations[] = $personalization;

        $this->emailData['per_recipient'][] = [
            'html' => $html,
            'plain' => $plain,
        ];

        return $this;
    }

    public function broadcast(array $subscribers, array $dynamicKeys = [], array $fallbacks = []): self
    {
        $this->emailType = 'broadcast';
        $chunks = $this->chunkRandomSize($subscribers, 800, 950);

        foreach ($chunks as $chunk) {
            foreach ($chunk as $subscriber) {
                if (!isset($subscriber['email'])) continue;

                $dynamicData = [];
                foreach ($dynamicKeys as $key) {
                    $dynamicData[$key] = $subscriber[$key] ?? ($fallbacks[$key] ?? '');
                }

                $html = $this->mergeDynamicData($this->rawHtml, $dynamicData);
                $plain = $this->mergeDynamicData($this->rawPlain, $dynamicData);

                $trackingId = $this->trackingId ?? uniqid('track_', true);
                $customArgs = array_merge(
                    [
                        'tracking_id' => $trackingId,
                        'email_type' => $this->emailType,
                    ],
                    $subscriber
                );
                unset($customArgs['email']); // prevent duplication

                $personalization = [
                    'to' => [['email' => $subscriber['email']]],
                    'subject' => $this->emailData['subject'] ?? '',
                    'custom_args' => $customArgs,
                ];

                if ($this->category) {
                    $personalization['category'] = [$this->category];
                }

                if ($this->sendAtTimestamp) {
                    $personalization['send_at'] = $this->sendAtTimestamp;
                }

                $this->personalizations[] = $personalization;

                $this->emailData['per_recipient'][] = [
                    'html' => $html,
                    'plain' => $plain,
                ];
            }
        }

        return $this;
    }

    public function send(): array
    {
        $responses = [];
        $total = count($this->personalizations);

        for ($i = 0; $i < $total; $i++) {
            $p = $this->personalizations[$i];

            $payload = [
                'from' => $this->emailData['from'],
                'reply_to' => $this->emailData['reply_to'] ?? $this->emailData['from'],
                'personalizations' => [$p],
                'tracking_settings' => $this->trackingSettings,
                'content' => [],
            ];

            $html = $this->emailData['per_recipient'][$i]['html'] ?? '';
            $plain = $this->emailData['per_recipient'][$i]['plain'] ?? '';

            if (!empty($html)) {
                $payload['content'][] = ['type' => 'text/html', 'value' => $html];
            } elseif (!empty($plain)) {
                $payload['content'][] = ['type' => 'text/plain', 'value' => $plain];
            } else {
                $responses[] = ['status' => 'error', 'message' => 'Missing content'];
                continue;
            }

            $responses[] = $this->sendRequest($payload);

            if (($i + 1) % rand(800, 950) === 0) {
                sleep(5);
            }
        }

        return $responses;
    }

    private function sendRequest(array $payload): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($error) {
            return ['status' => 'error', 'message' => $error];
        }

        return $status >= 200 && $status < 300
            ? ['status' => 'success', 'code' => $status]
            : ['status' => 'error', 'code' => $status, 'response' => json_decode($response, true)];
    }

    private function mergeDynamicData(string $content, array $data): string
    {
        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        return $content;
    }

    private function chunkRandomSize(array $list, int $min, int $max): array
    {
        $chunks = [];
        $i = 0;
        $count = count($list);
        while ($i < $count) {
            $size = rand($min, $max);
            $chunks[] = array_slice($list, $i, $size);
            $i += $size;
        }
        return $chunks;
    }
} 
?>
