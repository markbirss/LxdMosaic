<?php
require "../../vendor/autoload.php";

$haveServers = $this->container->make("dhope0000\LXDClient\Model\Hosts\HostList");

if ($haveServers->haveAny() === true) {
    header("Location: /");
    exit;
}

$userSession = $this->container->make("dhope0000\LXDClient\Tools\User\UserSession");
$validatePermissions = $this->container->make("dhope0000\LXDClient\Tools\User\ValidatePermissions");

$userId = $userSession->getUserId();
$isAdmin = (int) $validatePermissions->isAdmin($userId);
$apiToken = $userSession->getToken();

echo "<script>var userDetails = {
    isAdmin: $isAdmin,
    apiToken: '$apiToken',
    userId: $userId
} </script>";

?>
<!DOCTYPE html>
<head>
<link rel="apple-touch-icon" sizes="57x57" href="/assets/lxdMosaic/favicons/apple-icon-57x57.png">
<link rel="apple-touch-icon" sizes="60x60" href="/assets/lxdMosaic/favicons/apple-icon-60x60.png">
<link rel="apple-touch-icon" sizes="72x72" href="/assets/lxdMosaic/favicons/apple-icon-72x72.png">
<link rel="apple-touch-icon" sizes="76x76" href="/assets/lxdMosaic/favicons/apple-icon-76x76.png">
<link rel="apple-touch-icon" sizes="114x114" href="/assets/lxdMosaic/favicons/apple-icon-114x114.png">
<link rel="apple-touch-icon" sizes="120x120" href="/assets/lxdMosaic/favicons/apple-icon-120x120.png">
<link rel="apple-touch-icon" sizes="144x144" href="/assets/lxdMosaic/favicons/apple-icon-144x144.png">
<link rel="apple-touch-icon" sizes="152x152" href="/assets/lxdMosaic/favicons/apple-icon-152x152.png">
<link rel="apple-touch-icon" sizes="180x180" href="/assets/lxdMosaic/favicons/apple-icon-180x180.png">
<link rel="icon" type="image/png" sizes="192x192"  href="/assets/lxdMosaic/favicons/android-icon-192x192.png">
<link rel="icon" type="image/png" sizes="32x32" href="/assets/lxdMosaic/favicons/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="96x96" href="/assets/lxdMosaic/favicons/favicon-96x96.png">
<link rel="icon" type="image/png" sizes="16x16" href="/assets/lxdMosaic/favicons/favicon-16x16.png">
<link rel="manifest" href="/assets/lxdMosaic/favicons/manifest.json">
<meta name="msapplication-TileColor" content="#ffffff">
<meta name="msapplication-TileImage" content="/assets/lxdMosaic/favicons/ms-icon-144x144.png">
<meta name="theme-color" content="#ffffff">
<meta charset="utf-8">
<title>LXD Mosaic</title>
<link rel="stylesheet" href="/assets/dist/login.dist.css">
<script src="/assets/dist/login.dist.js"></script>
<style>
body {
    background-color: #a8a8a8;
    font-family: SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace !important;
}

.arrow {
  border: solid white;
  border-width: 0 3px 3px 0;
  display: inline-block;
  padding: 3px;
}

.right {
  transform: rotate(-45deg);
  -webkit-transform: rotate(-45deg);
}

</style>

</head>
<html>
<body>
    <div class="container-fluid">
    <div class="row">
        <div class="col-sm-12 col-md-12 col-lg-6 offset-lg-3">
            <h1 class='text-center mt-5'>LXD Mosaic</h1>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center m-2 mt-3">
                <h4 class=''> Add Your Hosts
                </h4>
            </div>
            <small class="">
                <i class="fa fa-info-circle text-info mr-2"></i>If one LXD host is in a cluster we attempt to add  other cluster members
                using the same trust password!
            </small>
            <div class="mt-2 d-block">
                <button class="btn btn-sm btn-primary mb-2 float-left" id="addServer">
                    <b style="font-size: 1.2rem;" class="mr-2">+</b>Add Another Host
                </button>
                <div class="form-check float-right">
                  <input class="form-check-input" type="checkbox" value="" id="showPasswordCheck" autocomplete="off">
                  <label class="form-check-label" for="showPasswordCheck">
                    Show Passwords
                  </label>
                </div>
            </div>
            <div id="serverGroups" class="d-block mt-2"></div>
            <div class="d-block text-center">
                <button class="btn btn-primary" id="addServers">Manage Hosts <i class="arrow right"></i></button>
            </div>
        </div>

    </div>
    </div>
</body>
</html>
<script>

let inputTemplate = `<div class="input-group mb-3 serverGroup">
    <div class="input-group-prepend">
        <span class="input-group-text serverLabel"></span>
    </div>
    <input placeholder="ip / hostname" name="connectDetails" class="form-control" autocomplete="new-password"/>
    <input placeholder="trust password" name="trustPassword" type="password" class="form-control trustPasswordInput" autocomplete="new-password"/>
    <input placeholder="Alias (Optional)" name="alias" type="text" class="form-control"/>
    <div class="input-group-append">
        <button class="btn btn-sm btn-outline-danger removeRow" type="button">
            <b>X</b>
        </button>
    </div>
</div>`;

$(function(){
    $("#addServer").trigger("click");
});

function reLabelServers(){
    let i = 1;
    $("#serverGroups").find(".serverLabel").each(function(){
        $(this).text("Host " + i);
        i++;
    });
}

$(document).on("change", "#showPasswordCheck", function(){
    if($(this).is(":checked")){
        $(document).find(".trustPasswordInput").attr("type", "text");
    }else{
        $(document).find(".trustPasswordInput").attr("type", "password");
    }
});

$(document).on("click", ".removeRow", function(){
    $(this).parents(".serverGroup").remove();
    reLabelServers();
});

$(document).on("click", "#addServer", function(){
    $("#serverGroups").append(inputTemplate);
    reLabelServers();
});

$(document).on("click", "#addServers", function(){

    if($(".serverGroup").length == 0){
        $("#addServer").trigger("click");
        toastr["error"]("Please provide atleast one host");
        return false;
    }

    let details = {
        hostsDetails: []
    };

    let failed = false;

    $(".serverGroup").each(function(){

        let connectDetailsInput = $(this).find("input[name=connectDetails]");
        let trustPasswordInput = $(this).find("input[name=trustPassword]");
        let connectDetailsInputVal = connectDetailsInput.val();
        let trustPasswordInputVal = trustPasswordInput.val();
        if(connectDetailsInputVal == ""){
            failed = true;
            connectDetailsInput.focus();
            toastr["error"]("Please provide connection details");
            return false;
        } else if(trustPasswordInputVal == ""){
            failed = true;
            trustPasswordInput.focus();
            toastr["error"]("Please provide trust password");
            return false;
        }

        let alias = $(this).find("input[name=alias]").val();
        alias = alias == "" ? null : alias;

        details.hostsDetails.push({
            name: connectDetailsInputVal,
            trustPassword: trustPasswordInputVal,
            alias: alias
        });
    });

    if(failed){
        return false;
    }

    $.ajax({
         type: 'POST',
         headers: userDetails,
         data: details,
         url: "/api/Hosts/AddHostsController/add",
         success: function(data){
             let result = $.parseJSON(data);
             if(result.state !== "success"){
                 toastr["error"](result.message);
                 return false;
             }
             location.reload();
         }
     });
});

</script>
