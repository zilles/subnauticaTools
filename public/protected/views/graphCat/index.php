<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<?php if (yiiparam("debug")) { ?>
    <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
<?php } else {?>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.12"></script>
<?php } ?>
<script src="https://cdn.jsdelivr.net/npm/lodash@4.17.20/lodash.min.js"></script>
<script src="https://code.highcharts.com/stock/highstock.js"></script>

<div id="app">
    <a id="leaderboard_link" target="_blank" :href="cat.weblink" v-if="cat">{{cat.name}} Leaderboard</a>
    <select v-model="category">
        <option v-for="cat in categories" :key="cat.id" v-bind:value="cat.id">
            {{cat.name}}
        </option>
    </select>
    <span v-for="variable in variables">
        {{variable.name}}:
        <select v-model="variable.selected" v-on:change="createGraph()">
            <option v-for="(value,key) in variable.values.values" :key="key" v-bind:value="key">
                {{value.label}}
            </option>
        </select>
    </span>
    <div id="container" style="width:100%; height:50vw;"></div>
    <?php if (yiiparam("debug")) { ?>
    <pre v-for="run in runs" v-if="runMatches(run,variables) || run.players.data[0].names.international=='salvner'">
    {{ run }}
    </pre>
    <?php } ?>

</div>
<style>
    #leaderboard_link {
        float:right;
        font-size: 80%;
        color: gray;
    }
