var Domain = window.location.href;
var si=Domain.indexOf('manage');
var SiteDomain =Domain.substring(7,si-1);

var SiteUrl = "http://"+SiteDomain+"/index.php/home";
var ApiUrl = "http://"+SiteDomain+"/index.php/sellerApi";
var pagesize = 10;
var WapSiteUrl = "http://"+SiteDomain+"/manage";
var IOSSiteUrl = "http://"+SiteDomain+"/app.ipa";
var AndroidSiteUrl = "http://"+SiteDomain+"/app.apk";
var WeiXinOauth = true;