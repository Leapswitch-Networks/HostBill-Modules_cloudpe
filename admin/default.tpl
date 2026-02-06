<div class="alert alert-info">
    <strong>Summary:</strong>
    Total CloudPe Accounts: {$totalAccounts} |
    Clients with cloudpeid: {$clientsWithCloudpeid|@count} |
    Clients without cloudpeid: {$clientsWithoutCloudpeid|@count}
    {if !$hasServerConfig}
        | <span class="text-danger">Warning: No CloudPE App configured</span>
    {/if}
</div>

<h5 style="padding: 0 15px;">Clients WITH cloudpeid (will be synced by cron)</h5>
<table class="glike hover">
    <thead>
        <tr>
            <th>ID</th>
            <th>Email</th>
            <th>Company</th>
            <th>Service</th>
            <th>Status</th>
            <th>Sync Updated at</th>
            <th>Sync status</th>
            <th>UID</th>
        </tr>
    </thead>
    <tbody>
        {if $clientsWithCloudpeid|@count == 0}
            <tr>
                <td colspan="8" class="text-center">No clients found with cloudpeid</td>
            </tr>
        {else}
            {foreach from=$clientsWithCloudpeid item=client}
                <tr>
                    <td><a href="?cmd=clients&action=show&id={$client.client_id}">{$client.client_id}</a></td>
                    <td>{$client.email}</td>
                    <td>{$client.company|default:'-'}</td>
                    <td>
                        {foreach from=$client.services item=svc name=svcloop}
                            <a href="?cmd=accounts&action=edit&id={$svc.id}">{$svc.id}</a>{if !$smarty.foreach.svcloop.last}, {/if}
                        {/foreach}
                    </td>
                    <td>
                        {foreach from=$client.services item=svc name=svcloop}
                            {if $svc.status == 'Active'}
                                <span class="label label-success">{$svc.status}</span>
                            {elseif $svc.status == 'Suspended'}
                                <span class="label label-warning">{$svc.status}</span>
                            {elseif $svc.status == 'Terminated'}
                                <span class="label label-danger">{$svc.status}</span>
                            {else}
                                <span class="label label-default">{$svc.status}</span>
                            {/if}
                            {if !$smarty.foreach.svcloop.last}<br>{/if}
                        {/foreach}
                    </td>
                    <td>
                        {foreach from=$client.services item=svc name=svcloop}
                            {if $svc.lastupdate}
                                {$svc.lastupdate|date_format:"%d/%m/%Y %H:%M:%S"}
                            {else}
                                -
                            {/if}
                            {if !$smarty.foreach.svcloop.last}<br>{/if}
                        {/foreach}
                    </td>
                    <td>
                        {foreach from=$client.services item=svc name=svcloop}
                            {if $svc.sync_status == 'Successful'}
                                <span class="label label-success">{$svc.sync_status}</span>
                            {elseif $svc.sync_status == 'Failed'}
                                <span class="label label-danger">{$svc.sync_status}</span>
                            {elseif $svc.sync_status == 'Mismatch'}
                                <span class="label label-warning">{$svc.sync_status}</span>
                            {else}
                                -
                            {/if}
                            {if !$smarty.foreach.svcloop.last}<br>{/if}
                        {/foreach}
                    </td>
                    <td><code>{$client.cloudpeid}</code></td>
                </tr>
            {/foreach}
        {/if}
    </tbody>
</table>

<h5 style="padding: 0 15px; margin-top: 40px;">Clients WITHOUT cloudpeid (will be skipped by cron)</h5>
<table class="glike hover">
    <thead>
        <tr>
            <th>ID</th>
            <th>Email</th>
            <th>Company</th>
        </tr>
    </thead>
    <tbody>
        {if $clientsWithoutCloudpeid|@count == 0}
            <tr>
                <td colspan="3" class="text-center">All clients have cloudpeid</td>
            </tr>
        {else}
            {foreach from=$clientsWithoutCloudpeid item=client}
                <tr>
                    <td><a href="?cmd=clients&action=show&id={$client.client_id}">{$client.client_id}</a></td>
                    <td>{$client.email}</td>
                    <td>{$client.company|default:'-'}</td>
                </tr>
            {/foreach}
        {/if}
    </tbody>
</table>