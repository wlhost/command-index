<?php

if (!function_exists('add_action')) {
    exit('403 Forbidden');
}

class Command
{
    private $option;
    private $command = [
        'command' => null,
        'opts' => [],
        'args' => []
    ];
    private $error = [];

    public function __construct($command)
    {
        $this->option = get_option(CMD_INDEX_PLUGIN_OPTION);
        $this->parse($command);
    }


    public function exec()
    {
        if (!empty($this->error)) {
            return ['command' => null, 'rows' => $this->error];
        } else {
            switch ($this->command['command']) {
                case 'ls':
                    return $this->ls();
                case 'admin':
                    return ['action' => 'js', 'rows' => [], 'data' => 'window.open("/wp-admin")'];
                case 'help':
                    return $this->help();
                default:
                    return $this->command();
            }
        }
    }

    private function command()
    {
        if (isset($this->option['command'])) {
            foreach ($this->option['command'] as $command) {
                if ($command['command'] === $this->command['command']) {
                    if ($command['kind'] === 'js') {
                        return ['action' => 'js', 'rows' => [], 'data' => stripslashes($command['content'])];
                    } else {
                        $list = explode("
", stripslashes($command['content']));
                        return ['action' => null, 'rows' => $list];
                    }
                }
            }
        }
        return ['action' => null, 'rows' => ["{$this->command['command']}: 此命令不存在", "输入 help 查看所有命令"]];
    }

    private function help()
    {
        $list = [
            '<span class="left">ls</span>查看文章列表',
            '<span class="left">ls -h</span>查看ls命令参数详情',
            '<span class="left">cls</span>清除屏幕',
            '<span class="left">web</span>切换为网页浏览'
        ];
        if (isset($this->option['command'])) {
            foreach ($this->option['command'] as $command) {
                $list[] = "<span class='left'>{$command['command']}</span>{$command['desc']}";
            }
        }
        return ['command' => null, 'rows' => $list];
    }

    private function ls()
    {
        if (isset($this->command['opts']['help']) || isset($this->command['opts']['h'])) {
            return ['action' => null, 'rows' => [
                'Usage: ls [options] [args...]',
                '<span class="left">&nbsp;&nbsp;-a ' . htmlspecialchars('<分类ID>') . '</span>获取指定分类列表',
                '<span class="left">&nbsp;&nbsp;-c</span>获取分类列表',
                '<span class="left">&nbsp;&nbsp;-h</span>获取命令详情',
                '<span class="left">&nbsp;&nbsp;-p ' . htmlspecialchars('<页码>') . '</span>查看第几页',
            ]];
        } elseif (isset($this->command['opts']['c'])) {
            //获取分类
            $list = get_categories([
                'type' => 'post',
                'child_of' => 0,
            ]);
            $rows = ["=======================[Category]"];
            if (count($list) > 0) {
                $data = [];
                foreach ($list as $k => $category) {
                    $data[] = [
                        'id' => $category->term_id,
                        'name' => $category->name,
                        'url' => get_category_link($category->term_id)
                    ];
                    $rows[] = "<span class='color-green'>({$k}) [ID:{$category->term_id}]{$category->name}</span>";
                }
                $rows[] = "============================";
                return ['action' => 'url', 'tips' => '请输入查看分类序号：', 'data' => $data, 'rows' => $rows];
            } else {
                $rows[] = "未找到文章";
                $rows[] = "输入 ls 查看最新文章";
                $rows[] = "============================";
                return ['action' => null, 'rows' => $rows];
            }
        }

        $page = isset($this->command['opts']['p']) ? $this->command['opts']['p'] : 1;
        $page = max(intval($page), 1);
        $cid = 0;
        $rows = ["=======================[Page:{$page}]"];

        if (isset($this->command['opts']['a'])) {
            $cid = intval($this->command['opts']['a']);
            if (!$category = get_category($cid)) {
                return ['action' => null, 'rows' => ["分类ID不存在"]];
            }
            $rows = ["=================[Page:{$page}][{$category->name}]"];
        }

        $list = get_posts([
            //需要提取的文章数
            'numberposts' => 10,
            //以第几篇文章为起始位置
            'offset' => ($page - 1) * 10,
            //分类的ID，多个用逗号将分类编号隔开，或传递编号数组，可指定多个分类编号。
            'category' => $cid ? $cid : 0,
            //排序规则
            'orderby' => 'post_date',
            //升序、降序 'ASC' —— 升序 （低到高） 'DESC' —— 降序 （高到底）
            'order' => 'DESC',
            //post（日志）——默认，page（页面），
            //attachment（附件），any —— （所有）
            'post_type' => 'post',
            //文章状态
            'post_status' => 'publish'
        ]);

        if (count($list) > 0) {
            $data = [];
            foreach ($list as $k => $post) {
                $data[] = [
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => get_permalink($post->ID)
                ];
                $rows[] = "<span class='color-green'>({$k}) {$post->post_title}[" . substr($post->post_date, 0, 10) . "]</span>";
            }
            $rows[] = "============================";
            return ['action' => 'url', 'tips' => '请输入查看文章序号：', 'data' => $data, 'rows' => $rows];
        } else {
            $rows[] = "未找到文章";
            $rows[] = "输入 ls 查看最新文章";
            $rows[] = "============================";
            return ['action' => null, 'rows' => $rows];
        }
    }

    private function parse($command)
    {
        if (strlen($command) < 1) {
            $this->error = ["输入 help 查看所有命令"];
            return false;
        }
        $command = array_values(array_filter(explode(' ', $command)));
        $this->command = [
            'command' => $command[0],
            'opts' => [],
            'args' => []
        ];
        for ($i = 1; $i < count($command); $i++) {
            $cmd = $command[$i];
            if (substr($cmd, 0, 2) === '--') {
                if (!$this->parseLongOpts($command, $i)) return false;
            } elseif (substr($cmd, 0, 1) === '-') {
                if (!$this->parseShortOpts($command, $i)) return false;
            } else {
                $this->command['args'][] = $cmd;
            }
        }
    }

    private function parseShortOpts(&$command, &$i)
    {
        $cmd = $command[$i];
        if (strlen($cmd) > 2) {
            if (!$this->checkName(substr($cmd, 1))) return false;
            $this->command['opts'][substr($cmd, 1, 1)] = substr($cmd, 2);
        } elseif (strlen($cmd) == 2) {
            if (isset($command[$i + 1]) && substr($command[$i + 1], 0, 1) !== '-') {//不以 - 开头
                if (!$this->checkName(substr($cmd, 1))) return false;
                $this->command['opts'][substr($cmd, 1)] = $command[$i + 1];
                $i++;
            } else {
                if (!$this->checkName(substr($cmd, 1))) return false;
                $this->command['opts'][substr($cmd, 1)] = 1;
            }
        } else {
            return false;
        }
        return true;
    }

    private function parseLongOpts(&$command, &$i)
    {
        $cmd = $command[$i];
        if (preg_match('/^--(.*?)="(.*?)"$/', $cmd, $r)) {
            if (!$this->checkName(substr($cmd, 2))) return false;
            $this->command['opts'][$r[1]] = $r[2];
        } else {
            if (isset($command[$i + 1]) && substr($command[$i + 1], 0, 1) !== '-') {//不以 - 开头
                if (!$this->checkName(substr($cmd, 2))) return false;
                $this->command['opts'][substr($cmd, 2)] = $command[$i + 1];
                $i++;
            } else {
                if (!$this->checkName(substr($cmd, 2))) return false;
                $this->command['opts'][substr($cmd, 2)] = 1;
            }
        }
        return true;
    }

    /**
     * 判断是不是以字母开头
     * @param $name
     * @return int
     */
    private function checkName($name)
    {
        if (!preg_match('/^[a-z].*$/', $name)) {
            $this->error = ["{$this->command['command']}: 不正确的参数 '{$name}'"];
            if ($this->command['command'] == 'ls') {
                $this->error [] = "输入 'ls -help' 获取更多参数信息";
            }
            return false;
        }
        return true;
    }
}

$command = new Command(isset($_POST['command']) ? $_POST['command'] : null);
exit(json_encode($command->exec()));
