$(document).ready(function() {
    function getStoreInfo() {
        var url = ApiUrl + "/Index/index";
        //http://apim.tp-shop.cn/sellerApi/Index/index
        $.post(url,{}, function(data) {
            var json = eval("(" + data + ")");
            if (json.status == 1) {
                $("#mobile_value").html(json.result.mobile)
                $("#mobile_value").html(json.result.mobile)
                $("#mobile_value").html(json.result.mobile)
                $("#mobile_value").html(json.result.mobile)
                $("#mobile_value").html(json.result.mobile)
                $("#mobile_value").html(json.result.mobile)
            }
        });
    }
    getStoreInfo();
})

