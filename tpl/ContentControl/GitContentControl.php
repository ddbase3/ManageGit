<div id="wrap">
<div id="GitContentControl" class="contentcontrol">

<div id="git-repo-table-container">
    <div>
        <div id="pagination-controls">
            <button id="prev-page" type="button">Zurück</button>
            <span id="page-info"></span>
            <button id="next-page" type="button">Weiter</button>
        </div>
        <div id="column-selector" class="dropdown">
            <button id="toggle-column-dropdown" type="button">Spalten wählen ⬇</button>
            <div id="column-options" class="dropdown-content"></div>
        </div>
    </div>
    <table id="git-repo-table" class="git-table">
        <thead></thead>
        <tbody></tbody>
    </table>
    <div>
        <div id="reset-controls">
            <button id="reset-view" type="button">Reset</button>
        </div>
        <div id="pagination-info"></div>
    </div>
</div>

</div>
</div>

<style>
    #git-repo-table-container {
        margin-top: 10px;
    }

    .git-table {
        width: 100%;
        border-collapse: collapse;
    }

    .git-table th, .git-table td {
        padding: 10px;
        border: 1px solid #ccc;
    }

    .git-table th {
        background: #e7e7e7;
    }

    #git-repo-table th:first-child, #git-repo-table td:first-child {
        width: 40px;
    }

    #git-repo-table .branch-table th:first-child, #git-repo-table .branch-table td:first-child {
        width: auto;
    }

    .branch-row {
        display: none;
        background-color: #f9f9f9;
    }

    .branch-table {
        width: 100%;
        border-collapse: collapse;
    }

    .branch-table th, .branch-table td {
        padding: 5px;
        border: 1px solid #ddd;
    }

    .expand-button {
        padding: 5px 15px;
        cursor: pointer;
        color: blue;
        text-decoration: none;
    }

    .git-table th.sort-asc::after {
        content: " ▲";
    }

    .git-table th.sort-desc::after {
        content: " ▼";
    }

    .git-table th[data-sort] {
        cursor: pointer;
    }

    .no-select {
        user-select: none;         /* Standard */
        -webkit-user-select: none; /* Safari */
        -moz-user-select: none;    /* Firefox */
        -ms-user-select: none;     /* IE10+ */
    }

    .git-table .filter-row input {
        width: 100%;
        box-sizing: border-box;
        padding: 5px;
    }

    .filter-cell {
        position: relative;
        display: flex;
        align-items: center;
    }

    .filter-input {
        width: 100%;
        padding-right: 20px;
    }

    .filter-clear {
        position: absolute;
        right: 5px;
        cursor: pointer;
        color: #888;
        font-weight: bold;
        display: none;
    }

    .filter-clear:hover {
        color: #000;
    }

    #pagination-controls {
        float: right;
        display: inline-block;
    }

    #pagination-controls #page-info {
        margin: 0 10px;
    }

    #reset-controls {
        margin: 10px 0;
        display:inline-block;
    }

    #pagination-info {
        float: right;
        display: inline-block;
        margin: 10px 0;
    }

    #column-selector {
        min-width: 220px;
        position: relative;
        display: inline-block;
        margin-bottom: 10px;
    }

    #column-selector option {
        white-space: nowrap;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        background-color: #fff;
        border: 1px solid #ccc;
        padding: 10px;
        z-index: 100;
    }

    .dropdown-content label {
        display: block;
    }

    #column-selector.open .dropdown-content {
        display: block;
    }
</style>

<script>
var availableColumns = [
    { key: "base3config", label: "Account" },
    { key: "name", label: "Name" },
    { key: "html_url", label: "Link" },
    { key: "default_branch", label: "Default Branch" },
    { key: "description", label: "Beschreibung" },
    { key: "private", label: "Öffentlich" }
];
var visibleColumns = JSON.parse(localStorage.getItem('visibleColumns') ?? '["base3config", "name", "html_url", "default_branch"]');
var previousVisibleColumns = [...visibleColumns];

var repoFilters = JSON.parse(localStorage.getItem('repoFilters') || '{}');
var repoSort = JSON.parse(localStorage.getItem('repoSort') || '{}');

