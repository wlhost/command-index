<?php
/*
Plugin Name: Command Index
Plugin URI: https://www.klsf.cc/
Description: 仿命令行界面首页插件，可以添加自定义命令
Author: klsf<me@klsf.men>
Version: 1.0.5
Author URI: https://www.klsf.cc/
*/

if (!function_exists('add_action')) {
    exit('403 Forbidden');
}

define('CMD_INDEX_PLUGIN_VERSION', '1.0.5');
define('CMD_INDEX_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CMD_INDEX_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CMD_INDEX_PLUGIN_OPTION', 'cmd_index_plugin');


class CommandIndex
{
    private $option;

    public function __construct()
    {
        $this->option = get_option(CMD_INDEX_PLUGIN_OPTION);
    }

    public function run()
    {
        add_action('init', [$this, 'init']);
        add_action('template_redirect', [$this, 'template_redirect']);
        if (is_admin()) {
            //插件页面添加配置按钮
            add_filter('plugin_action_links', [$this, 'plugin_action_links'], 10, 2);//必须接受两个参数
            add_action('admin_menu', [$this, 'admin_menu']);//添加配置页面

            register_activation_hook(__FILE__, [$this, 'activation']);
            register_deactivation_hook(__FILE__, [$this, 'deactivation']);
        }
    }

    public function activation()
    {
        add_option(CMD_INDEX_PLUGIN_OPTION, [
            'default_mode' => 'command',
            'default_command' => 'ls -h',
            'command' => []
        ]);
    }

    public function deactivation()
    {
        delete_option(CMD_INDEX_PLUGIN_OPTION);
    }

    public function admin_menu()
    {
        add_options_page('命令行首页-配置', 'CommandIndex', 'administrator', 'command_index_config', [$this, 'config_page']);
    }

    function config_page()
    {
        include_once CMD_INDEX_PLUGIN_DIR . 'views/config.php';
    }


    public function plugin_action_links(array $links, $file)
    {
        if ($file == 'command_index/command_index.php') {
            $links[] = '<a href="' . admin_url('options-general.php?page=command_index_config') . '">设置</a>';
        }
        return $links;
    }

    public function init()
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            $url = explode('?', $_SERVER['REQUEST_URI']);
            if ($url[0] === '/index.php/command') {
                include CMD_INDEX_PLUGIN_DIR . '/command.php';
                exit();
            }
        }
        if (isset($_GET['command_index_mode'])) {
            setcookie('command_index_mode', $_GET['command_index_mode'], time() + 3600 * 24 * 30, '/');
            header("Location:/");
            exit();
        }
    }

    public function template_redirect()
    {
        if (is_home()) {
            $ua = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : null;
            if (!strpos($ua, 'spider')) {//不是蜘蛛
                $commandMode = false;

                if (!isset($_COOKIE['command_index_mode']) && $this->option['default_mode'] === 'command') {//默认命令模式
                    $commandMode = true;
                } elseif (isset($_COOKIE['command_index_mode']) && $_COOKIE['command_index_mode'] === 'command') {
                    $commandMode = true;
                }
                if ($commandMode) {
                    include CMD_INDEX_PLUGIN_DIR . '/views/index.php';
                    exit();
                }
            }
        }
    }


    private function get_value($name, $default = null)
    {
        return isset($this->option[$name]) ? $this->option[$name] : $default;
    }

    private function post($name, $default = null)
    {
        return isset($_POST[$name]) ? trim($_POST[$name]) : $default;
    }
}

$commandIndex = new CommandIndex();
$commandIndex->run();

?>
