<div id="ServerContentControl" class="contentcontrol">
    <div id="wrap">

        <fieldset class="gitreport">
            <legend>Repositories per Active Branch</legend>
            <div id="branchTable" class="reportTable"></div>
            <div class="reportChart">
                <canvas id="branchChart"></canvas>
            </div>
        </fieldset>

        <fieldset class="gitreport">
            <legend>Repository Activity per Month</legend>
            <div id="activityTable" class="reportTable"></div>
            <div class="reportChart">
                <canvas id="activityChart"></canvas>
            </div>
        </fieldset>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    urlActiveBranch = '?name=gitactivebranchconnector&out=json';
    urlActivity = '?name=gitactivityconnector&out=json';

    function getRandomInt(max) {
        return Math.floor(Math.random() * max);
    }

    function fillTable(container, result) {
        if (!result.length) return;
        var table = $('<table>').appendTo(container);
        var trHead = $('<tr>').appendTo(table);
        for (var key in result[0]) $('<th>' + key + '</th>').appendTo(trHead);
        for (let i in result) {
            var trBody = $('<tr>').appendTo(table);
            for (var key in result[i]) $('<td>' + result[i][key] + '</td>').appendTo(trBody);
        }
    }

    $(function () {



$.getJSON(urlActiveBranch, function(result) {
        console.log(result); // Debug-Ausgabe
        fillTable($('#branchTable'), result);

        if (!result.length) return;
        const labels = [];
        const data = [];
        const bgs = [];

        for (let i in result) {
            labels.push(result[i]['branch']);
            data.push(result[i]['count']);
            bgs.push('rgb(' + getRandomInt(256) + ', ' + getRandomInt(256) + ', ' + getRandomInt(256) + ')');
        }

        const ctx = document.getElementById('branchChart');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Repositories per Active Branch',
                    data: data,
                    backgroundColor: bgs,
                    hoverOffset: 4
                }]
            }
        });
});



        $.getJSON(urlActivity, function (result) {
            console.log("Activity per Month:", result);
            fillTable($('#activityTable'), result);

            if (!result.length) return;

            const labels = result.map(entry => entry.month);
            const data = result.map(entry => entry.repos);

            new Chart(document.getElementById('activityChart'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Active Repositories',
                        data: data,
                        borderWidth: 1,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)'
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        }
                    }
                }
            });
        });
    });
</script>

<style>
    .reportTable { display: none; }
    .reportChart { height: 300px; }

    @media only screen and (min-width: 600px) {
        .reportTable { display: table; width: 20%; float: left; }
        .reportChart { width: 75%; height: 400px; float: right; }
    }

    .reportTable table { border-collapse: collapse; }
    .reportTable table th { background: #eee; }
    .reportTable table th, .reportTable table td { border: 1px solid #000; padding: 4px; }

    .gitreport { height: 300px; margin-bottom: 2em; }
    @media only screen and (min-width: 600px) {
        .gitreport { width: 45%; height: 450px; float: left; margin-right: 1%; }
    }
</style>

