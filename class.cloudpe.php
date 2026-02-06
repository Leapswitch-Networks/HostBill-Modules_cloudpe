<?php

/**
 * Hosting/Provisioning module
 * @author Atul Mahankal
 * 
 * @see  http://dev.hostbillapp.com/dev-kit/provisioning-modules/
 * @src hostbill_plugin_devkit/hosting_2/includes/modules/Hosting/advancedexample/class.advancedexample.php
 */
class cloudpe extends HostingModule
{
    protected $modname = 'CloudPe';
    protected $version = '1.0.6';
    protected $description = 'CloudPe Provisioning module for HostBill which communicate with CloudPe VHI to create and manage Users & envirnments.';
    protected $client_data;

    /**
     * You can choose which fields to display in Settings->Apps section
     * by defining this variable
     * @var array
     */
    protected $serverFields = [
        'hostname' => false,
        'ip' => false,
        'maxaccounts' => false,
        'status_url' => true,
        'username' => false,
        'password' => false,
        'hash' => false,
        'ssl' => false,
        'nameservers' => false,
        'url' => true,

        // Custom fields
        'field1' => true,
        'field2' => true,
    ];

    /**
     * HostBill will replace default labels for server fields
     * with this variable configured
     * @var array
     */
    protected $serverFieldsDescription = [
        // 'hostname' => 'Host URL',
        // 'username' => 'User ID',
        // 'password' => 'Password',
        'status_url' => 'Host URL',
        'field1' => 'App Id',
        'field2' => 'Session',
    ];

    /**
     * Options presented during Service connecting with APP Connection
     * @var array
     */
    protected $options = array(
        // 'package' => array(
        //     'name' => 'Package type',
        //     'value' => '',
        //     'type' => 'select', //html select element
        //     'default' => array('package A', 'package B', 'package C'),
        // ),
        // 'memory' => array(
        //     'name' => 'Memory',
        //     'value' => '',
        //     'type' => 'select',
        //     'default' => array('1024MB', '2048MB')
        // ),
        // 'subdomain' => array(
        //     'name' => 'Subdomain prefix',
        //     'value' => '',
        //     'type' => 'input',
        //     'default' => "subdomain.",
        // ),
        // 'reseller' => array(
        //     'name' => 'Is this reseller?',
        //     'value' => false,
        //     'type' => 'check', //html input type='checkbox'
        //     'default' => false,
        // )
    );

    /**
     * Account details stored in HostBill database for each service account
     * from /?cmd=accounts&action=edit&id=XXX => Account Details Section
     * @var array
     */
    protected $details = array(
        // 'option1' => [
        //     'name' => 'username',
        //     'value' => false,
        //     'type' => 'hidden',
        //     'default' => false
        // ],
        // 'option2' => [
        //     'name' => 'password',
        //     'value' => false,
        //     'type' => 'hidden',
        //     'default' => false
        // ],
        // 'option3' => [
        //     'name' => 'domain',
        //     'value' => false,
        //     'type' => 'hidden',        // this field is required, so we have set the type to 'hidden' and its not visible.
        //     'default' => false
        // ],
        // 'option4' => [               // NEW FIELD declared here
        //     'name' => 'CloudPe ID',     // this name will be displayed as a label of this field
        //     'value' => false,
        //     'type' => 'input',
        //     'default' => false
        // ]
    );

    public function __construct()
    {
        parent::__construct();

        // Load available brands dynamically from HostBill MultiBrand
        // $brands = $this->getAvailableBrands();
        // $this->configuration['brand']['default'] = [''] + $brands;
    }


    /**
     * Every time when user click "Test Connection" button from
     */
    public function Connect($app_details)
    {
        $this->connection['status_url'] = $app_details['status_url'];
        $this->connection['field1'] = $app_details['field1'];
        $this->connection['field2'] = $app_details['field2'];

        hbm_log_system("Connect called", "CloudPe Module");
    }

