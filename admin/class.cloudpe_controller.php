<?php

/**
 * Admin controller for CloudPe Hosting module
 * Access: ?cmd=cloudpe
 * @see https://dev.hostbillapp.com/dev-kit/advanced-topics/hostbill-controllers/
 */
class cloudpe_controller extends HBController
{
    /**
     * Related module object (cloudpe)
     * @var cloudpe
     */
    public $module;

    /**
     * Template object
     * @var Smarty
     */
    public $template;

    /**
     * Cached server credentials
     * @var array
     */
    private $serverConfig = null;

    /**
     * Called before any action
     */
    public function beforeCall($params)
    {
        $this->template->pageTitle = 'CloudPe Module';
        $this->template->module_template_dir = APPDIR_MODULES . 'Hosting' . DS . 'cloudpe' . DS . 'admin';
        $this->template->showtpl = 'default';
        $this->template->assign('modulename', 'cloudpe');

        // Load CloudPE server config once
        $this->loadCloudPeServerConfig();
    }

    /**
     * Load CloudPE server/app configuration
     */
    private function loadCloudPeServerConfig()
    {
        // Get CloudPE app from database using name column
        $db = HBRegistry::db();
        $stmt = $db->prepare("SELECT * FROM hb_servers WHERE name LIKE '%cloudpe%' OR name LIKE '%CloudPe%' LIMIT 1");
        $stmt->execute();
        $server = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!empty($server)) {
            $this->serverConfig = [
                'url' => !empty($server['status_url']) ? $server['status_url'] : '',
                'appid' => !empty($server['field1']) ? $server['field1'] : '',
                'session' => !empty($server['field2']) ? $server['field2'] : '',
            ];
        }
    }

    /**
     * Get account sync info directly from database
     * @param int $accountId Account ID
     * @return array ['sync_date' => string, 'sync_status' => string]
     */
    private function getAccountSyncFromDb($accountId)
    {
        $db = HBRegistry::db();

        $syncDate = '';
        $syncStatus = '';

        // Get sync info from hb_accounts table (synch_date and synch_error columns)
        try {
            $stmt = $db->prepare("SELECT synch_date, synch_error FROM hb_accounts WHERE id = ? LIMIT 1");
            $stmt->execute([$accountId]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($account) {
                // Get synch_date (HostBill uses 'synch' with 'h')
                if (isset($account['synch_date']) && $account['synch_date'] != '0000-00-00 00:00:00') {
                    $syncDate = $account['synch_date'];

                    // Default to Successful if we have a valid sync date
                    // Only mark as Failed if synch_error is explicitly set to non-zero value
                    $syncStatus = 'Successful';
                    if (isset($account['synch_error']) && $account['synch_error'] != '0') {
                        $syncStatus = 'Failed';
                    }
                }
            }
        } catch (Exception $e) {
            hbm_log_system("hb_accounts query error: " . $e->getMessage(), "CloudPe Module");
        }

        return ['sync_date' => $syncDate, 'sync_status' => $syncStatus];
    }

    /**
     * Default action - shows clients with CloudPe brand and their services
     */
    public function _default($params)
    {
        $api = new ApiWrapper();

        // Step 1: Get all clients (we'll filter by groupid after getting full details)
        $clientsResponse = $api->getClients([]);
        $allClientsRaw = !empty($clientsResponse['clients']) ? $clientsResponse['clients'] : [];

        // Step 2: Get all CloudPe accounts
        $accountsResponse = $api->getAccounts([
            'filter' => ['name' => 'CloudPe']
        ]);
        $allAccounts = !empty($accountsResponse['accounts']) ? $accountsResponse['accounts'] : [];

        // Index accounts by client_id
        $accountsByClient = [];
        if (!empty($allAccounts)) {
            foreach ($allAccounts as $acc) {
                if (empty($acc['client_id'])) {
                    continue;
                }
                $clientId = $acc['client_id'];
                if (!isset($accountsByClient[$clientId])) {
                    $accountsByClient[$clientId] = [];
                }
                $accountsByClient[$clientId][] = $acc;
            }
        }

        // Step 3: Build client lists
        $clientsWithCloudpeid = [];
        $clientsWithoutCloudpeid = [];

        foreach ($allClientsRaw as $client) {
            if (empty($client['id'])) {
                continue;
            }
            $clientId = $client['id'];

            // Get full client details to access custom fields like cloudpeid and groupid
            $clientData = $api->getClientDetails(['id' => $clientId]);
            $clientFull = !empty($clientData['client']) ? $clientData['client'] : $client;

            // Filter: only include clients with CloudPe brand (groupid = 1)
            $groupId = 0;
            foreach (['groupid', 'group_id', 'brand_id', 'brandid', 'brand', 'client_group'] as $field) {
                if (isset($clientFull[$field]) && $clientFull[$field] !== '' && $clientFull[$field] !== null) {
                    $groupId = (int)$clientFull[$field];
                    break;
                }
            }

            // Skip clients not in CloudPe group (groupid = 1)
            if ($groupId !== 1) {
                continue;
            }

            $cloudpeid = !empty($clientFull['cloudpeid']) ? $clientFull['cloudpeid'] : '';

            // Get this client's CloudPe services
            $clientAccounts = !empty($accountsByClient[$clientId]) ? $accountsByClient[$clientId] : [];

            // Build services array
            $services = [];
            if (!empty($clientAccounts)) {
                foreach ($clientAccounts as $acc) {
                    if (empty($acc['id'])) {
                        continue;
                    }
                    // Get sync info directly from database (not available in API)
                    $syncInfo = $this->getAccountSyncFromDb($acc['id']);
                    $syncDate = !empty($syncInfo['sync_date']) ? $syncInfo['sync_date'] : '';
                    $syncStatus = !empty($syncInfo['sync_status']) ? $syncInfo['sync_status'] : '';

                    $services[] = [
                        'id' => $acc['id'],
                        'status' => !empty($acc['status']) ? $acc['status'] : '',
                        'lastupdate' => $syncDate,
                        'sync_status' => $syncStatus,
                    ];
                }
            }

            // Build client row
            $clientRow = [
                'client_id' => $clientId,
                'email' => !empty($clientFull['email']) ? $clientFull['email'] : '',
                'company' => !empty($clientFull['companyname']) ? $clientFull['companyname'] : '',
                'cloudpeid' => $cloudpeid,
                'services' => $services,
            ];

            // WITH cloudpeid = has cloudpeid AND has services
            // WITHOUT cloudpeid = no cloudpeid OR no services
            if (!empty($cloudpeid) && !empty($services)) {
                $clientsWithCloudpeid[] = $clientRow;
            } else {
                $clientsWithoutCloudpeid[] = $clientRow;
            }
        }

        // Sort by client_id descending
        usort($clientsWithCloudpeid, function($a, $b) {
            return $b['client_id'] - $a['client_id'];
        });
        usort($clientsWithoutCloudpeid, function($a, $b) {
            return $b['client_id'] - $a['client_id'];
        });

        // Assign variables to template
        // Total accounts = total clients in CloudPe group (with + without cloudpeid)
        $this->template->assign('totalAccounts', count($clientsWithCloudpeid) + count($clientsWithoutCloudpeid));
        $this->template->assign('totalClients', count($clientsWithCloudpeid) + count($clientsWithoutCloudpeid));
        $this->template->assign('clientsWithCloudpeid', $clientsWithCloudpeid);
        $this->template->assign('clientsWithoutCloudpeid', $clientsWithoutCloudpeid);
        $this->template->assign('hasServerConfig', (bool)$this->serverConfig);

        // Render template
        $this->template->render(APPDIR_MODULES . 'Hosting' . DS . 'cloudpe' . DS . 'admin' . DS . 'default.tpl', [], true);
    }

}
