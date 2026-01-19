# CloudPe Module - Implementation Documentation

## Technical Overview

This document provides technical implementation details for developers working with or extending the CloudPe provisioning module.

## Class Structure

```php
class cloudpe extends HostingModule
{
    protected $modname = 'CloudPe';
    protected $version = '1.0.0';
    protected $description = '...';

    // Server field configuration
    protected $serverFields = [...];
    protected $serverFieldsDescription = [...];

    // Product options (currently empty)
    protected $options = [];

    // Account details (currently empty)
    protected $details = [];

    // Connection data (set by Connect())
    protected $connection = [];
}
```

## Server Fields Configuration

### Enabled Fields

```php
protected $serverFields = [
    'hostname' => false,      // Disabled
    'ip' => false,            // Disabled
    'maxaccounts' => false,   // Disabled
    'status_url' => true,     // Enabled - Used as Host URL
    'username' => false,      // Disabled
    'password' => false,      // Disabled
    'hash' => false,          // Disabled
    'ssl' => false,           // Disabled
    'nameservers' => false,   // Disabled
    'url' => true,            // Enabled
    'field1' => true,         // Enabled - Used as App Id
    'field2' => true,         // Enabled - Used as Session
];
```

### Field Labels

```php
protected $serverFieldsDescription = [
    'status_url' => 'Host URL',
    'field1' => 'App Id',
    'field2' => 'Session',
];
```

## Core Methods

### Connect($app_details)

Called before any operation to establish connection details.

```php
public function Connect($app_details)
{
    // Store connection parameters from App configuration
    $this->connection['status_url'] = $app_details['status_url'];  // Host URL
    $this->connection['field1'] = $app_details['field1'];          // App Id
    $this->connection['field2'] = $app_details['field2'];          // Session
}
```

**Parameters received:**
| Key | Description |
|-----|-------------|
| `status_url` | CloudPe API base URL |
| `field1` | Application ID |
| `field2` | Session token |

### testConnection()

Verifies API connectivity.

```php
public function testConnection()
{
    $path = 'marketplace/app/rest/getchecksum';
    return $this->Send($path) ? true : false;
}
```

**Returns:** `true` on success, `false` on failure

### Create()

Handles account provisioning.

```php
public function Create()
{
    $this->addInfo('Account has been created.');
    return true;
}
```

**Current behavior:** Returns success without external API call. Account creation is expected to happen externally, with `cloudpeid` linked manually.

**To implement full provisioning:**
```php
public function Create()
{
    $path = 'billing/account/rest/createaccount';
    $data = [
        'email' => $this->client_data['email'],
        'firstname' => $this->client_data['firstname'],
        'lastname' => $this->client_data['lastname'],
        // ... other fields
    ];

    $response = $this->Send($path, $data, 'POST');
    if ($response && isset($response['uid'])) {
        // Store CloudPe ID in client custom field
        $this->details['option4']['value'] = $response['uid'];
        $this->addInfo('Account has been created.');
        return true;
    }
    return false;
}
```

### Suspend()

Suspends (deactivates) user account on CloudPe.

```php
public function Suspend()
{
    $client_data = $this->getClientDetails($this->account_details['client_id']);
    $cloudpeid = $client_data['client']['cloudpeid'];

    $path = 'billing/account/rest/setaccountstatus';
    $data = [
        'uid' => $cloudpeid,
        'status' => 2,  // 2 = Suspended/Inactive
    ];

    if (!empty($cloudpeid) && $this->Send($path, $data)) {
        $this->addInfo("Account Inactivated successfully.");
        return true;
    }
    return false;
}
```

**CloudPe Status Codes:**
| Code | Status |
|------|--------|
| 1 | Active |
| 2 | Suspended/Inactive |

### Unsuspend()

Reactivates suspended user account.

```php
public function Unsuspend()
{
    $client_data = $this->getClientDetails($this->account_details['client_id']);
    $cloudpeid = $client_data['client']['cloudpeid'];

    $path = 'billing/account/rest/setaccountstatus';
    $data = [
        'uid' => $cloudpeid,
        'status' => 1,  // 1 = Active
    ];

    if (!empty($cloudpeid) && $this->Send($path, $data)) {
        $this->addInfo("Account Activated successfully.");
        return true;
    }
    return false;
}
```

### Terminate()

Handles account termination.

```php
public function Terminate()
{
    return true;  // Currently no API action
}
```

**To implement full termination:**
```php
public function Terminate()
{
    $client_data = $this->getClientDetails($this->account_details['client_id']);
    $cloudpeid = $client_data['client']['cloudpeid'];

    $path = 'billing/account/rest/deleteaccount';
    $data = ['uid' => $cloudpeid];

    if (!empty($cloudpeid) && $this->Send($path, $data)) {
        $this->addInfo("Account terminated successfully.");
        return true;
    }
    return false;
}
```

### getSynchInfo()

Synchronizes account status from CloudPe to HostBill.

```php
public function getSynchInfo()
{
    $client_data = $this->getClientDetails($this->account_details['client_id']);
    $cloudpeid = $client_data['client']['cloudpeid'];

    $path = 'billing/account/rest/getaccounts';
    $data = [
        'filterField' => 'uid',
        'filterValue' => $cloudpeid,
    ];

    if (!empty($cloudpeid)) {
        $user_details = $this->Send($path, $data);

        if (!empty($user_details['totalCount'])) {
            $api = new ApiWrapper();
            $params = ['id' => $this->account_details['id']];

            // Map CloudPe status to HostBill status
            if ($user_details['array'][0]['isEnabled'] == 0) {
                $params['status'] = 'Terminated';
            } elseif ($user_details['array'][0]['status'] == 1) {
                $params['status'] = 'Active';
            } else {
                $params['status'] = 'Suspended';
            }

            $api->editAccountDetails($params);
        }
    }

    return ['user' => '', 'domain' => ''];
}
```