$(document).ready(function () {
    let currentPage = 1;
    const perPage = 10;
    const filters = repoFilters || {};
    const sort = repoSort || {};
    let filterValues = { ...filters };
    let currentSort = { column: sort.column || 'name', direction: sort.direction || 'asc' };

    function debounce(fn, delay) {
        let timer = null;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    function arraysEqual(a, b) {
        return a.length === b.length && a.every((val, i) => val === b[i]);
    }




function buildHeaderAndFilters() {
    const $thead = $('#git-repo-table thead');
    $thead.find('tr.repo-header, tr.filter-row').remove();

    // Header-Zeile
    const $headerRow = $('<tr class="repo-header"></tr>').append('<th></th>');
    visibleColumns.forEach(key => {
        const col = availableColumns.find(c => c.key === key);
        $headerField = $(`<th data-sort="${key}" class="no-select">${col.label}</th>`);
        if (currentSort.column === key) $headerField.addClass(currentSort.direction  === 'asc' ? 'sort-asc' : 'sort-desc');
        $headerRow.append($headerField);
    });

    // Filter-Zeile
    const $filterRow = $('<tr class="filter-row"></tr>').append('<th></th>');
    visibleColumns.forEach(key => {
        const val = filterValues[key] || '';
        let $filterElement;

        if (key === 'private') {
            $filterElement = $(`
                <select class="filter-input" data-filter="${key}">
                    <option value="">Alle</option>
                    <option value="false">✅ Öffentlich</option>
                    <option value="true">❌ Privat</option>
                </select>
            `);
            $filterElement.val(val); // aktuellen Wert setzen
        } else {
            $filterElement = $(`<input type="text" class="filter-input" data-filter="${key}" placeholder="Filter..." value="${val}">`);
        }

        const $div = $(`<div class="filter-cell"></div>`).append($filterElement);

        // Reset-Button nur bei Textfeld
        var $reset = $();
        if (key !== 'private') {
            $reset = $('<span class="filter-clear" style="display:none;">✕</span>');
            if (val) $reset.show();
            $div.append($reset);
        }

        // Filter wiederherstellen
        if (filters[key]) {
            $filterElement.val(filters[key]);
            $reset.show();
        }

        const $container = $('<th></th>').append($div);
        $filterRow.append($container);
    });

    // Einfügen
    $thead.append($headerRow);
    $thead.append($filterRow);


    bindSort();
    bindFilterInputs();
    bindFilterClearButtons();
}




    function loadRepos() {
        const params = {
            name: 'gitconnector',
            out: 'json',
            page: currentPage,
            sort: currentSort.column,
            direction: currentSort.direction
        };

        Object.entries(filterValues).forEach(([key, value]) => {
            if (value) params[`filter[${key}]`] = value;
        });

        if (!arraysEqual(visibleColumns, previousVisibleColumns)) {
            buildHeaderAndFilters();
            previousVisibleColumns = [...visibleColumns];
        }

        $.getJSON("?" + $.param(params), function (response) {
            const $tbody = $('#git-repo-table tbody').empty();

            if (!response || !Array.isArray(response.repos)) {
                $tbody.append(`<tr><td colspan="${visibleColumns.length + 1}">Fehler beim Laden der Daten.</td></tr>`);
                $('#page-info').text("Fehler beim Laden");
                return;
            }

            response.repos.forEach((repo, index) => {
                const repoId = 'repo-' + index;
                const branchRowId = 'branch-row-' + index;

                const $repoRow = $('<tr class="repo-row" data-id="' + repoId + '">');
                $repoRow.append(`<td><span class="expand-button no-select" data-target="${branchRowId}">▶</span></td>`);
                visibleColumns.forEach(key => {
                    let val = repo[key];
                    if (key === 'html_url') {
                        val = `<a href="${val}" target="_blank">${val}</a>`;
                    } else if (key === 'private') {
                        const isPublic = val === 'false';
                        val = isPublic ? '✅' : '❌';
                    }
                    $repoRow.append(`<td>${val ?? ''}</td>`);
                });

const $branchRow = $(`
    <tr id="${branchRowId}" class="branch-row" data-parent="${repoId}">
        <td colspan="${visibleColumns.length + 1}">
            <table class="branch-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Protected</th>
                        <th>Default</th>
                        <th>Letzter Commit</th>
                    </tr>
                </thead>
                <tbody>
                    ${(repo.branches || []).map(branch => `
                        <tr>
                            <td>${branch.name}</td>
                            <td>${branch.protected === 'true' ? '✅' : '❌'}</td>
                            <td>${branch.is_default === 'true' ? '✅' : ''}</td>
                            <td>
                                <div><strong>${branch.last_commit?.message || ''}</strong></div>
                                <div>${branch.last_commit?.author?.name || ''} &lt;${branch.last_commit?.author?.email || ''}&gt;</div>
                                <div>${branch.last_commit?.date || ''}</div>
                                <div><a href="${branch.last_commit?.url || '#'}" target="_blank">View Commit</a></div>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </td>
    </tr>
`);

                $tbody.append($repoRow).append($branchRow);
            });

            $('.expand-button').on('click', function () {
                const targetId = $(this).data('target');
                $('.branch-row').not(`#${targetId}`).hide();
                $(`#${targetId}`).toggle();
            });

            // Paginierungs-UI
            $('#page-info').text(`Seite ${response.page} von ${response.totalPages}`);
            $('#prev-page').prop('disabled', response.page <= 1);
            $('#next-page').prop('disabled', response.page >= response.totalPages);

            // Paginierings-Info
            var from = (response.page - 1) * response.perPage + 1;
            var to = Math.min(from + response.perPage - 1, response.total);
            $('#pagination-info').text(`Datensätze ${from} bis ${to} von ${response.total}`);;
        }).fail(function () {
            $('#git-repo-table tbody').html(`<tr><td colspan="5">Fehler beim Abrufen der Daten.</td></tr>`);
            $('#page-info').text("Fehler beim Abrufen");
        });
    }

    // Paging-Buttons mit preventDefault + type="button"
    $('#prev-page').on('click', function (e) {
        e.preventDefault();
        if (currentPage > 1) {
            currentPage--;
            loadRepos();
        }
    });

    $('#next-page').on('click', function (e) {
        e.preventDefault();
        currentPage++;
        loadRepos();
    });

function bindSort() {
    const $thead = $('#git-repo-table thead');
    $thead.find('th[data-sort]').off('click').on('click', function (e) {
        e.preventDefault();
        const sortKey = $(this).data('sort');
        const isSameColumn = currentSort.column === sortKey;
        const newDirection = isSameColumn && currentSort.direction === 'asc' ? 'desc' : 'asc';
        currentSort = { column: sortKey, direction: newDirection };

        $thead.find('th').removeClass('sort-asc sort-desc');
        $(this).addClass(newDirection === 'asc' ? 'sort-asc' : 'sort-desc');

        localStorage.setItem('repoSort', JSON.stringify(currentSort));

        currentPage = 1;
        loadRepos();
    });
}

    var debounceLoadRepos = debounce(loadRepos, 300);
function bindFilterInputs() {
    $('.filter-input').off('input change').on('input change', debounce(function () {
        const $input = $(this);
        const value = $input.val().trim();
        const filter = $input.data('filter');

        if (value) {
            filterValues[filter] = value;
            filters[filter] = value;
            $input.siblings('.filter-clear').show();
        } else {
            delete filterValues[filter];
            delete filters[filter];
            $input.siblings('.filter-clear').hide();
        }

        localStorage.setItem('repoFilters', JSON.stringify(filters));

        currentPage = 1;
        debounceLoadRepos();
    }, 300));
}

function bindFilterClearButtons() {
    $('.filter-clear').off('click').on('click', function () {
        const $clear = $(this);
        const $input = $clear.siblings('.filter-input');
        const key = $input.data('filter');

        $input.val('').focus();
        $clear.hide();
        filterValues[key] = '';

        currentPage = 1;
        loadRepos();
    });
}

function renderColumnSelector() {
    const $container = $('#column-options').empty();
    availableColumns.forEach(col => {
        const isChecked = visibleColumns.includes(col.key);
        const $checkbox = $(`
            <label>
                <input type="checkbox" value="${col.key}" ${isChecked ? 'checked' : ''}> ${col.label}
            </label>
        `);
        $container.append($checkbox);
    });

    $container.find('input[type="checkbox"]').on('change', function () {
        const key = $(this).val();
        if (this.checked) {
            if (!visibleColumns.includes(key)) visibleColumns.push(key);
        } else {
            visibleColumns = visibleColumns.filter(c => c !== key);
        }

        localStorage.setItem('visibleColumns', JSON.stringify(visibleColumns));

        currentPage = 1;
        loadRepos();
    });
}

$('#toggle-column-dropdown').on('click', function (e) {
    e.stopPropagation();
    $('#column-selector').toggleClass('open');
});
$(document).on('click', function (e) {
    if (!$(e.target).closest('#toggle-column-dropdown').length && 
        !$(e.target).closest('#column-selector').length) {
        $('#column-selector').removeClass('open');
    }
});


$('#reset-view').on('click', function () {
    localStorage.removeItem('visibleColumns');
    localStorage.removeItem('repoFilters');
    localStorage.removeItem('repoSort');
    location.reload();
});


    buildHeaderAndFilters();
    bindSort();
    bindFilterInputs();
    bindFilterClearButtons();
    renderColumnSelector();
    loadRepos();
});
</script>

