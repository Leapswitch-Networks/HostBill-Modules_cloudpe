<?php

/**
 * Cron controller for CloudPe Hosting module
 * @see https://dev.hostbillapp.com/dev-kit/advanced-topics/adding-cron-to-your-modules/
 */
class cloudpe_controller extends HBController
{
    /**
     * Related module object (cloudpe)
     * @var cloudpe
     */
    var $module;

    /**
     * Legacy every-5-minute task (kept for backward compatibility)
     */
    public function call_EveryRun()
    {
        echo "CloudPe EveryRun: Finished." . PHP_EOL;
        return "CloudPe EveryRun: Finished.";
    }

    /**
     * Daily sync - synchronize all CloudPe accounts
     */
    public function call_Daily()
    {
        hbm_log_system("call_Daily called", "CloudPe Module");
        return $this->syncAllAccounts();
    }

    /**
     * Sync all CloudPe accounts that have cloudpeid set.
     * Self-contained: loads server config and calls CloudPe API directly.
     */
    private function syncAllAccounts()
    {
        echo "CloudPe Daily Sync: Starting..." . PHP_EOL;

        // Load CloudPe server config from database
        $db = HBRegistry::db();
        $stmt = $db->prepare("SELECT * FROM hb_servers WHERE name LIKE '%cloudpe%' OR name LIKE '%CloudPe%' LIMIT 1");
        $stmt->execute();
        $server = $stmt->fetch(PDO::FETCH_ASSOC);

        if (empty($server['status_url']) || empty($server['field1']) || empty($server['field2'])) {
            echo "CloudPe Daily Sync: No server config found. Skipping." . PHP_EOL;
            return "CloudPe Daily Sync: No server config.";
        }

        $apiUrl = $server['status_url'];
        $appid = $server['field1'];
        $session = $server['field2'];

        $api = new ApiWrapper();

        // Get all CloudPe accounts
        $response = $api->getAccounts([
            'filter' => ['name' => 'CloudPe']
        ]);

        $allAccounts = isset($response['accounts']) ? $response['accounts'] : [];

        // echo "CloudPe Daily Sync: Found " . count($allAccounts) . " Need to debug for sync process for class error.." . PHP_EOL;
        // return "CloudPe Daily Sync: Found " . count($allAccounts) . " accounts to process.";

        $count = 0;
        foreach ($allAccounts as $acc) {
            echo "Processing Account #{$acc['id']} (Client ID: {$acc['client_id']}, Status: {$acc['status']})..." . PHP_EOL;
            if (!in_array($acc['status'], ['Active', 'Suspended', 'Pending'])) {
                continue;
            }

            // Get client's cloudpeid
            $clientData = $api->getClientDetails(['id' => $acc['client_id']]);
            $cloudpeid = !empty($clientData['client']['cloudpeid']) ? $clientData['client']['cloudpeid'] : '';

            if (empty($cloudpeid)) {
                continue;
            }

            echo "  => CloudPe ID: " . ($cloudpeid ?: 'Not Set') . PHP_EOL; 
            
        //     // Call CloudPe API to get remote account status
        //     $data = [
        //         'filterField' => 'uid',
        //         'filterValue' => $cloudpeid,
        //         'appid' => $appid,
        //         'session' => $session,
        //     ];
        //     $query = http_build_query($data);
        //     $url = sprintf("%s/billing/account/rest/getaccounts?%s", $apiUrl, $query);

        //     $curl = curl_init();
        //     curl_setopt($curl, CURLOPT_URL, $url);
        //     curl_setopt($curl, CURLOPT_TIMEOUT, 100);
        //     curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //     $out = curl_exec($curl);
        //     curl_close($curl);

        //     $userDetails = json_decode($out, true);

        //     if (!empty($userDetails['totalCount'])) {
        //         $newStatus = null;
        //         if ($userDetails['array'][0]['isEnabled'] == 0) {
        //             $newStatus = 'Terminated';
        //         } elseif ($userDetails['array'][0]['status'] == 1) {
        //             $newStatus = 'Active';
        //         } else {
        //             $newStatus = 'Suspended';
        //         }

        //         // Only update if status changed
        //         if ($newStatus !== $acc['status']) {
        //             $api->editAccountDetails([
        //                 'id' => $acc['id'],
        //                 'status' => $newStatus,
        //             ]);
        //             echo "  Account #{$acc['id']}: {$acc['status']} -> {$newStatus}" . PHP_EOL;
        //         }
        //     }

            $count++;
        }

        echo "CloudPe Daily Sync: Completed. Processed {$count} accounts." . PHP_EOL;
        return "CloudPe Daily Sync: {$count} accounts processed.";
    }
}