## API Communication

### Send() Method

Handles all HTTP requests to CloudPe API.

```php
public function Send($path, $data = [], $method = 'get')
{
    // Build URL
    $url = sprintf("%s/%s", $this->connection['status_url'], $path);

    // Add authentication
    $data['appid'] = $this->connection['field1'];
    $data['session'] = $this->connection['field2'];

    // cURL request
    $curl = curl_init();

    if ($method === 'POST') {
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    } else {
        $query = http_build_query($data);
        curl_setopt($curl, CURLOPT_URL, sprintf("%s?%s", $url, $query));
    }

    curl_setopt($curl, CURLOPT_TIMEOUT, 100);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $out = curl_exec($curl);

    if ($out === false) {
        $this->addError(ucwords(curl_error($curl)));
    }

    curl_close($curl);
    $resp = json_decode($out, true);

    if (!empty($resp['error'])) {
        hbm_log_error($resp['error'], "CloudPe Module");
        $this->addError($resp['error']);
        return false;
    }

    return $resp;
}
```

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$path` | string | API endpoint path |
| `$data` | array | Request data (optional) |
| `$method` | string | HTTP method: 'get' or 'POST' |

**Returns:** Decoded JSON response array or `false` on error

### Authentication

All API requests include:
```php
$data['appid'] = $this->connection['field1'];   // App ID
$data['session'] = $this->connection['field2']; // Session token
```

## Helper Methods

### getClientDetails($ID)

Retrieves client information from HostBill API.

```php
protected function getClientDetails($ID)
{
    $api = new ApiWrapper();
    return $api->getClientDetails(['id' => $ID]);
}
```

**Returns:** Array with client data including custom fields:
```php
[
    'client' => [
        'id' => 123,
        'email' => 'user@example.com',
        'firstname' => 'John',
        'lastname' => 'Doe',
        'cloudpeid' => 'CP-12345',  // Custom field
        // ... other fields
    ]
]
```

## Pre-loaded Variables

Variables available in module methods:

| Variable | Description |
|----------|-------------|
| `$this->account_details` | Account info (id, client_id, product_id, status, etc.) |
| `$this->product_details` | Product info (name, module, etc.) |
| `$this->client_data` | Client info (in Create() method) |
| `$this->connection` | Server connection details |

## Logging

### System Logs
```php
hbm_log_system($message, "CloudPe Module");
```

### Error Logs
```php
hbm_log_error($message, "CloudPe Module");
```

### Debug Logging (commented out)
```php
// Uncomment in development:
hbm_log_system(sprintf("Debug: %s", json_encode($data, JSON_PRETTY_PRINT)), "CloudPe Module");
```

## Error Handling

```php
// Add error message (displayed to admin)
$this->addError('Error message');

// Add success message (displayed to admin)
$this->addInfo('Success message');
```

## Extending the Module

### Adding Product Options

```php
protected $options = [
    'package' => [
        'name' => 'Package Type',
        'value' => '',
        'type' => 'select',
        'default' => ['Basic', 'Standard', 'Premium'],
    ],
    'disk_space' => [
        'name' => 'Disk Space (GB)',
        'value' => '',
        'type' => 'input',
        'default' => '10',
    ],
];
```

### Adding Account Details

```php
protected $details = [
    'option1' => [
        'name' => 'CloudPe User ID',
        'value' => false,
        'type' => 'input',
        'default' => false
    ],
    'option2' => [
        'name' => 'Environment ID',
        'value' => false,
        'type' => 'input',
        'default' => false
    ],
];
```

### Adding Client Area Controller

Create `user/class.cloudpe_controller.php`:

```php
<?php
class cloudpe_controller extends HBController
{
    public function _default($request)
    {
        // Load module
        $this->module->Connect($this->module->getAppDetails());

        // Get account info from CloudPe
        $info = $this->module->getAccountInfo();

        // Assign to template
        $this->template->assign('info', $info);
        $this->template->render('cloudpe_client.tpl');
    }
}
```

### Adding Admin Area Controller

Create `admin/class.cloudpe_controller.php`:

```php
<?php
class cloudpe_controller extends HBController
{
    public function _default($request)
    {
        // Admin panel functionality
    }
}
```

## CloudPe API Reference

### Endpoints Used

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `marketplace/app/rest/getchecksum` | GET | Test connectivity |
| `billing/account/rest/getaccounts` | GET | Fetch account details |
| `billing/account/rest/setaccountstatus` | GET | Update account status |

### Request Format

```
GET {host_url}/{endpoint}?appid={app_id}&session={session}&{params}
```

### Response Format

**Success:**
```json
{
    "totalCount": 1,
    "array": [
        {
            "uid": "CP-12345",
            "email": "user@example.com",
            "status": 1,
            "isEnabled": 1
        }
    ]
}
```

**Error:**
```json
{
    "error": "Error message description"
}
```

## Security Considerations

1. **Credentials Storage**: App credentials stored in HostBill database (encrypted)
2. **API Communication**: Use HTTPS for production
3. **Session Tokens**: Rotate session tokens periodically
4. **Input Validation**: Validate all inputs before API calls
5. **Error Messages**: Avoid exposing sensitive info in error messages
