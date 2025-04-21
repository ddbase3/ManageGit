<div id="wrap">
<div id="GitContentControl" class="contentcontrol">

<div id="git-repo-table-container">
    <h2>Git Repositories</h2>
    <div id="pagination-controls" style="margin-bottom: 10px;">
        <button id="prev-page" type="button">Zurück</button>
        <span id="page-info">Seite 1</span>
        <button id="next-page" type="button">Weiter</button>
    </div>
    <table id="git-repo-table" class="git-table">
        <thead>
            <tr>
                <th></th>
                <th class="no-select" data-sort="base3config">Account</th>
                <th class="no-select" data-sort="name">Name</th>
                <th class="no-select" data-sort="html_url">Link</th>
                <th class="no-select" data-sort="default_branch">Default Branch</th>
            </tr>
            <tr class="filter-row">
                <th></th>
                <th><div class="filter-cell"><input class="filter-input" data-column="base3config"><span class="filter-clear">✕</span></div></th>
                <th><div class="filter-cell"><input class="filter-input" data-column="name"><span class="filter-clear">✕</span></div></th>
                <th><div class="filter-cell"><input class="filter-input" data-column="html_url"><span class="filter-clear">✕</span></div></th>
                <th><div class="filter-cell"><input class="filter-input" data-column="default_branch"><span class="filter-clear">✕</span></div></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

</div>
</div>

<style>
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
        text-align: center;
    }

    #pagination-controls button {
        margin: 0 10px;
    }
</style>

<script>
$(document).ready(function () {
    let filterValues = {};
    let currentPage = 1;
    let currentSort = { column: 'name', direction: 'asc' };
    const perPage = 10;

    function debounce(fn, delay) {
        let timer = null;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
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

        $.getJSON("?" + $.param(params), function (response) {
            const $tbody = $('#git-repo-table tbody').empty();

            if (!response || !Array.isArray(response.repos)) {
                $tbody.append(`<tr><td colspan="5">Fehler beim Laden der Daten.</td></tr>`);
                $('#page-info').text("Fehler beim Laden");
                return;
            }

            response.repos.forEach((repo, index) => {
                const repoId = 'repo-' + index;
                const branchRowId = 'branch-row-' + index;

                const $repoRow = $(`
                    <tr class="repo-row" data-id="${repoId}">
                        <td><span class="expand-button no-select" data-target="${branchRowId}">▶</span></td>
                        <td>${repo.base3config}</td>
                        <td>${repo.name}</td>
                        <td><a href="${repo.html_url}" target="_blank">${repo.html_url}</a></td>
                        <td>${repo.default_branch}</td>
                    </tr>
                `);

                const $branchRow = $(`
                    <tr id="${branchRowId}" class="branch-row" data-parent="${repoId}">
                        <td colspan="5">
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
                                            <td>${branch.protected ? '✅' : '❌'}</td>
                                            <td>${branch.is_default ? '✅' : ''}</td>
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
        }).fail(function () {
            $('#git-repo-table tbody').html(`<tr><td colspan="5">Fehler beim Abrufen der Daten.</td></tr>`);
            $('#page-info').text("Fehler beim Abrufen");
        });
    }

    // Sortierung per Header
    $('#git-repo-table thead th[data-sort]').on('click', function (e) {
        e.preventDefault();
        const sortKey = $(this).data('sort');
        const isSameColumn = currentSort.column === sortKey;
        const newDirection = isSameColumn && currentSort.direction === 'asc' ? 'desc' : 'asc';
        currentSort = { column: sortKey, direction: newDirection };

        $('#git-repo-table thead th').removeClass('sort-asc sort-desc');
        $(this).addClass(newDirection === 'asc' ? 'sort-asc' : 'sort-desc');

        currentPage = 1;
        loadRepos();
    });

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

    const debounceLoadRepos = debounce(loadRepos, 300);
    $('.filter-input').on('input', debounce(function () {
        const $input = $(this);
        const value = $input.val().trim();
        const column = $input.data('column');

        if (value) {
            filterValues[column] = value;
            $input.siblings('.filter-clear').show();
        } else {
            delete filterValues[column];
            $input.siblings('.filter-clear').hide();
        }

        currentPage = 1;
        debounceLoadRepos();
    }, 300));

    $('.filter-clear').on('click', function () {
        const $clear = $(this);
        const $input = $clear.siblings('.filter-input');
        const column = $input.data('column');

        $input.val('').focus();
        $clear.hide();
        delete filterValues[column];

        currentPage = 1;
        loadRepos();
    });

    loadRepos();
});
</script>

