# Deployment

## Servers

| Environment | Domain | IP | SSH |
|-------------|--------|----|-----|
| **Production** | `hostbill.leapswitch.com` | `167.99.249.244` | `ssh -p 8463 root@167.99.249.244` |
| **Testing** | `vps.hostbill.leapswitch.com` | `45.120.138.29` | `ssh -p 1210 root@45.120.138.29` |

Module path on both servers: `/home/hostbill/public_html/includes/modules/Hosting/cloudpe/`

## Deploy module files

**Testing:**
```bash
rsync -avz --exclude='.git' --exclude='.claude' -e "ssh -p 1210" \
  modules/Hosting/cloudpe/ \
  root@45.120.138.29:/home/hostbill/public_html/includes/modules/Hosting/cloudpe/
```

**Production:**
```bash
rsync -avz --exclude='.git' --exclude='.claude' -e "ssh -p 8463" \
  modules/Hosting/cloudpe/ \
  root@167.99.249.244:/home/hostbill/public_html/includes/modules/Hosting/cloudpe/
```

> **Important:** Always use `rsync` with trailing slash on source path. Do NOT use `scp -r` as it creates a nested `cloudpe/cloudpe/` directory structure, causing HostBill to read stale files instead of the deployed ones.

## First-time setup

1. Deploy files to server
2. In HostBill admin: **Settings > Apps Connections** (`?cmd=servers`) - add a server with:
   - Name: `CloudPe VHI`
   - Host URL (`status_url`): CloudPe API base URL
   - App Id (`field1`): CloudPe application identifier
   - Session (`field2`): CloudPe session token
3. In HostBill admin: **Settings > Modules > Hosting** - activate the "CloudPe" module
4. Create a product under a CloudPe category and assign it to the CloudPe VHI server
5. Ensure clients have the `cloudpeid` custom field set to their CloudPe User ID
6. Admin panel accessible at `?cmd=cloudpe` or via **Extras > Plugins > CloudPe**

## Upgrading existing installs

When adding new `$info` flags (e.g. `extras_menu`, `havecron`), HostBill does NOT re-read `$info` from the class. The `upgrade()` method in `class.cloudpe.php` handles this automatically when `$version` is bumped - it updates the `settings` column in `hb_modules_configuration` and registers cron tasks.
