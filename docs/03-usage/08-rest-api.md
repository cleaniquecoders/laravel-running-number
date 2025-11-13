# REST API

Generate and manage running numbers via HTTP endpoints.

## Overview

The Laravel Running Number package provides optional REST API endpoints for generating and checking running numbers remotely. This is useful for:

- **Microservices**: Generate numbers from external services
- **Mobile Apps**: Generate numbers from iOS/Android applications
- **Third-party Integrations**: Allow partners to generate numbers via API
- **Webhooks**: Generate numbers in response to external events
- **Distributed Systems**: Centralized number generation across multiple applications

## Configuration

Enable the API in your `config/running-number.php`:

```php
'api' => [
    // Enable or disable API routes
    'enabled' => env('RUNNING_NUMBER_API_ENABLED', false),

    // API route prefix (default: api/running-numbers)
    'prefix' => env('RUNNING_NUMBER_API_PREFIX', 'api/running-numbers'),

    // API middleware
    'middleware' => ['api'],

    // Optional: Add authentication
    // 'middleware' => ['api', 'auth:sanctum'],
],
```

Or use environment variables:

```bash
RUNNING_NUMBER_API_ENABLED=true
RUNNING_NUMBER_API_PREFIX=api/running-numbers
```

## Available Endpoints

### 1. Generate Running Number

Generate a new sequential number.

**Endpoint**: `POST /api/running-numbers/generate`

**Request Body**:

```json
{
  "type": "invoice",
  "scope": "retail",
  "start_from": 1000,
  "max_number": 9999,
  "presenter": "CleaniqueCoders\\RunningNumber\\Presenters\\DatePrefixPresenter"
}
```

**Parameters**:

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `type` | string | Yes | The type of running number |
| `scope` | string | No | Optional scope for independent sequences |
| `start_from` | integer | No | Starting number (only applies when creating new type) |
| `max_number` | integer | No | Maximum number limit |
| `presenter` | string | No | Full class name of custom presenter |

**Success Response** (201 Created):

```json
{
  "success": true,
  "data": {
    "number": "INVOICE1001",
    "type": "INVOICE",
    "scope": "retail",
    "current_count": 1001,
    "uuid": "123e4567-e89b-12d3-a456-426614174000",
    "reset_period": "monthly",
    "last_reset_at": "2025-11-01T00:00:00+00:00",
    "created_at": "2025-01-15T10:30:15+00:00"
  }
}
```

**Error Responses**:

```json
// Validation Error (422)
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "type": ["The type field is required."]
  }
}

// Invalid Type (400)
{
  "success": false,
  "message": "Invalid running number type",
  "error": "Type 'unknown' is not in the allowed types list"
}

// Max Number Reached (422)
{
  "success": false,
  "message": "Maximum number reached",
  "error": "Maximum number 9999 reached for type INVOICE"
}
```

**cURL Example**:

```bash
curl -X POST http://your-app.test/api/running-numbers/generate \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "type": "invoice",
    "scope": "retail"
  }'
```

**PHP/Guzzle Example**:

```php
use GuzzleHttp\Client;

$client = new Client();
$response = $client->post('http://your-app.test/api/running-numbers/generate', [
    'json' => [
        'type' => 'invoice',
        'scope' => 'retail',
        'start_from' => 1000,
    ],
]);

$data = json_decode($response->getBody(), true);
$number = $data['data']['number']; // INVOICE1001
```

### 2. Get Current Number

Retrieve current running number information.

**Endpoint**: `GET /api/running-numbers/current`

**Query Parameters**:

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `type` | string | Yes | The type of running number |
| `scope` | string | No | Optional scope identifier |

**Success Response** (200 OK):

```json
{
  "success": true,
  "data": {
    "uuid": "123e4567-e89b-12d3-a456-426614174000",
    "type": "INVOICE",
    "scope": "retail",
    "current_number": 42,
    "reset_period": "monthly",
    "last_reset_at": "2025-11-01T00:00:00+00:00",
    "created_at": "2025-01-15T10:30:15+00:00",
    "updated_at": "2025-11-13T14:25:10+00:00"
  }
}
```

**Error Response** (404 Not Found):

