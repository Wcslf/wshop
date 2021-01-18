$(function () {
    $("#loginbut").click(function () {
        login();
    });

    $("#outlogin").click(function () {
        outlogin();
    });
});

function login() {
    var username = $("#username").val();
    var password = $("#password").val();
    if (username == "") {
        layer.open({content: "请输入登陆账号", time: 2});
        return false;
    }
    if (password == "") {
        layer.open({content: "请输入登陆密码", time: 2});
        return false;
    }
    var url = ApiUrl + "/admin/login";
    $.post(url, {username: username, password: password}, function (data) {
        var json = eval("(" + data + ")");
        if (json.status == 1) {
            addCookie("username", json.result.data.seller_name, 10);
            addCookie("key", json.result.data.token, 10);
            var username = getCookie("username");
            var key = getCookie("key");
            location.href = 'seller_index.html';
        } else {
            layer.open({content: json.msg, time: 2});
        }
    });
}

function outlogin() {
    var url = ApiUrl + "/admin/logout";
    $.get(url, function (data) {
        var json = eval("(" + data + ")");
        if (json.status == 1) {
            addCookie("username", "", 10);
            addCookie("key", "", 10);
            //location.href = 'login.html';
        } else {
            layer.open({content: json.msg, time: 2});
        }
    });
}





