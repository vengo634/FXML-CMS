<?php


$MyRoles=getArrayAvailableUserRoles();
$AllowRead=is_access($MyRoles,explode(",",$PLUGIN["settings"]["allowread"]));
$AllowWrite=is_access($MyRoles,explode(",",$PLUGIN["settings"]["allowwrite"]));
$AllowUpload=is_access($MyRoles,explode(",",$PLUGIN["settings"]["allowupload"]));
$AllowDelete=is_access($MyRoles,explode(",",$PLUGIN["settings"]["allowdelete"]));