```json
{
  "success": false,
  "message": "Running number not found"
}
```

**cURL Example**:

```bash
curl -X GET "http://your-app.test/api/running-numbers/current?type=invoice&scope=retail" \
  -H "Accept: application/json"
```

**JavaScript/Fetch Example**:

```javascript
const response = await fetch(
  'http://your-app.test/api/running-numbers/current?type=invoice&scope=retail',
  {
    headers: {
      'Accept': 'application/json',
    },
  }
);

const data = await response.json();
console.log(`Current number: ${data.data.current_number}`);
```

### 3. Preview Next Number

Preview the next number without generating it.

**Endpoint**: `GET /api/running-numbers/preview`

**Query Parameters**:

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `type` | string | Yes | The type of running number |
| `scope` | string | No | Optional scope identifier |
| `start_from` | integer | No | Starting number for new types |
| `presenter` | string | No | Full class name of custom presenter |

**Success Response** (200 OK):

```json
{
  "success": true,
  "data": {
    "preview": "INVOICE043",
    "type": "invoice",
    "scope": "retail"
  }
}
```

**cURL Example**:

```bash
curl -X GET "http://your-app.test/api/running-numbers/preview?type=invoice" \
  -H "Accept: application/json"
```

### 4. List Running Numbers

List all running numbers with pagination and filtering.

**Endpoint**: `GET /api/running-numbers/list`

**Query Parameters**:

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `type` | string | No | Filter by type |
| `scope` | string | No | Filter by scope |
| `per_page` | integer | No | Items per page (1-100, default: 15) |

**Success Response** (200 OK):

```json
{
  "success": true,
  "data": [
    {
      "uuid": "123e4567-e89b-12d3-a456-426614174000",
      "type": "INVOICE",
      "scope": "retail",
      "current_number": 42,
      "reset_period": "monthly",
      "last_reset_at": "2025-11-01T00:00:00+00:00",
      "created_at": "2025-01-15T10:30:15+00:00",
      "updated_at": "2025-11-13T14:25:10+00:00"
    },
    {
      "uuid": "234e5678-e89b-12d3-a456-426614174001",
      "type": "ORDER",
      "scope": "wholesale",
      "current_number": 156,
      "reset_period": "never",
      "last_reset_at": null,
      "created_at": "2025-02-20T14:45:22+00:00",
      "updated_at": "2025-11-13T12:10:05+00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 2,
    "last_page": 1
  }
}
```

**cURL Example**:

```bash
# List all
curl -X GET "http://your-app.test/api/running-numbers/list" \
  -H "Accept: application/json"

# Filter by type
curl -X GET "http://your-app.test/api/running-numbers/list?type=INVOICE" \
  -H "Accept: application/json"

# With pagination
curl -X GET "http://your-app.test/api/running-numbers/list?per_page=10" \
  -H "Accept: application/json"
```

## Authentication

### Using Laravel Sanctum

Update your configuration to require authentication:

```php
'api' => [
    'enabled' => true,
    'middleware' => ['api', 'auth:sanctum'],
],
```

Then include the bearer token in requests:

```bash
curl -X POST http://your-app.test/api/running-numbers/generate \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -d '{"type": "invoice"}'
```

### Using Custom Middleware

You can add your own authentication middleware:

```php
'api' => [
    'enabled' => true,
    'middleware' => ['api', 'custom.auth'],
],
```

## Rate Limiting

Add Laravel's rate limiting middleware:

```php
'api' => [
    'enabled' => true,
    'middleware' => ['api', 'throttle:60,1'], // 60 requests per minute
],
```

## Use Cases

### 1. Microservice Integration

Generate invoices from an external billing service:

```php
// In your billing microservice
$client = new \GuzzleHttp\Client();

$response = $client->post('https://main-app.com/api/running-numbers/generate', [
    'json' => [
        'type' => 'invoice',
        'scope' => $tenantId,
    ],
    'headers' => [
        'Authorization' => 'Bearer ' . $apiToken,
    ],
]);

$invoiceNumber = json_decode($response->getBody())->data->number;
```

### 2. Mobile App

Generate order numbers from a mobile app:

