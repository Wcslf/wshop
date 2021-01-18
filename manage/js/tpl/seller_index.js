$(document).ready(function () {
    function getStoreInfo() {
        var url = ApiUrl + "/Index/index";
        //http://apim.tp-shop.cn/sellerApi/Index/index
        $.post(url, {}, function (data) {
            var json = eval("(" + data + ")");
            if (json.status == 1) {
                $("#todayOrder").html(json.result.todayOrder);
                $("#todayEarnings").html(json.result.todayEarnings);
                $("#yesterdayOrder").html(json.result.yesterdayOrder);
                $("#yesterdayEarnings").html(json.result.yesterdayEarnings);
                $("#message").html(json.result.message);
            } else {
                layer.open({content: json.msg, time: 2});
            }
        });
    }

    getStoreInfo();
})



