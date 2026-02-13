# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Module Overview

CloudPE is a HostBill hosting/provisioning module that integrates with CloudPE VHI (Virtual Hosting Infrastructure) for account status management and synchronization. It manages status (suspend/unsuspend/sync) for pre-existing CloudPE accounts rather than automating full account lifecycle.

**Type:** Hosting Module extending `HostingModule`
**Version:** 1.0.0

## Architecture

```
cloudpe/
├── class.cloudpe.php              # Main module (extends HostingModule)
├── cron/
│   └── class.cloudpe_controller.php   # Cron sync controller (extends HBController)
└── docs/
    ├── WORKFLOW.md                # Business workflow documentation
    └── IMPLEMENTATION.md          # Technical implementation details
```

### Key Components

**Main Module (`class.cloudpe.php`):**
- `Connect($app_details)` - Stores API credentials from server config
- `testConnection()` - Verifies API via `marketplace/app/rest/getchecksum`
- `Suspend()` / `Unsuspend()` - Set CloudPE account status (2=inactive, 1=active)
- `getSynchInfo()` - Fetches CloudPE status and updates HostBill account
- `Send($path, $data, $method)` - cURL wrapper for all API calls

**Cron Controller (`cron/class.cloudpe_controller.php`):**
- `call_Daily()` - Runs once daily, syncs all active CloudPE accounts

### Data Flow

1. HostBill client must have `cloudpeid` custom field set to their CloudPE User ID
2. Module retrieves `cloudpeid` via `getClientDetails()` → `ApiWrapper`
3. API calls use `appid` + `session` authentication from server config
4. Status mapping: CloudPE `status=1` → Active, `status=2` → Suspended, `isEnabled=0` → Terminated

## Server Configuration Fields

| Field | Maps To | Purpose |
|-------|---------|---------|
| `status_url` | Host URL | CloudPE API base URL |
| `field1` | App Id | CloudPE application identifier |
| `field2` | Session | CloudPE session token |

## API Endpoints Used

| Endpoint | Purpose |
|----------|---------|
| `marketplace/app/rest/getchecksum` | Connection test |
| `billing/account/rest/getaccounts` | Fetch account by `uid` filter |
| `billing/account/rest/setaccountstatus` | Update account status |

## Stub Methods (Not Fully Implemented)

- `Create()` - Returns success without API call (accounts created manually on CloudPE)
- `Terminate()` - Returns success without API call

## HostBill Framework Patterns

**Available in module methods:**
- `$this->account_details` - Account info (id, client_id, status)
- `$this->product_details` - Product info
- `$this->connection` - Server credentials (set by `Connect()`)

**Error handling:**
```php
$this->addError('Error message');  // Display to admin
$this->addInfo('Success message');
hbm_log_error($message, "CloudPe Module");  // System log
```

**API access:**
```php
$api = new ApiWrapper();
$api->getClientDetails(['id' => $client_id]);
$api->editAccountDetails(['id' => $account_id, 'status' => 'Active']);
```

## Development Notes

- Enable debug logging by uncommenting `hbm_log_system()` calls in `Send()` and other methods
- Logs appear in HostBill Admin → Reports → Logs → System Log with tag "CloudPe Module"
- The `$options` and `$details` arrays are empty but can be extended for product options and per-account storage