    public function testConnection()
    {
        $path = 'marketplace/app/rest/getchecksum';
        if ($this->Send($path)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Perform account creation action
     * @return bool true on success, false on failure
     */
    public function Create()
    {
        // $this->details['option4']['value'] = $this->client_data['cloudpeid'];   // CloudPe ID saved

        // hbm_log_system("Create called", "CloudPe Module");

        $this->addInfo('Account has been created.');
        return true;
    }

    /**
     * Synchronize remote account.
     * This method should use $this->details $this->options, etc arrays to return
     * basic info about user
     * @return array|false
     */
    public function getSynchInfo()
    {
        hbm_log_system(sprintf("getSynchInfo called for account_id: %s", $this->account_details['id'] ?? 'unknown'), "CloudPe Sync");

        $client_data = $this->getClientDetails($this->account_details['client_id']);
        $cloudpeid = $client_data['client']['cloudpeid'];
        $path = 'billing/account/rest/getaccounts';
        $data = array(
            'filterField' => 'uid',
            'filterValue' => $cloudpeid,
        );

        if (!empty($cloudpeid)) {
            $user_details =  $this->Send($path, $data);
            // hbm_log_system(sprintf("getSynchInfo: user_details: %s", json_encode($user_details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)), "CloudPe Module");

            if (!empty($user_details['totalCount'])) {
                $api = new ApiWrapper();
                $params = [
                    'id' => $this->account_details['id'],
                ];

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


        return array(
            'user' => '',
            'domain' => '',
        );
    }

    public function Suspend()
    {
        $client_data = $this->getClientDetails($this->account_details['client_id']);
        // hbm_log_system(sprintf("Suspend: account_details: %s", json_encode($this->account_details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)), "CloudPe Module");
        // hbm_log_system(sprintf("Suspend: product_details: %s", json_encode($this->product_details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)), "CloudPe Module");
        // hbm_log_system(sprintf("Suspend: account_config: %s", json_encode($this->account_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)), "CloudPe Module");
        // hbm_log_system(sprintf("Suspend: client_data: %s", json_encode($client_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)), "CloudPe Module");
        // hbm_log_system(sprintf("Suspend: connection: %s", json_encode($this->connection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)), "CloudPe Module");

        $cloudpeid = $client_data['client']['cloudpeid'];
        $path = 'billing/account/rest/setaccountstatus';
        $data = array(
            'uid' => $cloudpeid,
            'status' => 2,
        );

        if (!empty($cloudpeid) && $this->Send($path, $data)) {
            $this->addInfo("Account Inactivated successfully.");
            return true;
        } else {
            return false;
        }
    }

    public function Unsuspend()
    {
        $client_data = $this->getClientDetails($this->account_details['client_id']);
        $cloudpeid = $client_data['client']['cloudpeid'];
        $path = 'billing/account/rest/setaccountstatus';
        $data = array(
            'uid' => $cloudpeid,
            'status' => 1,
        );

        if (!empty($cloudpeid) && $this->Send($path, $data)) {
            $this->addInfo("Account Activated successfully.");
            return true;
        } else {
            return false;
        }
    }

    public function Terminate()
    {
        // hbm_log_system("Terminate called", "CloudPe Module");
        return true;
    }

    // public function Renewal()
    // {
    //     // hbm_log_system("Renewal called", "CloudPe Module");
    //     return true;
    // }

    // public function ChangePackage()
    // {
    //     // hbm_log_system("ChangePackage called", "CloudPe Module");
    //     return true;
    // }

    // public function ChangePassword($newpassword)
    // {
    //     return true;
    // }

    /**
     * This method is OPTIONAL. in this example it is used to connect to the server and manage all the modules action with the API.
     *
     * Its public, because we can call it from addon class
     * @ignore
     */
    public function Send($path, $data = array(), $method = 'get')
    {
        $details = array(
            'url' => $this->connection['status_url'],
            'appid' => $this->connection['field1'],
            'session' => $this->connection['field2'],
            // 'client_id' => $this->details['option4']['value'],
        );

        $url = sprintf("%s/%s", $details['url'], $path);

        // $data = 'adminusername=' . $this->server_username . '&adminpassword=' . $this->server_password;
        // $data .= $data;

        $data['appid'] = $this->connection['field1'];
        $data['session'] = $this->connection['field2'];
        hbm_log_system(sprintf("CloudPe API data: %s", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)), "CloudPe Module");

        $curl = curl_init();                                // we are using cURL library here
        if ($method === 'POST') {
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            // curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
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
        $resp = json_decode($out, true);    // API returns data encoded in JSON

        // hbm_log_system(sprintf("CloudPe Send: %s", json_encode($resp, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)), "CloudPe Module");

        if (!empty($resp['error'])) {
            hbm_log_error(sprintf("%s", json_encode($resp, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)), "CloudPe Module");
            $this->addError(sprintf('%s', $resp['error']));
            return false;
        } else {
            return $resp;
        }
    }

    protected function getClientDetails($ID)
    {
        $api = new ApiWrapper();
        $params = [
            'id' => $ID,
        ];
        return $api->getClientDetails($params);
    }
}