</style>
<script>
    var game = ['9dom836p','76rx0ze6'];

    var app = new Vue({
        el: '#app',
        data: {
            category: '',
            categories: [],
            variables: [],
            runs: [],
        },
        computed: {
            cat: function() {
                return _.find(this.categories, {id:this.category});
            }
        },
        methods: {
            runMatches: function(run,variables) {
                let vm = this;
                return _.every(variables, function(variable) {
                    return run.values[variable.id] === variable.selected;
                });
            },
            /*
            inGameTime: function() {
                if (this.cat.name == "Any%" && _.some(this.variables, function(v) {
                        return v.values.values[v.selected].label == "Creative";
                    }))
                        return true;
                return false;
            },
             */
            createGraph: function()
            {
                let vm = this;
                let wrTime = Number.MAX_SAFE_INTEGER;
                let seriesMap = {};
                _.each(vm.runs, function(run) {
                    if (vm.runMatches(run, vm.variables))
                    {
                        let value = run.times.primary_t*1000;
                        let player = run.players.data[0];
                        let player_name=player.names.international;
                        let wr = value < wrTime;
                        if (wr)
                            wrTime = value;

                        // Create series if it doesn't exist
                        if (!_.has(seriesMap,player.id))
                            seriesMap[player.id] = {
                                name: player_name,
                                data:[],
                                step: "left"
                            };

                        let len = seriesMap[player.id].data.length;
                        let video = "";
                        if (run.videos && run.videos.links)
                        {
                            _.each(run.videos.links, function(x) {
                                // Replace video link unless it already points at youtube or twitch
                                if (x.uri && !video.match(/youtube|twitch/))
                                    video = x.uri;
                            })
                        }
                        let point = {
                            x:new Date(run.status['verify-date']).getTime(),
                            y:value,
                            custom: {video: video, comment: run.comment, wr: wr ? "WR" : ""}
                        };
                        if (len == 0 || wr)
                            point.dataLabels= {
                                format: (len==0?player_name+" ":"")+(wr?"WR":""),
                            };
                        if (len==0 || value < seriesMap[player.id].data[len-1].y)
                            seriesMap[player.id].data.push(point);
                    }
                });
                let series = _.values(seriesMap);
                let name = vm.cat.name;
                let vars = _.map(vm.variables, function(v) {
                    return v.values.values[v.selected].label;
                });
                name+=" "+vars.join(" ");
                makeChart(name, series);
                //console.log(vars, series);
            }
        },
        watch: {
            category: function(val) {
                if (val)
                {
                    let vm = this;
                    let getRuns = function(offset) {
                        axios.get('https://www.speedrun.com/api/v1/runs?max=200&orderby=verify-date&embed=players&status=verified&category='+val+'&offset='+offset)
                            .then(function (response) {
                                // handle success
                                // if it still matches
                                if (val == vm.category)
                                {
                                    if (offset == 0)
                                        vm.runs = response.data.data;
                                    else
                                        vm.runs = vm.runs.concat(response.data.data);

                                    if (response.data.data.length == 200)
                                        getRuns(offset+200);
                                    else vm.createGraph();
                                }
                            })
                            .catch(function (error) {
                                // handle error
                                console.log(error);
                            });
                    }
                    axios.get('https://www.speedrun.com/api/v1/categories/'+val+'/variables')
                        .then(function (response) {
                            // handle success
                            vm.variables = response.data.data;
                            _.each(response.data.data, function(variable) {
                                Vue.set(variable,'selected',_.keys(variable.values.values)[0]);
                            });
                            getRuns(0);
                        })
                        .catch(function (error) {
                            // handle error
                            console.log(error);
                        });
                }
            }
        }
    })

    let catPromises = _.map(game, function(g) {
        return axios.get('https://www.speedrun.com/api/v1/games/'+g+'/categories');
    });

    Promise.all(catPromises)
        .then(function (response) {
            // handle success
            //console.log(response);
            let combined = response[0].data.data.concat(response[1].data.data);
            app.categories = combined;
            app.category = combined[0].id;
        })
        .catch(function (error) {
            // handle error
            console.log(error);
        });

    function makeChart(name, series) {

        series.unshift({ name: 'Show/Hide all',  marker: { enabled:false}});

        var myChart = Highcharts.chart('container', {
            chart: {
                type: 'line',
                events: {
                    load: function() {
                        this.showHideFlag = true;
                    }
                }
            },
            title: {
                text: name+' History'
            },
            xAxis: {
                type: 'datetime',
                dateTimeLabelFormats: { // don't display the dummy year
                    day: '%b %e %Y',
                    week: '%b %e %Y',
                    month: '%b %Y',
                    year: '%Y'
                },
                title: {
                    text: 'Date'
                }
            },
            yAxis: {
                type: 'datetime',
                title: {
                    text: 'Run Time'
                },
            },
            navigator: {
                enabled: true,
                series: {
                    type: 'line',
                    dataLabels: {
                        enabled: false
                    }
                }
            },
            rangeSelector: {
                enabled: true,
                verticalAlign: 'top',
            },
            tooltip: {
                style: {
                    pointerEvents: 'auto',
                    'width': '500%',
                    whiteSpace: 'normal'
                },

 //               stickOnContact: true,
                headerFormat: '<b>{series.name} - {point.x:%b %e, %Y} - ({point.y:%k:%M:%S}) {point.custom.wr}</b><br>',
                pointFormat: '{point.custom.comment}<br/><a href="{point.custom.video}">{point.custom.video}</a>'
            },
            plotOptions: {
                series: {
                    showInNavigator: true,
                    dataLabels: {
                        enabled: true,
                        format:"",
                        position: "center",
                        allowOverlap: true,
                    },
                    states: {
                        inactive: {
                            opacity: .3,
                        }
                    },
                    findNearestPointBy: "xy",
                    marker: {
                        enabled: true
                    },
                    events: {
                        legendItemClick() {
                            let chart = this.chart,
                                series = chart.series;
                            if (this.index === 0) {
                                if (chart.showHideFlag) {
                                    series.forEach(series => {
                                        series.setVisible(false, false);
                                    })
                                } else {
                                    series.forEach(series => {
                                        series.setVisible(true, false);
                                    })
                                }
                                chart.redraw();
                                chart.showHideFlag = !chart.showHideFlag;
                                return false;
                            }
                            return true;
                        },
                    }
                },
            },
            series: series,
        });
    }
    function sizeGraph()
    {
        let height = (window.innerHeight - 150);
        if (height < 500)
            height = 500;
        document.getElementById("container").style.height = height + "px";
    }
    sizeGraph();
    window.onresize = function() {
        sizeGraph();
    };
</script>