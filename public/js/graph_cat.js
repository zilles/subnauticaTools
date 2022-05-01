/*
Handling the hash path

Initial load:
Load the categories:
Parse the hash, splitting on /
If the first section is a valid category name, load that category
If not change hash so that first category name is added to path

Category load:
Load variables for category.

If hash splits after first one map to valid variables then set variables
Otherwise reset hash to be first variables

Whenever hash changes:
If category changes, load new category that matches.  Set variables to defaults.
If variables change reload graph.

 */


function isTouchDevice() {
    return (('ontouchstart' in window) ||
        (navigator.maxTouchPoints > 0) ||
        (navigator.msMaxTouchPoints > 0));
}

var game = ['9dom836p','76rx0ze6'];

var app = Vue.createApp({
    data() {
        return {
            category: '',
            category_select: '',
            categories: [],
            variables: [],
            runs: [],
            currentPath: window.location.hash,
            loading: false
        }
    },
    computed: {
        cat() {
            return _.find(this.categories, {id:this.category});
        },
        segments() {
            return _.map(this.currentPath.substring(1).split("/"), function(x) {
                return decodeURI(x);
            });
        }
    },
    methods: {
        runMatches(run,variables) {
            return _.every(variables, function(variable) {
                return run.values[variable.id] === variable.hash;
            });
        },
        updateVariables() {
            var newSegs = [this.segments[0]];
            _.each(this.variables, function(variable) {
                newSegs.push(variable.values.values[variable.selected].label);
            });
            this.setHash(newSegs);
        },
        setHash(segs) {
            window.location.hash = _.map(segs,function(x) { return encodeURIComponent(x);}).join("/");
        },
        updateCategoryFromHash() {
            var categoryChanged = false;
            var updateHash = true;
            if (this.segments.length>0)
            {
                var start = _.find(this.categories, {name:this.segments[0]});
                if (start)
                {
                    if (this.category !== start.id)
                    {
                        this.category = start.id;
                        this.category_select = start.name;
                        categoryChanged = true;
                    }
                    updateHash = false;
                }
            }
            if (updateHash)
                this.setHash([this.categories[0].name]);
            return categoryChanged;
        },
        updateVariablesFromHash() {
            var newSegs = [this.segments[0]];
            var switchHash = false;
            var vm = this;
            _.each(this.variables, function(variable,index) {
                var updateHash = true;
                var label = vm.segments[index+1];
                if (label)
                {
                    var startKey = _.findKey(variable.values.values, {label: label});
                    if (startKey) {
                        variable.selected = startKey;
                        variable.hash = startKey;
                        newSegs.push(label);
                        updateHash = false;
                    }
                }

                if (updateHash)
                {
                    variable.selected = _.keys(variable.values.values)[0];
                    newSegs.push(variable.values.values[variable.selected].label);
                    switchHash = true;
                }
            });
            if (switchHash)
                this.setHash(newSegs);
        },
        createGraph()
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

                    let videotext = video? (video+"<br/>(click point to launch video)"):"";
                    if (isTouchDevice())
                        videotext = '<a href="'+video+'">'+video+'</a>';

                    let point = {
                        x:new Date(run.date).getTime(),
                        y:value,
                        events: {
                            click: function() {
                                if (video && !isTouchDevice())
                                    window.open(video);
                            }
                        },
                        custom: {video: videotext, comment: run.comment, wr: wr ? "WR" : ""}
                    };
                    if (len === 0 || wr)
                        point.dataLabels= {
                            format: (len===0?player_name+" ":"")+(wr?"WR":""),
                        };
                    if (len===0 || value < seriesMap[player.id].data[len-1].y)
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
        category_select(val) {
            var segs = this.segments.slice();
            segs[0] = val;
            this.setHash(segs);
        },
        segments() {
            if (!this.updateCategoryFromHash())
            {
                this.updateVariablesFromHash();
                if (!this.loading)
                    this.createGraph();
            }
        },
        category(val) {
            if (val)
            {
                let vm = this;
                let getRuns = function(offset) {
                    axios.get('https://www.speedrun.com/api/v1/runs?max=200&orderby=date&embed=players&status=verified&category='+val+'&offset='+offset)
                        .then(function (response) {
                            // handle success
                            // if it still matches
                            if (val === vm.category)
                            {
                                if (offset === 0)
                                    vm.runs = response.data.data;
                                else
                                    vm.runs = vm.runs.concat(response.data.data);

                                if (response.data.data.length === 200)
                                    getRuns(offset+200);
                                else
                                {
                                    vm.loading = false;
                                    setTimeout(function() {
                                        vm.createGraph();
                                    },0);
                                }

                            }
                        })
                        .catch(function (error) {
                            // handle error
                            console.log(error);
                        });
                }
                vm.loading = true;
                axios.get('https://www.speedrun.com/api/v1/categories/'+val+'/variables')
                    .then(function (response) {
                        // handle success
                        vm.variables = response.data.data;
                        vm.updateVariablesFromHash();
                        getRuns(0);
                    })
                    .catch(function (error) {
                        // handle error
                        console.log(error);
                    });
            }
        }
    },
    mounted() {
        let catPromises = _.map(game, function(g) {
            return axios.get('https://www.speedrun.com/api/v1/games/'+g+'/categories');
        });

        var vm = this;
        Promise.all(catPromises)
            .then(function (response) {
                // handle success
                //console.log(response);
                vm.categories = response[0].data.data.concat(response[1].data.data);
                vm.updateCategoryFromHash();
            })
            .catch(function (error) {
                // handle error
                console.log(error);
            });

        window.addEventListener('hashchange', function() {
            vm.currentPath = window.location.hash
        })
    }

})

app.mount('#app')


function makeChart(name, series) {

    series.unshift({ name: 'Show/Hide all',  marker: { enabled:false}});

    Highcharts.chart('container', {
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

            stickOnContact: isTouchDevice(),
            headerFormat: '<b>{series.name} - {point.x:%b %e, %Y} - ({point.y:%k:%M:%S}) {point.custom.wr}</b><br/>',
            pointFormat: '{point.custom.comment}<br/><br/>{point.custom.video}'
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