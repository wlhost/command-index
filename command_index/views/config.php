<?php
if (!function_exists('add_action')) {
    exit('403 Forbidden');
}

$action = $this->post('action');
if ($action === 'config') {
    $this->option['default_mode'] = $this->post('default_mode', 'command');
    $this->option['default_command'] = $this->post('default_command', 'command');
    update_option(CMD_INDEX_PLUGIN_OPTION, $this->option);
    $message = '设置已保存。';
} elseif ($action === 'addCommand') {
    $command = [
        'command' => $this->post('command'),
        'desc' => $this->post('desc'),
        'kind' => $this->post('kind'),
        'content' => $this->post('content')
    ];
    $this->option['command'][] = $command;
    update_option(CMD_INDEX_PLUGIN_OPTION, $this->option);
    $message = '添加自定义命令成功。';
} elseif ($action === 'delCommand') {
    $i = intval($this->post('i'));
    if (isset($this->option['command'][$i])) {
        unset($this->option['command'][$i]);
        $this->option['command'] = array_values($this->option['command']);
        update_option(CMD_INDEX_PLUGIN_OPTION, $this->option);
        $message = '删除命令成功。';
    } else {
        $message = '命令不存在。';
    }
}


?>
<div class="wrap">
    <h1>命令行首页配置</h1>
    <p>用户访问 <?= home_url() ?>/?command_index_mode=command 即可切换为命令行模式。因此在首页放置一个此链接即可用户自主点击切换！<br></p>
    <h2>基本设置</h2>
    <?php
    if (isset($message)) {
        echo <<<HTML
<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible">
    <p><strong>$message</strong></p>
    <button type="button" class="notice-dismiss"><span class="screen-reader-text">忽略此通知。</span></button>
</div>
HTML;
    }
    ?>
    <form method="post" action="">
        <input type="hidden" name="action" value="config">
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row"><label>默认首页模式</label></th>
                <td>
                    <select name="default_mode" class="postform">
                        <option value="command" <?= $this->get_value('default_mode') === 'command' ? 'selected' : '' ?>>
                            命令行
                        </option>
                        <option value="web" <?= $this->get_value('default_mode') === 'web' ? 'selected' : '' ?>>网页
                        </option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label>默认执行命令</label></th>
                <td>
                    <input name="default_command" type="text" value="<?= $this->get_value('default_command') ?>"
                           class="regular-text ltr">
                </td>
            </tr>
            </tbody>
        </table>
        <p class="submit"><input type="submit" name="submit" class="button button-primary" value="保存更改"></p>
    </form>
    <h2 class="title">自定义命令</h2>
    <table class="wp-list-table widefat fixed striped posts">
        <thead>
        <tr>
            <th>命令</th>
            <th>类型</th>
            <th>介绍</th>
            <th>执行内容</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($this->get_value('command', []) as $k => $command) {
            echo "<tr><td>{$command['command']}</td><td>{$command['kind']}</td><td>{$command['desc']}</td><td><pre>" . htmlentities(stripslashes($command['content'])) . "</pre></td><td>
<form method='post'><input type='hidden' name='action' value='delCommand'><input type='hidden' name='i' value='{$k}'><input type='submit' class='button button-primary' value='删除'></form>
</td></tr>";
        }
        ?>
        </tbody>
    </table>
    <h2 class="title">添加自定义命令</h2>
    <form method="post" action="">
        <input type="hidden" name="action" value="addCommand">
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row"><label>命令</label></th>
                <td>
                    <input name="command" type="text" value="" class="regular-text ltr">
                </td>
            </tr>
            <tr>
                <th scope="row"><label>介绍</label></th>
                <td>
                    <input name="desc" type="text" value="" class="regular-text ltr">
                </td>
            </tr>
            <tr>
                <th scope="row"><label>类型</label></th>
                <td>
                    <select name="kind" class="postform">
                        <option value="text">返回文本(一行一条)</option>
                        <option value="js">执行JS</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label>执行内容</label></th>
                <td>
                    <textarea name="content" class="large-text code" rows="3"></textarea>
                </td>
            </tr>
            </tbody>
        </table>
        <p class="submit"><input type="submit" name="submit" class="button button-primary" value="确认添加"></p>
    </form>

</div>