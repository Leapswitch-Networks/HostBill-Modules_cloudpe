# CloudPe Module - Workflow Documentation

## Overview

This document describes the workflows and processes for the CloudPe provisioning module integration with HostBill.

## Architecture

```
┌─────────────────┐      ┌─────────────────┐      ┌─────────────────┐
│    HostBill     │ ──── │  CloudPe Module │ ──── │   CloudPe VHI   │
│   Admin/Cron    │      │  class.cloudpe  │      │      API        │
└─────────────────┘      └─────────────────┘      └─────────────────┘
```

## Workflow Diagrams

### 1. Initial Setup Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                        INITIAL SETUP                                 │
└─────────────────────────────────────────────────────────────────────┘

1. Install Module
   └── Copy cloudpe/ to includes/modules/Hosting/

2. Configure App (Server)
   └── Settings → Apps → Add New
       ├── Host URL: CloudPe API base URL
       ├── App Id: Application identifier
       └── Session: API session token

3. Test Connection
   └── Click "Test Connection" button
       └── Module calls: GET marketplace/app/rest/getchecksum
           ├── Success → Connection verified ✓
           └── Failure → Check credentials/URL

4. Create Product
   └── Settings → Products → Add/Edit
       ├── Select Module: CloudPe
       └── Select App: Configured CloudPe App

5. Configure Client Custom Field
   └── Settings → Client Custom Fields
       └── Add field: cloudpeid (Text)
```

### 2. Account Provisioning Flow (Create)

```
┌─────────────────────────────────────────────────────────────────────┐
│                    ACCOUNT CREATION WORKFLOW                         │
└─────────────────────────────────────────────────────────────────────┘

  Client Orders Product
         │
         ▼
  ┌──────────────┐
  │ Order Created │
  │  (Pending)    │
  └──────┬───────┘
         │
         ▼
  ┌──────────────┐
  │Payment Received│
  │   (if auto)   │
  └──────┬───────┘
         │
         ▼
  ┌──────────────┐      ┌──────────────┐
  │  HostBill    │──────│  Create()    │
  │ Calls Module │      │   Method     │
  └──────────────┘      └──────┬───────┘
                               │
                               ▼
                        ┌──────────────┐
                        │ Account Set  │
                        │   Active     │
                        └──────────────┘

Note: Current implementation marks account as created without
external API call. CloudPe user should be pre-created and
linked via cloudpeid custom field.
```

### 3. Account Suspension Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                    ACCOUNT SUSPENSION WORKFLOW                       │
└─────────────────────────────────────────────────────────────────────┘

  Admin/Automation Triggers Suspension
         │
         ▼
  ┌──────────────┐
  │  HostBill    │
  │ Calls Module │
  └──────┬───────┘
         │
         ▼
  ┌──────────────────────────────────┐
  │          Suspend() Method         │
  └──────────────┬───────────────────┘
                 │
                 ▼
  ┌──────────────────────────────────┐
  │   Get Client Details via API     │
  │   $api->getClientDetails()       │
  └──────────────┬───────────────────┘
                 │
                 ▼
  ┌──────────────────────────────────┐
  │   Extract cloudpeid from client  │
  └──────────────┬───────────────────┘
                 │
         ┌───────┴───────┐
         │               │
    cloudpeid        cloudpeid
      empty           exists
         │               │
         ▼               ▼
  ┌──────────┐   ┌───────────────────────────────┐
  │  Return  │   │   Call CloudPe API            │
  │  false   │   │   POST billing/account/rest/  │
  └──────────┘   │        setaccountstatus       │
                 │   { uid: cloudpeid, status: 2 }│
                 └───────────────┬───────────────┘
                                 │
                         ┌───────┴───────┐
                         │               │
                      Success         Failure
                         │               │
                         ▼               ▼
                  ┌──────────┐   ┌──────────┐
                  │ Account  │   │  Error   │
                  │Suspended │   │ Logged   │
                  │  in VHI  │   │          │
                  └──────────┘   └──────────┘
```

### 4. Account Unsuspension Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                   ACCOUNT UNSUSPENSION WORKFLOW                      │
└─────────────────────────────────────────────────────────────────────┘

  Admin/Automation Triggers Unsuspension
         │
         ▼
  ┌──────────────┐
  │  HostBill    │
  │ Calls Module │
  └──────┬───────┘
         │
         ▼
  ┌──────────────────────────────────┐
  │        Unsuspend() Method         │
  └──────────────┬───────────────────┘
                 │
                 ▼
  ┌──────────────────────────────────┐
  │   Get Client Details via API     │
  └──────────────┬───────────────────┘
                 │
                 ▼
  ┌──────────────────────────────────┐
  │   Extract cloudpeid from client  │
  └──────────────┬───────────────────┘
                 │
         ┌───────┴───────┐
         │               │
    cloudpeid        cloudpeid
      empty           exists
         │               │
         ▼               ▼
  ┌──────────┐   ┌───────────────────────────────┐
  │  Return  │   │   Call CloudPe API            │
  │  false   │   │   POST billing/account/rest/  │
  └──────────┘   │        setaccountstatus       │
                 │   { uid: cloudpeid, status: 1 }│
                 └───────────────┬───────────────┘
                                 │
                         ┌───────┴───────┐
                         │               │
                      Success         Failure
                         │               │
                         ▼               ▼
                  ┌──────────┐   ┌──────────┐
                  │ Account  │   │  Error   │
                  │ Active   │   │ Logged   │
                  │  in VHI  │   │          │
                  └──────────┘   └──────────┘
