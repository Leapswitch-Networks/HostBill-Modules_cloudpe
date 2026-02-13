# CloudPe Provisioning Module for HostBill

A HostBill provisioning module that integrates with CloudPe VHI (Virtual Hosting Infrastructure) to manage user account status and synchronization.

## Overview

| Property    | Value                |
| ----------- | -------------------- |
| Module Type | Hosting/Provisioning |
| Version     | 1.0.0                |
| Author      | Atul Mahankal        |
| Base Class  | `HostingModule`      |

## Features

| Feature                | Status     | Description                                                |
| ---------------------- | ---------- | ---------------------------------------------------------- |
| Test Connection        | ✅ Working | Verify API connectivity with CloudPe VHI                   |
| Account Suspension     | ✅ Working | Suspend user accounts on CloudPe (status=2)                |
| Account Unsuspension   | ✅ Working | Reactivate suspended accounts (status=1)                   |
| Status Synchronization | ✅ Working | Daily cron sync of account status from CloudPe to HostBill  |
| Account Creation       | ⚠️ Stub    | Returns success without API call (manual linking required) |
| Account Termination    | ⚠️ Stub    | Returns success without API call                           |

## Prerequisites

- CloudPe user accounts must be **created manually** on CloudPe VHI
- Each HostBill client must have their CloudPe User ID stored in the `cloudpeid` custom field
- The module manages status (suspend/unsuspend/sync) for pre-existing CloudPe accounts

## Requirements

- HostBill (any recent version)
- CloudPe VHI instance with API access
- Valid App ID and Session credentials from CloudPe

## Installation

1. Copy the `cloudpe` folder to your HostBill installation:

   ```
   /path/to/hostbill/includes/modules/Hosting/cloudpe/
   ```

2. The folder should contain:

   ```
   cloudpe/
   ├── class.cloudpe.php
   └── cron/
       └── class.cloudpe_controller.php
   ```

3. Go to HostBill Admin → Settings → Modules → Hosting Modules
4. Activate the "CloudPe" module

## Configuration

### App (Server) Setup

Go to **Settings → Apps → Add New App** and configure:

| Field    | Description                                                        |
| -------- | ------------------------------------------------------------------ |
| Host URL | CloudPe VHI API base URL (e.g., `https://api.cloudpe.example.com`) |
| App Id   | Your CloudPe application identifier                                |
| Session  | CloudPe session token for API authentication                       |

### Product Setup

1. Go to **Settings → Products → Add/Edit Product**
2. Select "CloudPe" as the provisioning module
3. Link to the configured App (server)

### Custom Client Field (Required)

This module **requires** a custom client field `cloudpeid` to link HostBill clients to CloudPe users.

**Settings → Client Custom Fields → Add Field**

| Property    | Value           |
| ----------- | --------------- |
| Field Name  | `cloudpeid`     |
| Field Type  | Text Input      |
| Description | CloudPe User ID |

## How It Works

1. **Create CloudPe user manually** on CloudPe VHI
2. **Add client in HostBill** and set the `cloudpeid` custom field to the CloudPe User ID
3. **Create order/account** in HostBill - the module marks it as created (no API call)
4. **Suspend/Unsuspend** - module calls CloudPe API to change account status
5. **Synchronize** - daily cron fetches status from CloudPe and updates HostBill

## API Endpoints Used

| Action             | Endpoint                                | Method |
| ------------------ | --------------------------------------- | ------ |
| Test Connection    | `marketplace/app/rest/getchecksum`      | GET    |
| Get Accounts       | `billing/account/rest/getaccounts`      | GET    |
| Set Account Status | `billing/account/rest/setaccountstatus` | GET    |

## Workflow

See [docs/WORKFLOW.md](docs/WORKFLOW.md) for detailed workflow documentation.

## Implementation Details

See [docs/IMPLEMENTATION.md](docs/IMPLEMENTATION.md) for technical implementation details.

## Troubleshooting

### Connection Test Fails

- Verify the Host URL is correct and accessible
- Check App ID and Session credentials
- Ensure CloudPe API is responding

### Suspension/Unsuspension Fails

- Verify the client has a valid `cloudpeid` custom field value
- Ensure the CloudPe user exists with that ID
- Check HostBill system logs for API errors

### Account Sync Not Working

- Confirm `cloudpeid` is set for the client
- Check if CloudPe API returns data for that user ID
- Review logs for API response errors

## Logging

The module logs to HostBill's system log with the tag "CloudPe Module". Check logs at:

**Reports → Logs → System Log**

## Limitations

- Account creation is not automated - CloudPe users must be created manually
- Account termination does not delete users from CloudPe
- Requires manual linking of HostBill clients to CloudPe users via `cloudpeid`

## License

Proprietary - All rights reserved.
