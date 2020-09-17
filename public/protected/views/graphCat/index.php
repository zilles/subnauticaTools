<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lodash@4.17.20/lodash.min.js"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>

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
    <!--ul>
        <li v-for="run in runs" v-if="runMatches(run,variables)">
             {{run.players.data[0].names.international}} on {{run.date}} got {{run.times.realtime}}
        </li>
    </ul-->
    <!--pre v-for="run in runs" v-if="runMatches(run,variables) && run.players.data[0].names.international=='salvner'">
    {{ categories[0] }}
    </pre-->

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
            createGraph: function()
            {
                let vm = this;
                let wrTime = Number.MAX_SAFE_INTEGER;
                let seriesMap = {};
                _.each(vm.runs, function(run) {
                    if (vm.runMatches(run, vm.variables))
                    {
                        let value = run.times.realtime_t*1000;
                        let player = run.players.data[0];
                        let player_name=player.names.international;
                        let wr = value < wrTime;
                        if (wr)
                            wrTime = value;

                        // Create series if it doesn't exist
                        if (!_.has(seriesMap,player.id))
                            seriesMap[player.id] = {name: player_name, data:[]};

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
                            custom: {video: video, comment: run.comment}
                        };
                        if (len == 0 || wr)
                            point.dataLabels= {
                                enabled: true,
                                allowOverlap: true,
                                align: 'left',
                                style: {
                                    fontWeight: 'bold',
                                },
                                x: 3,
                                format: (len==0?player_name+" ":"")+(wr?"WR":""),
                                verticalAlign: 'middle',
                                overflow: true,
                                crop: false
                            };
                        if (len==0 || value < seriesMap[player.id].data[len-1].y)
                            seriesMap[player.id].data.push(point);
                    }
                });
                let series = _.values(seriesMap);
                let name = _.find(vm.categories, {id:vm.category}).name;
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
                type: 'spline',
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
            tooltip: {
                style: {
                    pointerEvents: 'auto',
                    'width': '500%',
                    whiteSpace: 'normal'
                },

 //               stickOnContact: true,
                headerFormat: '<b>{series.name} - {point.x:%b %e, %Y} ({point.y:%k:%M:%S})</b><br>',
                pointFormat: '{point.custom.comment}<br/><a href="{point.custom.video}">{point.custom.video}</a>'
            },
            plotOptions: {
                series: {
                    marker: {
                        enabled: true
                    },
                    events: {
                        legendItemClick() {
                            let chart = this.chart,
                                series = chart.series;
                            if (this.index === 0) {
                                console.log(chart.showHideFlag);
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
                                console.log("done");
                                chart.showHideFlag = !chart.showHideFlag;
                                return false;
                            }
                            return true;
                        },
                    }
                },
            },
            // Define the data points. All series have a dummy year
            // of 1970/71 in order to be compared on the same x axis. Note
            // that in JavaScript, months start at 0 for January, 1 for February etc.
            series: series,
        });
    }
</script>