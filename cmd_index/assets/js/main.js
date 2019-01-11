function setCookie(name, value) {
    var Days = 30;
    var exp = new Date();
    exp.setTime(exp.getTime() + Days * 24 * 60 * 60 * 1000);
    document.cookie = name + "=" + escape(value) + ";expires=" + exp.toGMTString();
}

function getCookie(name) {
    var arr, reg = new RegExp("(^| )" + name + "=([^;]*)(;|$)");
    if (arr = document.cookie.match(reg)) {
        return unescape(arr[2]);
    } else {
        return null;
    }
}


function getTips() {
    var tips = "Last visit: " + new Date();
    if (typeof (returnCitySN) !== 'undefined') {
        tips += " from " + returnCitySN.cip;
    }
    var _tips = getCookie("cmd_index_tips");
    tips = _tips ? _tips : tips;
    setCookie('cmd_index_tips', tips);
    return tips;
}

var _vue = new Vue({
    el: '#window',
    data: {
        commandIndex: -1,
        tips: '',
        action: null,//执行操作类型
        commandTips: null,//命令行前文字，无则显示默认文字
        defaultTips: '[klsf@klsf-blog /]# ',//默认命令行前文字
        rows: [],//显示内容
        commandList: [],//输入过的命令列表

    },
    methods: {
        //重置Data
        resetData: function () {
            this.action = null;
            this.commandTips = null;
            this.page = 0;
            this.commandIndex = -1;
        },
        /**
         * 显示新的一行命令
         * @param rows array 增加显示的内容
         * @param command string
         * @param tips string
         * @param no 不重置data
         */
        newCommand: function (rows, action, tips, no) {
            var _command = $("#command").val();
            var lastCommand = this.action ? (this.commandTips + _command) : (this.defaultTips + _command);
            this.commandTips = tips ? tips : null;
            this.action = action ? action : null;

            var _rows = this.rows;
            _rows.push(lastCommand);
            if (rows) _rows = _rows.concat(rows);

            this.rows = _rows;
            this.commandFocus('');

            if (!no) this.resetData();
        },
        //选择序号跳转网页
        selectData: function (i, cmd) {
            if (i >= this.data.length) {
                this.newCommand(["============================", "此序号不存在"]);
            } else {
                window.open(this.data[i].url);
            }
        },
        //执行命令
        sendCommand: function () {
            var vm = this;
            var command = $("#command").val();
            if (command.length > 0) {
                if (!vm.action) {
                    vm.commandList.unshift(command);//记录输入的命令
                }
                if (command.replace(/\s+/g, "") === 'cls') {
                    //清屏
                    vm.rows = [];
                    vm.commandFocus("");
                    return;
                } else if (command.replace(/\s+/g, "") === 'web') {
                    //切换为WEB访问
                    setCookie('cmd_index_mode', 'web');
                    window.location.reload();
                    return;
                }
                if (vm.action === 'url') {
                    vm.selectData(parseInt(command));
                    return;
                }

                $.ajax({
                    url: "/index.php/command",
                    type: "POST",
                    async: false,
                    data: "command=" + encodeURIComponent(command) + "&action=" + vm.action,
                    dataType: 'json',
                    error: function () {
                        vm.newCommand();
                    },
                    success: function (data) {
                        if (data.action === 'js') {//JS脚本
                            vm.newCommand();
                            eval(data.data);
                        } else if (data.action) {
                            vm.newCommand(data.rows, data.action, data.tips, true);
                        } else {
                            vm.newCommand(data.rows);
                        }
                        if (data.data) vm.data = data.data;
                    }
                });
            } else {
                vm.newCommand();
            }
        },
        //输入内容并且光标闪烁
        commandFocus: function (val) {
            val = typeof(val) === "undefined" ? $("#command").val() : val;
            $("#command").val("").focus().val(val);
        },
        commandInput: function (e) {
            if (this.action) {
                e.target.value = e.target.value.replace(/[^\d]/g, '');//输入命令编号模式，只能输入数字
            }
        }

    },
    mounted: function () {
        var vm = this;

        //显示顶部提示语句
        vm.tips = getTips();

        //绑定键盘事件
        $('#command').bind('keydown', function (e) {
            if (e.keyCode === 13) {
                vm.sendCommand();
            }
            if (!vm.action) {//不是输入编号模式
                if (e.keyCode === 38) {
                    //上一条命令
                    var index = vm.commandIndex + 1;
                    var len = vm.commandList.length;
                    if (index < len && index >= 0) {
                        vm.commandIndex = index;
                        vm.commandFocus(vm.commandList[vm.commandIndex]);
                    }
                } else if (e.keyCode === 40) {
                    //下一条命令
                    var index = vm.commandIndex - 1;
                    var len = vm.commandList.length;
                    if (index < len && index >= -1) {
                        vm.commandIndex = index;
                        vm.commandFocus(index >= 0 ? vm.commandList[vm.commandIndex] : '');
                    }
                }
            }
        });
    }
});