```

### 5. Account Synchronization Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                 ACCOUNT SYNCHRONIZATION WORKFLOW                     │
└─────────────────────────────────────────────────────────────────────┘

  Admin Clicks "Synchronize" or Cron Job Runs
         │
         ▼
  ┌──────────────┐
  │  HostBill    │
  │ Calls Module │
  └──────┬───────┘
         │
         ▼
  ┌──────────────────────────────────┐
  │       getSynchInfo() Method       │
  └──────────────┬───────────────────┘
                 │
                 ▼
  ┌──────────────────────────────────┐
  │   Get Client Details via API     │
  │   Extract cloudpeid              │
  └──────────────┬───────────────────┘
                 │
         ┌───────┴───────┐
         │               │
    cloudpeid        cloudpeid
      empty           exists
         │               │
         ▼               ▼
  ┌──────────┐   ┌───────────────────────────────┐
  │  Return  │   │   Call CloudPe API            │
  │  empty   │   │   GET billing/account/rest/   │
  │  array   │   │        getaccounts            │
  └──────────┘   │   { filterField: uid,         │
                 │     filterValue: cloudpeid }  │
                 └───────────────┬───────────────┘
                                 │
                                 ▼
                 ┌───────────────────────────────┐
                 │   Parse Response              │
                 │   Check totalCount > 0       │
                 └───────────────┬───────────────┘
                                 │
                                 ▼
                 ┌───────────────────────────────┐
                 │   Determine Status:           │
                 │   isEnabled=0 → Terminated   │
                 │   status=1    → Active       │
                 │   otherwise   → Suspended    │
                 └───────────────┬───────────────┘
                                 │
                                 ▼
                 ┌───────────────────────────────┐
                 │   Update HostBill Account     │
                 │   $api->editAccountDetails()  │
                 └───────────────────────────────┘
```

### 6. Connection Test Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                    CONNECTION TEST WORKFLOW                          │
└─────────────────────────────────────────────────────────────────────┘

  Admin Clicks "Test Connection" in App Settings
         │
         ▼
  ┌──────────────────────────────────┐
  │    Connect() Method Called       │
  │    Store connection details:     │
  │    - status_url (Host URL)       │
  │    - field1 (App Id)             │
  │    - field2 (Session)            │
  └──────────────┬───────────────────┘
                 │
                 ▼
  ┌──────────────────────────────────┐
  │   testConnection() Method Called  │
  └──────────────┬───────────────────┘
                 │
                 ▼
  ┌──────────────────────────────────┐
  │   Send() API Request             │
  │   GET marketplace/app/rest/      │
  │       getchecksum                │
  └──────────────┬───────────────────┘
                 │
         ┌───────┴───────┐
         │               │
      Success         Failure
         │               │
         ▼               ▼
  ┌──────────┐   ┌──────────────┐
  │  Return  │   │ Return false │
  │   true   │   │ + Log Error  │
  └──────────┘   └──────────────┘
```

## Status Mapping

| CloudPe Status | HostBill Status |
|----------------|-----------------|
| `isEnabled = 0` | Terminated |
| `status = 1` | Active |
| `status = 2` | Suspended |
| Other | Suspended |

## Event Triggers

| HostBill Event | Module Method | CloudPe Action |
|----------------|---------------|----------------|
| Account Creation | `Create()` | (No API call - account marked as created) |
| Account Suspension | `Suspend()` | Set status = 2 (Inactive) |
| Account Unsuspension | `Unsuspend()` | Set status = 1 (Active) |
| Account Termination | `Terminate()` | (No API call - returns true) |
| Manual/Cron Sync | `getSynchInfo()` | Fetch account status |
| Test Connection | `testConnection()` | Verify API connectivity |

## Error Handling

All API errors are:
1. Logged to HostBill system log with tag "CloudPe Module"
2. Displayed to admin via `$this->addError()` method
3. Returns `false` to indicate failure to HostBill

## Best Practices

1. **Always configure cloudpeid**: Ensure all clients have the CloudPe user ID set
2. **Regular sync**: Use HostBill cron to periodically sync account statuses
3. **Monitor logs**: Check "CloudPe Module" logs for API errors
4. **Test connection**: Always test after configuration changes
