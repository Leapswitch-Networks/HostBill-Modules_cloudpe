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
    var $module;

    /**
     * Template object
     * @var Smarty
     */
    var $template;

    /**
     * Called before any action
     */
    public function beforeCall($params)
    {
        $this->template->pageTitle = 'CloudPe Module';
        $this->template->module_template_dir = APPDIR_MODULES . 'Hosting' . DS . 'cloudpe' . DS . 'admin';
        $this->template->showtpl = 'default';
        $this->template->assign('modulename', 'cloudpe');
    }

    /**
     * Default action - shows accounts with cloudpeid
     */
    public function _default($params)
    {
        $api = new ApiWrapper();

        // Get all CloudPe accounts using internal API call
        // ref: https://api2.hostbillapp.com/accounts/getAccounts.html
        $response = $api->getAccounts([
            'filter' => ['name' => 'CloudPe']
        ]);

        $allAccounts = isset($response['accounts']) ? $response['accounts'] : [];

        // Get cloudpeid for each account's client
        $accountsWithCloudpeid = [];
        $accountsWithoutCloudpeid = [];

        foreach ($allAccounts as $acc) {
            // Get client details using internal API call
            // ref: https://api2.hostbillapp.com/clients/getClientDetails.html
            $clientData = $api->getClientDetails(['id' => $acc['client_id']]);
            $cloudpeid = isset($clientData['client']['cloudpeid']) ? $clientData['client']['cloudpeid'] : '';

            $acc['cloudpeid'] = $cloudpeid;

            if (!empty($cloudpeid)) {
                $accountsWithCloudpeid[] = $acc;
            } else {
                $accountsWithoutCloudpeid[] = $acc;
            }
        }

        // Assign variables to template
        $this->template->assign('totalAccounts', count($allAccounts));
        $this->template->assign('accountsWithCloudpeid', $accountsWithCloudpeid);
        $this->template->assign('accountsWithoutCloudpeid', $accountsWithoutCloudpeid);

        // Render template
        $this->template->render(APPDIR_MODULES . 'Hosting' . DS . 'cloudpe' . DS . 'admin' . DS . 'default.tpl', [], true);
    }
}
