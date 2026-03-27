{if !$hasServerConfig}
<div class="alert alert-danger" style="margin: 15px;">
    <strong>Warning:</strong> No CloudPE App configured. Go to <a href="?cmd=apps">Settings &gt; Apps</a> to configure.
</div>
{/if}

<div style="padding: 15px;">

    <div style="display: flex; gap: 15px; margin-bottom: 20px;">
        <div id="card-no_account" class="cloudpe-card" onclick="cardFilter('no_account')" style="flex: 1; cursor: pointer; padding: 20px; border: 2px solid #ddd; border-radius: 8px; text-align: center; background: #fff; transition: all 0.2s;">
            <div style="font-size: 28px; font-weight: bold; color: #2c3e50;">{$countWithoutAccount}</div>
            <div style="font-size: 13px; color: #7f8c8d; margin-top: 4px;">w/o Service Accounts</div>
        </div>
        <div id="card-no_uid" class="cloudpe-card" onclick="cardFilter('no_uid')" style="flex: 1; cursor: pointer; padding: 20px; border: 2px solid #ddd; border-radius: 8px; text-align: center; background: #fff; transition: all 0.2s;">
            <div style="font-size: 28px; font-weight: bold; color: #2c3e50;">{$countWithoutCloudpeid}</div>
            <div style="font-size: 13px; color: #7f8c8d; margin-top: 4px;">w/o cloudpeid</div>
        </div>
        <div id="card-other_brand" class="cloudpe-card" onclick="cardFilter('other_brand')" style="flex: 1; cursor: pointer; padding: 20px; border: 2px solid #ddd; border-radius: 8px; text-align: center; background: #fff; transition: all 0.2s;">
            <div style="font-size: 28px; font-weight: bold; color: #2c3e50;">{$countOtherBrand}</div>
            <div style="font-size: 13px; color: #7f8c8d; margin-top: 4px;">from other brand</div>
        </div>
    </div>

    <div style="margin-bottom: 10px;">
        <a href="#" id="toggleFilter" onclick="toggleFilterPanel(); return false;" class="btn btn-default btn-sm"><i class="fa fa-filter"></i> Filter data</a>
        <span id="filterNotice" style="display: none; margin-left: 10px; color: #e74c3c;">
            <i class="fa fa-exclamation-triangle"></i> Filter is active, some data may be omitted -
            <a href="#" onclick="resetFilters(); return false;">reset filter</a>
        </span>
    </div>

    <div id="filterPanel" style="display: none; padding: 15px; margin-bottom: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
        <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
            <div style="flex: 2; min-width: 200px;">
                <label style="display: block; font-size: 12px; margin-bottom: 4px;">Search (Email, Company, ID, UID, Account #):</label>
                <input type="text" id="filterSearch" class="form-control input-sm" placeholder="Email, company, ID, UID, Account #..." onkeyup="currentPage=1; applyFilters()">
            </div>
            <div style="flex: 1; min-width: 120px;">
                <label style="display: block; font-size: 12px; margin-bottom: 4px;">Account Status:</label>
                <select id="filterStatus" class="form-control input-sm" onchange="currentPage=1; applyFilters()">
                    <option value="">All</option>
                    <option value="Active">Active</option>
                    <option value="Suspended">Suspended</option>
                    <option value="Terminated">Terminated</option>
                    <option value="Pending">Pending</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>
            <div style="flex: 1; min-width: 120px;">
                <label style="display: block; font-size: 12px; margin-bottom: 4px;">Sync Status:</label>
                <select id="filterSync" class="form-control input-sm" onchange="currentPage=1; applyFilters()">
                    <option value="">All</option>
                    <option value="Successful">Successful</option>
                    <option value="Failed">Failed</option>
                    <option value="none">Not synced</option>
                </select>
            </div>
        </div>
    </div>

    <table class="glike hover" id="cloudpe-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Company</th>
                <th>Brand</th>
                <th>UID</th>
                <th>Account #</th>
                <th>Status</th>
                <th>Sync Updated at</th>
                <th>Sync status</th>
            </tr>
        </thead>
        <tbody>
            {if $rows|@count == 0}
                <tr>
                    <td colspan="9" class="text-center">No clients found</td>
                </tr>
            {else}
                {foreach from=$rows item=row}
                    <tr data-tags="{$row.tags}" data-client="{$row.client_id}" data-brand="{$row.brand_id}" data-status="{$row.status}" data-sync="{$row.sync_status}" data-uid="{$row.cloudpeid}" data-first="{$row.is_first}" data-email="{$row.email}" data-company="{$row.company}" data-accountid="{$row.account_id}" data-svccount="{$row.service_count}">
                        {if $row.is_first}
                            <td rowspan="{$row.service_count}"><a href="?cmd=clients&action=show&id={$row.client_id}">{$row.client_id}</a></td>
                            <td rowspan="{$row.service_count}">{$row.email}</td>
                            <td rowspan="{$row.service_count}">{$row.company|default:'-'}</td>
                            <td rowspan="{$row.service_count}">
                                {if $row.brand_id == $cloudpeBrandId}
                                    <span class="label label-info">{$row.brand}</span>
                                {else}
                                    <span class="label label-default">{$row.brand}</span>
                                {/if}
                            </td>
                            <td rowspan="{$row.service_count}">
                                {if $row.cloudpeid}
                                    <code>{$row.cloudpeid}</code>
                                {else}
                                    -
                                {/if}
                            </td>
                        {/if}
                        <td>
                            {if $row.account_id}
                                <a href="?cmd=accounts&action=edit&id={$row.account_id}">{$row.account_id}</a>
                            {else}
                                -
                            {/if}
                        </td>
                        <td>
                            {if $row.status == 'Active'}
                                <span class="label label-success">{$row.status}</span>
                            {elseif $row.status == 'Suspended'}
                                <span class="label label-warning">{$row.status}</span>
                            {elseif $row.status == 'Terminated'}
                                <span class="label label-danger">{$row.status}</span>
                            {elseif $row.status}
                                <span class="label label-default">{$row.status}</span>
                            {else}
                                -
                            {/if}
                        </td>
                        <td>
                            {if $row.sync_date}
                                {$row.sync_date|date_format:"%d/%m/%Y %H:%M:%S"}
                            {else}
                                -
                            {/if}
                        </td>
                        <td>
                            {if $row.sync_status == 'Successful'}
                                <span class="label label-success">{$row.sync_status}</span>
                            {elseif $row.sync_status == 'Failed'}
                                <span class="label label-danger">{$row.sync_status}</span>
                            {else}
                                -
                            {/if}
                        </td>
                    </tr>
                {/foreach}
            {/if}
        </tbody>
    </table>

    <div class="blu" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0;">
        <div id="paginationInfo" style="font-size: 13px;">Showing <span id="pgCount">0</span> of <span id="pgTotal">0</span></div>
        <div class="right">
            <div class="pagination hb-pagination" id="paginationControls"></div>
        </div>
    </div>

</div>

{literal}
<script>
(function() {
    // Redirect ?cmd=module&module=33 to ?cmd=cloudpe
    var params = new URLSearchParams(window.location.search);
    if (params.get('cmd') === 'module' && params.get('module')) {
        window.location.replace('?cmd=cloudpe');
        return;
    }
})();

var activeCard = '';
var currentPage = 1;
var perPage = 25;

function cardFilter(card) {
    currentPage = 1;
    activeCard = (activeCard === card) ? '' : card;

    document.querySelectorAll('.cloudpe-card').forEach(function(c) {
        c.style.borderColor = '#ddd';
        c.style.background = '#fff';
    });
    if (activeCard) {
        var el = document.getElementById('card-' + activeCard);
        if (el) {
            el.style.borderColor = '#3498db';
            el.style.background = '#eaf4fd';
        }
    }

    applyFilters();
}

function toggleFilterPanel() {
    var panel = document.getElementById('filterPanel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

function resetFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterSync').value = '';
    activeCard = '';
    currentPage = 1;

    document.querySelectorAll('.cloudpe-card').forEach(function(c) {
        c.style.borderColor = '#ddd';
        c.style.background = '#fff';
    });

    applyFilters();
}

function applyFilters() {
    var search = document.getElementById('filterSearch').value.toLowerCase();
    var status = document.getElementById('filterStatus').value;
    var sync = document.getElementById('filterSync').value;

    var hasFilter = (search || status || sync || activeCard);
    document.getElementById('filterNotice').style.display = hasFilter ? 'inline' : 'none';

    var filteredClients = [];
    var rows = document.querySelectorAll('#cloudpe-table tbody tr[data-client]');

    // Group rows by client
    var clients = {};
    var clientOrder = [];
    rows.forEach(function(row) {
        var cid = row.getAttribute('data-client');
        if (!clients[cid]) {
            clients[cid] = [];
            clientOrder.push(cid);
        }
        clients[cid].push(row);
    });

    for (var i = 0; i < clientOrder.length; i++) {
        var cid = clientOrder[i];
        var clientRows = clients[cid];
        var firstRow = clientRows[0];

        var tags = firstRow.getAttribute('data-tags') || '';
        var rEmail = (firstRow.getAttribute('data-email') || '').toLowerCase();
        var rCompany = (firstRow.getAttribute('data-company') || '').toLowerCase();
        var rUid = (firstRow.getAttribute('data-uid') || '').toLowerCase();

        // Card filter (client-level)
        var passCard = true;
        if (activeCard) {
            passCard = tags.indexOf(activeCard) !== -1;
        }

        // Search filter (client-level: email, company, id, uid, account id)
        var passSearch = true;
        if (search) {
            var accountMatch = false;
            for (var j = 0; j < clientRows.length; j++) {
                var aid = (clientRows[j].getAttribute('data-accountid') || '').toLowerCase();
                if (aid.indexOf(search) !== -1) { accountMatch = true; break; }
            }
            passSearch = rEmail.indexOf(search) !== -1 ||
                         rCompany.indexOf(search) !== -1 ||
                         cid.indexOf(search) !== -1 ||
                         rUid.indexOf(search) !== -1 ||
                         accountMatch;
        }

        // If client-level fails, hide all rows
        if (!passCard || !passSearch) {
            clientRows.forEach(function(r) { r.style.display = 'none'; });
            // Fix rowspan: ensure merged cells don't break
            fixRowspan(clientRows, 0);
            continue;
        }

        // Row-level filters (status, sync)
        var visibleCount = 0;
        clientRows.forEach(function(row) {
            var rStatus = row.getAttribute('data-status') || '';
            var rSync = row.getAttribute('data-sync') || '';

            var passStatus = !status || rStatus === status;
            var passSync = true;
            if (sync === 'none') {
                passSync = rSync === '';
            } else if (sync) {
                passSync = rSync === sync;
            }

            if (passStatus && passSync) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // If no rows visible after row-level filter, hide all
        if (visibleCount === 0) {
            clientRows.forEach(function(r) { r.style.display = 'none'; });
            fixRowspan(clientRows, 0);
            continue;
        }

        fixRowspan(clientRows, visibleCount);
        filteredClients.push({ cid: cid, rows: clientRows, visibleCount: visibleCount });
    }

    // Pagination
    var totalClients = filteredClients.length;
    var totalPages = Math.max(1, Math.ceil(totalClients / perPage));
    if (currentPage > totalPages) currentPage = totalPages;

    var startIdx = (currentPage - 1) * perPage;
    var endIdx = Math.min(startIdx + perPage, totalClients);

    // Hide all filtered clients first, then show only current page
    for (var p = 0; p < filteredClients.length; p++) {
        var fc = filteredClients[p];
        if (p >= startIdx && p < endIdx) {
            fc.rows.forEach(function(r) {
                if (r.style.display !== 'none' || r.getAttribute('data-filtered') !== 'false') {
                    // Re-show rows that passed row-level filters
                }
            });
        } else {
            fc.rows.forEach(function(r) { r.style.display = 'none'; });
            fixRowspan(fc.rows, 0);
        }
    }

    // Re-apply row-level visibility for current page clients
    for (var p = startIdx; p < endIdx; p++) {
        var fc = filteredClients[p];
        var visCount = 0;
        fc.rows.forEach(function(row) {
            var rStatus = row.getAttribute('data-status') || '';
            var rSync = row.getAttribute('data-sync') || '';

            var passStatus = !status || rStatus === status;
            var passSync = true;
            if (sync === 'none') {
                passSync = rSync === '';
            } else if (sync) {
                passSync = rSync === sync;
            }

            if (passStatus && passSync) {
                row.style.display = '';
                visCount++;
            } else {
                row.style.display = 'none';
            }
        });
        fixRowspan(fc.rows, visCount);
    }

    updatePagination(startIdx + 1, endIdx, totalClients, totalPages);
}

function fixRowspan(clientRows, visibleCount) {
    for (var i = 0; i < clientRows.length; i++) {
        if (clientRows[i].getAttribute('data-first') === '1') {
            var cells = clientRows[i].querySelectorAll('td[rowspan]');
            cells.forEach(function(td) {
                td.setAttribute('rowspan', visibleCount > 0 ? visibleCount : 1);
            });
            break;
        }
    }
}

function updatePagination(from, to, total, totalPages) {
    document.getElementById('pgCount').textContent = total === 0 ? '0' : to;
    document.getElementById('pgTotal').textContent = total;

    var controls = document.getElementById('paginationControls');
    if (totalPages <= 1) {
        controls.innerHTML = '';
        return;
    }

    var html = '';
    if (currentPage > 1) {
        html += '<a href="#" onclick="goToPage(' + (currentPage - 1) + '); return false;" class="prev">&lt;</a>';
    }

    var startP = Math.max(1, currentPage - 2);
    var endP = Math.min(totalPages, currentPage + 2);
    for (var pg = startP; pg <= endP; pg++) {
        if (pg === currentPage) {
            html += '<span class="current">' + pg + '</span>';
        } else {
            html += '<a href="#" onclick="goToPage(' + pg + '); return false;">' + pg + '</a>';
        }
    }

    if (currentPage < totalPages) {
        html += '<a href="#" onclick="goToPage(' + (currentPage + 1) + '); return false;" class="next">&gt;</a>';
    }
    controls.innerHTML = html;
}

function goToPage(page) {
    currentPage = page;
    applyFilters();
}

// Initial pagination on page load
applyFilters();
</script>
{/literal}
