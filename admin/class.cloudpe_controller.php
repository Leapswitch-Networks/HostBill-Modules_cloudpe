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
     * CloudPe brand_id in hb_brands
     * @var int
     */
    private $cloudpeBrandId = 0;

    /**
     * Called before any action
     */
    public function beforeCall($params)
    {
        $this->template->pageTitle = 'CloudPe Module';
        $this->template->module_template_dir = APPDIR_MODULES . 'Hosting' . DS . 'cloudpe' . DS . 'admin';
        $this->template->showtpl = 'default';
        $this->template->assign('modulename', 'cloudpe');

        $this->loadCloudPeServerConfig();
        $this->resolveCloudPeBrandId();
    }

    /**
     * Load CloudPE server/app configuration
     */
    private function loadCloudPeServerConfig()
    {
        $db = HBRegistry::db();
        $stmt = $db->prepare("SELECT * FROM hb_servers WHERE name LIKE '%cloudpe%' OR name LIKE '%CloudPe%' LIMIT 1");
        $stmt->execute();
        $server = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (!empty($server)) {
            $this->serverConfig = [
                'url' => !empty($server['status_url']) ? $server['status_url'] : '',
                'appid' => !empty($server['field1']) ? $server['field1'] : '',
                'session' => !empty($server['field2']) ? $server['field2'] : '',
            ];
        }
    }

    /**
     * Resolve CloudPe brand_id from hb_brands table
     */
    private function resolveCloudPeBrandId()
    {
        $db = HBRegistry::db();
        $stmt = $db->prepare("SELECT brand_id FROM hb_brands WHERE LOWER(name) LIKE '%cloudpe%' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row) {
            $this->cloudpeBrandId = (int)$row['brand_id'];
        }
    }

    /**
     * Default action - shows clients with summary cards and filterable table
     */
    public function _default($params)
    {
        $db = HBRegistry::db();

        // 1. Get all brands keyed by id
        $stmt = $db->query("SELECT brand_id, name FROM hb_brands ORDER BY name");
        $brandRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $brandNames = [];
        foreach ($brandRows as $row) {
            $brandNames[(int)$row['brand_id']] = $row['name'];
        }

        // 2. Get cloudpeid custom field id
        $cloudpeFieldId = 0;
        $stmt = $db->prepare("SELECT id FROM hb_client_fields WHERE code = 'cloudpeid' LIMIT 1");
        $stmt->execute();
        $fieldRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if ($fieldRow) {
            $cloudpeFieldId = (int)$fieldRow['id'];
        }

        // 3. Get all clients with brand and cloudpeid in one query
        $sql = "SELECT
                    ca.id AS client_id,
                    ca.email,
                    ca.brand_id,
                    cd.firstname,
                    cd.lastname,
                    cd.companyname,
                    COALESCE(cfv.value, '') AS cloudpeid
                FROM hb_client_access ca
                INNER JOIN hb_client_details cd ON cd.id = ca.id
                LEFT JOIN hb_client_fields_values cfv ON cfv.client_id = ca.id AND cfv.field_id = ?
                ORDER BY ca.id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$cloudpeFieldId]);
        $allClients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        // 4. Get all CloudPe accounts with sync info
        //    Chain: hb_accounts.server_id -> hb_servers.id -> hb_servers.default_module -> hb_modules_configuration.id
        $sql = "SELECT
                    a.id AS account_id,
                    a.client_id,
                    a.status,
                    a.synch_date,
                    a.synch_error
                FROM hb_accounts a
                INNER JOIN hb_servers s ON s.id = a.server_id
                INNER JOIN hb_modules_configuration mc ON mc.id = s.default_module
                WHERE mc.module = 'cloudpe'
                ORDER BY a.id";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $accountRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        // Index accounts by client_id
        $accountsByClient = [];
        $totalAccountCount = 0;
        foreach ($accountRows as $acc) {
            $cid = (int)$acc['client_id'];
            if (!isset($accountsByClient[$cid])) {
                $accountsByClient[$cid] = [];
            }
            $accountsByClient[$cid][] = $acc;
            $totalAccountCount++;
        }

        // 5. Build flat rows (one row per service, or one row for clients without services)
        $rows = [];
        $countWithoutCloudpeid = 0;
        $countOtherBrand = 0;
        $countWithoutAccount = 0;

        foreach ($allClients as $c) {
            $clientId = (int)$c['client_id'];
            $brandId = (int)$c['brand_id'];
            $cloudpeid = trim($c['cloudpeid']);
            $isCloudpeBrand = ($brandId === $this->cloudpeBrandId);
            $clientAccounts = isset($accountsByClient[$clientId]) ? $accountsByClient[$clientId] : [];

            // Only include clients that are in CloudPe brand OR have CloudPe accounts
            if (!$isCloudpeBrand && empty($clientAccounts)) {
                continue;
            }

            // Compute tags
            $tags = [];
            if (!empty($clientAccounts)) {
                $tags[] = 'has_account';
            } else {
                $countWithoutAccount++;
                $tags[] = 'no_account';
            }
            if ($isCloudpeBrand && empty($cloudpeid)) {
                $countWithoutCloudpeid++;
                $tags[] = 'no_uid';
            }
            if (!$isCloudpeBrand) {
                $countOtherBrand++;
                $tags[] = 'other_brand';
            }
            $tagStr = implode(' ', $tags);

            $brandName = isset($brandNames[$brandId]) ? $brandNames[$brandId] : 'Unknown';

            if (!empty($clientAccounts)) {
                $svcCount = count($clientAccounts);
                foreach ($clientAccounts as $idx => $acc) {
                    $syncDate = '';
                    $syncStatus = '';
                    if (!empty($acc['synch_date']) && $acc['synch_date'] !== '0000-00-00 00:00:00') {
                        $syncDate = $acc['synch_date'];
                        $syncStatus = 'Successful';
                        if (!empty($acc['synch_error']) && $acc['synch_error'] !== '0') {
                            $syncStatus = 'Failed';
                        }
                    }

                    $rows[] = [
                        'client_id' => $clientId,
                        'email' => $c['email'],
                        'company' => !empty($c['companyname']) ? $c['companyname'] : '',
                        'brand' => $brandName,
                        'brand_id' => $brandId,
                        'cloudpeid' => $cloudpeid,
                        'account_id' => $acc['account_id'],
                        'status' => $acc['status'],
                        'sync_date' => $syncDate,
                        'sync_status' => $syncStatus,
                        'is_first' => ($idx === 0) ? 1 : 0,
                        'service_count' => $svcCount,
                        'tags' => $tagStr,
                    ];
                }
            } else {
                $rows[] = [
                    'client_id' => $clientId,
                    'email' => $c['email'],
                    'company' => !empty($c['companyname']) ? $c['companyname'] : '',
                    'brand' => $brandName,
                    'brand_id' => $brandId,
                    'cloudpeid' => $cloudpeid,
                    'account_id' => '',
                    'status' => '',
                    'sync_date' => '',
                    'sync_status' => '',
                    'is_first' => 1,
                    'service_count' => 1,
                    'tags' => $tagStr,
                ];
            }
        }

        // Assign to template
        $this->template->assign('rows', $rows);
        $this->template->assign('totalAccounts', $totalAccountCount);
        $this->template->assign('countWithoutCloudpeid', $countWithoutCloudpeid);
        $this->template->assign('countOtherBrand', $countOtherBrand);
        $this->template->assign('countWithoutAccount', $countWithoutAccount);
        $this->template->assign('cloudpeBrandId', $this->cloudpeBrandId);
        $this->template->assign('cloudpeBrandName', isset($brandNames[$this->cloudpeBrandId]) ? $brandNames[$this->cloudpeBrandId] : 'CloudPe');
        $this->template->assign('hasServerConfig', (bool)$this->serverConfig);

        $this->template->render(APPDIR_MODULES . 'Hosting' . DS . 'cloudpe' . DS . 'admin' . DS . 'default.tpl', [], true);
    }

}
