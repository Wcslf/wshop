var page = 0;
function getMessageInfo() {
    page += 1;
    var url = ApiUrl + "/Index/storeMsg";
    $.post(url, {p: page}, function (data) {
        var json = eval("(" + data + ")");
        if (json.status == 1) {
            if (json.result.unread != 0) {
                $("#msgcount").html(json.result.unread);
                $("#message").html(json.result.unread);
            }else{
                $("#message").hide();
            }
            if (isEmpty(json.result.msgList) && json.result.msgCount=='0') {
                html = '<div class="message-empty-con"><img src="../../images/mesage.png"><br><span>暂无消息~</span></div>';
                $('#wp').html(html);
            } else {
                if (isEmpty(json.result.msgList)) {
                    layer.open({content: "没有更多了...", time: 2});
                } else {
                    var html = template.render('tpl', json.result);
                }
                $('#wp').append(html);
            }
        }
    });
}
getMessageInfo();
