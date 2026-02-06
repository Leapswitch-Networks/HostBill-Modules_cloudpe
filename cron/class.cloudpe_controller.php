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
     * Synchronize all CloudPe accounts that have cloudpeid set
     * Called every 5 minutes by HostBill cron
     */
    public function call_EveryRun()
    {
        echo "CloudPe every 5 minutes Sync: Starting..." . PHP_EOL;

        $api = new ApiWrapper();

        // Get all CloudPe accounts using internal API call
        // ref: https://api2.hostbillapp.com/accounts/getAccounts.html
        $response = $api->getAccounts([
            'filter' => ['name' => 'CloudPe']
        ]);

        $allAccounts = isset($response['accounts']) ? $response['accounts'] : [];

        $count = 0;
        foreach ($allAccounts as $acc) {
            // Only sync Active, Suspended, Pending accounts
            if (!in_array($acc['status'], ['Active', 'Suspended', 'Pending'])) {
                continue;
            }

            // Load account and call getSynchInfo (which checks for cloudpeid)
            $this->module->loadAccount($acc['id']);
            $this->module->getSynchInfo();
            $count++;
        }

        echo "CloudPe every 5 minutes Sync: Completed. Processed {$count} accounts." . PHP_EOL;

        return "CloudPe Sync: {$count} accounts processed.";
    }
}
