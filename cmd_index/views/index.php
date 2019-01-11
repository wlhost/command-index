<?php
if (!function_exists('add_action')) {
    exit('403 Forbidden');
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title><?= get_bloginfo('name') ?></title>
    <script src="https://pv.sohu.com/cityjson"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.5.21/vue.min.js"></script>
    <link rel="stylesheet" href="<?= CMD_INDEX_PLUGIN_URL ?>assets/css/style.css?ver=<?= CMD_INDEX_PLUGIN_VERSION ?>"/>
</head>
<body>
<div id="window" @click="commandFocus()">
    <ul>
        <li>Welcome to my blog</li>
        <li v-html="tips"></li>
        <li class="color-green"><span class="left">ls</span>列出文章</li>
        <li class="color-green"><span class="left">cls</span>清除屏幕</li>
        <li class="color-green"><span class="left">web</span>切换为网页浏览</li>
        <li class="color-green"><span class="left">help</span>查看帮助</li>
        <li v-for="(row,i) in rows" :key="i" v-html="row"></li>
        <li><span v-html="action?commandTips:defaultTips"></span>
            <input id="command" type="text" @input="commandInput"/>
        </li>
    </ul>
</div>
<script src="<?= CMD_INDEX_PLUGIN_URL ?>assets/js/main.js?ver=<?= CMD_INDEX_PLUGIN_VERSION ?>"></script>
<?php
$option = get_option(CMD_INDEX_PLUGIN_OPTION);
if (isset($option['default_command']) && $option['default_command']) {//默认执行命令
    echo <<<HTML
<script>
    $("#command").val("").focus().val("{$option['default_command']}");
    _vue.sendCommand();
</script>
HTML;
}
?>
</body>
</html>
