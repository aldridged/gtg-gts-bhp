<%@ taglib uri="./Track" prefix="gts" %>
<%@ page isELIgnored="true" contentType="text/html; charset=UTF-8" %>
<%
//response.setContentType("text/html; charset=UTF-8");
//response.setCharacterEncoding("UTF-8");
response.setHeader("CACHE-CONTROL", "NO-CACHE");
response.setHeader("PRAGMA"       , "NO-CACHE");
response.setDateHeader("EXPIRES"  , 0         );
%>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<!-- DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN"> -->
<!-- DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> -->
<html xmlns='http://www.w3.org/1999/xhtml' xmlns:v='urn:schemas-microsoft-com:vml'>
<!-- loginSession_banner.jsp
  =======================================================================================
  Copyright(C) 2007-2010 GeoTelematic Solutions, Inc., All rights reserved
  Project: OpenGTS - Open GPS Tracking System [http://www.opengts.org]
  =======================================================================================
-->
<gts:var ifKey="notDefined" value="true">
<!--
  See "logSession.jsp" for additional notes
  =======================================================================================
  Change History:
   2010/01/28  Martin D. Flynn
      -Initial Release
  =======================================================================================
-->
</gts:var>

<!-- Head -->
<head>

  <!-- meta -->
  <gts:var>
  <meta name="author" content="GeoTelematic Solutions, Inc."/>
  <meta http-equiv="content-type" content='text/html; charset=UTF-8'/>
  <meta http-equiv="cache-control" content='no-cache'/>
  <meta http-equiv="pragma" content="no-cache"/>
  <meta http-equiv="expires" content="0"/>
  <meta name="copyright" content="${copyright}"/>
  <meta name="robots" content="none"/>
  </gts:var>

  <!-- page title -->
  <gts:var>
  <title>${pageTitle}</title>
  </gts:var>

  <!-- default style -->
  <link rel='stylesheet' type='text/css' href='css/General.css'/>
  <link rel='stylesheet' type='text/css' href='css/MenuBar.css'/>
  <link rel='stylesheet' type='text/css' href='css/Controls.css'/>

  <!-- custom overrides style -->
  <link rel='stylesheet' type='text/css' href='custom/General.css'/>
  <link rel='stylesheet' type='text/css' href='custom/MenuBar.css'/>
  <link rel='stylesheet' type='text/css' href='custom/Controls.css'/>
  
  <!-- page redesign styles -->
  <link rel='stylesheet' type='text/css' href='css/redesign.css'/>
  
  <!-- jquery ui -->
  <link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
  <script src="http://code.jquery.com/jquery-1.9.1.js"></script>
  <script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
  
  <!-- jtree -->
  <script type="text/javascript" src="/jstree/_lib/jquery.cookie.js"></script>
  <script type="text/javascript" src="/jstree/_lib/jquery.hotkeys.js"></script>
  <script type="text/javascript" src="/jstree/jquery.jstree.js"></script>

  <!-- javascript -->
  <script src="./js/utils.js" type="text/javascript"></script>
  <gts:track section="javascript"/>

  <!-- page specific style -->
  <gts:track section="stylesheet"/>

  <!-- custom override style -->
  <link rel='stylesheet' type='text/css' href='custom/Custom.css'/>

</head>

<!-- ======================================================================================= -->

<body onload="<gts:track section='body.onload'/>" onunload="<gts:track section='body.onunload'/>">
<div id="container" style="width:1024px; height:750px; margin-left:auto; margin-right:auto;">
	<gts:var ifKey="isLoggedIn" value="false">
	<div id="spacer" style="height:50px;">&nbsp;</div>
	</gts:var>
	<gts:var ifKey="isLoggedIn" value="true">
	<div id="menu">
	    <ul>
    	<li><a href="Track?page=map.fleet">Map</a></li>
    	<li><a href="#">Reports</a>
			<ul>
			<li><a href="Track?page=menu.rpt.devDetail"><div style="width:100px;">Modem Detail</div></a></li>
			<li><a href="Track?page=menu.rpt.grpDetail"><div style="width:100px;">Group Detail</div></a></li>
			<li><a href="Track?page=menu.rpt.grpSummary"><div style="width:100px;">Group Summary</div></a></li>
			</ul>
		</li>
    	<li><a href="#">Admin</a>
			<ul>
			<li><a href="Track?page=user.info"><div style="width:100px;">User Admin</div></a></li>
			<li><a href="Track?page=dev.info"><div style="width:100px;">Device Admin</div></a></li>
			<li><a href="Track?page=group.info"><div style="width:100px;">Group Admin</div></a></li>
			<li><a href="Track?page=passwd"><div style="width:100px;">Password</div></a></li>
			</ul>
		</li>
    	<li><a href="Track?page=login">Logout</a></li>
		</ul>
	</div>
	</gts:var>
    <div id="content_container" style="margin-left:auto;margin-right:auto;">
		<gts:track section="content.body"/>
    </div>
	<div id="message_container" style="margin-left:auto;margin-right:auto;">
		<center>
		<gts:track section="content.message"/>
		</center>
	</div>
	<gts:var ifKey="isLoggedIn" value="true">
	 <div id="footer" style="width:1024px; height:20px; background:navy; color:white;">
		<i>${i18n.Account}:</i> ${accountDesc} (${userDesc})
	 </div>
	 </gts:var>
</div>
</body>

</html>