```swift
// Swift/iOS
let url = URL(string: "https://api.example.com/api/running-numbers/generate")!
var request = URLRequest(url: url)
request.httpMethod = "POST"
request.setValue("application/json", forHTTPHeaderField: "Content-Type")
request.setValue("Bearer \(apiToken)", forHTTPHeaderField: "Authorization")

let body: [String: Any] = [
    "type": "order",
    "scope": "mobile-app"
]
request.httpBody = try? JSONSerialization.data(withJSONObject: body)

URLSession.shared.dataTask(with: request) { data, response, error in
    // Handle response
}.resume()
```

### 3. Webhook Handler

Generate ticket numbers from external webhooks:

```php
// In your webhook controller
public function handleWebhook(Request $request)
{
    $client = new \GuzzleHttp\Client();

    $response = $client->post(config('app.url') . '/api/running-numbers/generate', [
        'json' => [
            'type' => 'ticket',
            'scope' => $request->input('channel'),
        ],
    ]);

    $ticketNumber = json_decode($response->getBody())->data->number;

    // Create ticket with the number
    Ticket::create([
        'ticket_number' => $ticketNumber,
        'subject' => $request->input('subject'),
    ]);
}
```

### 4. JavaScript Frontend

Generate references from a SPA:

```javascript
// React/Vue/Vanilla JS
async function generateReference() {
  try {
    const response = await fetch('/api/running-numbers/generate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
      },
      body: JSON.stringify({
        type: 'reference',
        scope: 'web-app',
      }),
    });

    const data = await response.json();

    if (data.success) {
      console.log('Generated:', data.data.number);
      return data.data.number;
    }
  } catch (error) {
    console.error('Failed to generate number:', error);
  }
}
```

## Error Handling

All endpoints return consistent JSON structure for errors:

```json
{
  "success": false,
  "message": "Error type",
  "error": "Detailed error message",
  "errors": {} // Validation errors only
}
```

**HTTP Status Codes**:

- `200 OK` - Successful GET request
- `201 Created` - Successful generation
- `400 Bad Request` - Invalid type or configuration
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation failed or max number reached
- `500 Internal Server Error` - Server error

## Best Practices

1. **Always use HTTPS in production**

```php
'api' => [
    'middleware' => ['api', 'https'],
],
```

2. **Implement authentication**

```php
'api' => [
    'middleware' => ['api', 'auth:sanctum'],
],
```

3. **Add rate limiting**

```php
'api' => [
    'middleware' => ['api', 'throttle:100,1'],
],
```

4. **Handle errors gracefully**

```php
try {
    $response = $client->post($url, ['json' => $data]);
    $result = json_decode($response->getBody(), true);

    if (!$result['success']) {
        Log::error('Number generation failed', $result);
        return fallbackNumber();
    }

    return $result['data']['number'];
} catch (\Exception $e) {
    Log::error('API call failed', ['error' => $e->getMessage()]);
    return fallbackNumber();
}
```

5. **Cache current numbers when appropriate**

```php
$currentNumber = Cache::remember("running-number:invoice", 300, function () use ($client) {
    $response = $client->get('/api/running-numbers/current?type=invoice');
    return json_decode($response->getBody())->data->current_number;
});
```

## Security Considerations

1. **API Token Security**: Store API tokens securely, never in source code
2. **CORS Configuration**: Configure CORS properly for browser-based clients
3. **Input Validation**: All inputs are validated server-side
4. **SQL Injection**: Protected by Laravel's query builder
5. **Rate Limiting**: Prevent abuse with throttling
6. **HTTPS Only**: Always use HTTPS in production environments

## Testing the API

Use the provided test suite as a reference:

```bash
./vendor/bin/pest tests/ApiTest.php
```

Or test manually with Postman, Insomnia, or HTTPie:

```bash
http POST http://your-app.test/api/running-numbers/generate \
  type=invoice \
  scope=retail
```

## Related Topics

- [Helper Functions](01-helper-functions.md) - Generate numbers programmatically
- [Model Integration](04-model-integration.md) - Automatic generation with Eloquent
- [Artisan Commands](06-artisan-commands.md) - Manage numbers via CLI
- [Configuration](../02-configuration/01-overview.md) - Configure API settings
