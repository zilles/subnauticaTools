<div id="app">
    <a id="leaderboard_link" target="_blank" :href="cat.weblink" v-if="cat">{{cat.name}} Leaderboard</a>
    <select v-model="category_select">
        <option v-for="cat in categories" :key="cat.name" v-bind:value="cat.name">
            {{cat.name}}
        </option>
    </select>
    <span v-for="variable in variables">
        &nbsp;{{variable.name}}:
        <select v-model="variable.selected" v-on:change="updateVariables()">
            <option v-for="(value,key) in variable.values.values" :key="key" v-bind:value="key">
                {{value.label}}
            </option>
        </select>
    </span>
    <div id="loading" v-if="loading">
            <div class="lds-dual-ring"></div>
            Loading run data {{runs.length}}...
    </div>
    <div id="container" style="width:100%; height:50vw;"></div>
    <?php if (false) { //yiiparam("debug")) { ?>
    <pre v-for="run in runs" v-if="runMatches(run,variables) || run.players.data[0].names.international==='salvner'">
    {{ run }}
    </pre>
    <?php } ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<?php if (yiiparam("debug")) { ?>
    <script src="https://unpkg.com/vue@3"></script>
<?php } else {?>
    <script src="https://unpkg.com/vue@3.2.33"></script>
<?php } ?>
<script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
<script src="https://code.highcharts.com/stock/highstock.js"></script>
<script src="/js/graph_cat.js?<?php echo filemtime("js/graph_cat.js")?>"></script>
