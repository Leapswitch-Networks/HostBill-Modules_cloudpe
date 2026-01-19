<div class="alert alert-info">
    <strong>Summary:</strong>
    Total CloudPe Accounts: {$totalAccounts} |
    With cloudpeid: {$accountsWithCloudpeid|@count} |
    Without cloudpeid: {$accountsWithoutCloudpeid|@count}
</div>

<h5>Accounts WITH cloudpeid (will be synced by cron)</h5>
<table class="glike hover">
    <thead>
        <tr>
            <th>Account ID</th>
            <th>Client ID</th>
            <th>Status</th>
            <th>Domain</th>
            <th>CloudPe ID</th>
        </tr>
    </thead>
    <tbody>
        {if $accountsWithCloudpeid|@count == 0}
            <tr><td colspan="5" class="text-center">No accounts found with cloudpeid</td></tr>
        {else}
            {foreach from=$accountsWithCloudpeid item=acc}
                <tr>
                    <td><a href="?cmd=accounts&action=edit&id={$acc.id}">{$acc.id}</a></td>
                    <td><a href="?cmd=clients&action=show&id={$acc.client_id}">{$acc.client_id}</a></td>
                    <td>
                        {if $acc.status == 'Active'}
                            <span class="label label-success">{$acc.status}</span>
                        {elseif $acc.status == 'Suspended'}
                            <span class="label label-warning">{$acc.status}</span>
                        {else}
                            <span class="label label-default">{$acc.status}</span>
                        {/if}
                    </td>
                    <td>{$acc.domain|default:'-'}</td>
                    <td><code>{$acc.cloudpeid}</code></td>
                </tr>
            {/foreach}
        {/if}
    </tbody>
</table>

<h5 style="margin-top: 20px;">Accounts WITHOUT cloudpeid (will be skipped by cron)</h5>
<table class="glike hover">
    <thead>
        <tr>
            <th>Account ID</th>
            <th>Client ID</th>
            <th>Status</th>
            <th>Domain</th>
        </tr>
    </thead>
    <tbody>
        {if $accountsWithoutCloudpeid|@count == 0}
            <tr><td colspan="4" class="text-center">All accounts have cloudpeid</td></tr>
        {else}
            {foreach from=$accountsWithoutCloudpeid item=acc}
                <tr>
                    <td><a href="?cmd=accounts&action=edit&id={$acc.id}">{$acc.id}</a></td>
                    <td><a href="?cmd=clients&action=show&id={$acc.client_id}">{$acc.client_id}</a></td>
                    <td>
                        {if $acc.status == 'Active'}
                            <span class="label label-success">{$acc.status}</span>
                        {elseif $acc.status == 'Suspended'}
                            <span class="label label-warning">{$acc.status}</span>
                        {else}
                            <span class="label label-default">{$acc.status}</span>
                        {/if}
                    </td>
                    <td>{$acc.domain|default:'-'}</td>
                </tr>
            {/foreach}
        {/if}
    </tbody>
</table>
