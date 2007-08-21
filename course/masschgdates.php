<?php
//IMathAS:  Mass Change Assessment Dates
//(c) 2006 David Lippman
	require("../validate.php");
	
	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	
	$cid = $_GET['cid'];
	
	if (isset($_POST['chgcnt'])) {
		$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$items = unserialize(mysql_result($result,0,0));
		
		$cnt = $_POST['chgcnt'];
		$blockchg = 0;
		for ($i=0; $i<$cnt; $i++) {
			require_once("parsedatetime.php");
			if ($_POST['sdate'.$i]=='0') {
				$startdate = 0;
			} else {
				$startdate = parsedatetime($_POST['sdate'.$i],$_POST['stime'.$i]);
			}
			if ($_POST['edate'.$i]=='2000000000') {
				$enddate = 2000000000;
			} else {
				$enddate = parsedatetime($_POST['edate'.$i],$_POST['etime'.$i]);
			}
			if ($_POST['rdate'.$i]=='0') {
				$reviewdate = 0;
			} else if ($_POST['rdate'.$i]=='2000000000') {
				$reviewdate = 2000000000;
			} else {
				$reviewdate = parsedatetime($_POST['rdate'.$i],$_POST['rtime'.$i]);	
			}
			$type = $_POST['type'.$i];
			$id = $_POST['id'.$i];
			if ($type=='Assessment') {
				if ($id>0) {
					$query = "UPDATE imas_assessments SET startdate='$startdate',enddate='$enddate',reviewdate='$reviewdate' WHERE id='$id'";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
			} else if ($type=='Forum') {
				if ($id>0) {
					$query = "UPDATE imas_forums SET startdate='$startdate',enddate='$enddate' WHERE id='$id'";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
			} else if ($type=='InlineText') {
				if ($id>0) {
					$query = "UPDATE imas_inlinetext SET startdate='$startdate',enddate='$enddate' WHERE id='$id'";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
			} else if ($type=='LinkedText') {
				if ($id>0) {
					$query = "UPDATE imas_linkedtext SET startdate='$startdate',enddate='$enddate' WHERE id='$id'";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
			} else if ($type=='Block') {
				$blocktree = explode('-',$id);
				$sub =& $items;
				if (count($blocktree)>1) {
					for ($j=1;$j<count($blocktree)-1;$j++) {
						$sub =& $sub[$blocktree[$j]-1]['items']; //-1 to adjust for 1-indexing
					}
				}
				$sub =& $sub[$blocktree[$j]-1];
				$sub['startdate'] = $startdate;
				$sub['enddate'] = $enddate;
				$blockchg++;
			}
			
		}
		if ($blockchg>0) {
			$itemorder = addslashes(serialize($items));
			$query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid';";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
		}
		
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid=$cid");
			
		exit;
	}
	$shortdays = array("Su","M","Tu","W","Th","F","Sa");
	function getshortday($atime) {
		global $shortdays;
		return $shortdays[date('w',$atime)];
	}
	
	$pagetitle = "Mass Change Dates";
	$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/masschgdates.js\"></script>";
	require("../header.php");
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid=$cid\">$coursename</a> ";	
	echo "&gt; Mass Change Dates</div>\n";
	echo "<h2>Mass Change Dates</h2>";
	echo '<script src="../javascript/CalendarPopup.js"></script>';
	echo '<SCRIPT LANGUAGE="JavaScript" ID="js1">';
	echo 'var cal1 = new CalendarPopup();';
	echo 'cal1.setReturnFunction ("calcallback");';
	echo 'var basesdates = new Array(); var baseedates = new Array(); var baserdates = new Array();';
	echo '</SCRIPT>';
	
	if (isset($_GET['orderby'])) {
		$orderby = $_GET['orderby'];
	} else {
		$orderby = 0;
	}
	if (isset($_GET['filter'])) {
		$filter = $_GET['filter'];
	} else {
		$filter = "all";
	}
	echo "<script type=\"text/javascript\">var filteraddr = \"$imasroot/course/masschgdates.php?cid=$cid&orderby=$orderby\";";
	
	echo "var orderaddr = \"$imasroot/course/masschgdates.php?cid=$cid&filter=$filter\";</script>";
	
	echo '<p>Order by: <select id="orderby" onchange="chgorderby()">';
	echo '<option value="0" ';
	if ($orderby==0) {echo 'selected="selected"';}
	echo '>Start Date</option>';
	echo '<option value="1" ';
	if ($orderby==1) {echo 'selected="selected"';}
	echo '>End Date</option>';
	echo '<option value="2" ';
	if ($orderby==2) {echo 'selected="selected"';}
	echo '>Name</option>';
	echo '</select> ';
	
	echo 'Filter by type: <select id="filter" onchange="filteritems()">';
	echo '<option value="all" ';
	if ($filter=='all') {echo 'selected="selected"';}
	echo '>All</option>';
	echo '<option value="assessments" ';
	if ($filter=='assessments') {echo 'selected="selected"';}
	echo '>Assessments</option>';
	echo '<option value="inlinetext" ';
	if ($filter=='inlinetext') {echo 'selected="selected"';}
	echo '>Inline Text</option>';
	echo '<option value="linkedtext" ';
	if ($filter=='linkedtext') {echo 'selected="selected"';}
	echo '>Linked Text</option>';
	echo '<option value="forums" ';
	if ($filter=='forums') {echo 'selected="selected"';}
	echo '>Forums</option>';
	echo '<option value="blocks" ';
	if ($filter=='blocks') {echo 'selected="selected"';}
	echo '>Blocks</option>';
	echo '</select>';
	echo '</p>';
	
	echo "<p><input type=checkbox id=\"onlyweekdays\" checked=\"checked\"> Shift by weekdays only</p>";
	echo "<form method=post action=\"masschgdates.php?cid=$cid\">";
	echo '<table class=gb><thead><tr><th>Name</th><th>Type</th><th>Start Date</th><th>End Date</th><th>Review Date</th><th>Send Date Chg Down List</th></thead><tbody>';
	
	$names = Array();
	$startdates = Array();
	$enddates = Array();
	$reviewdates = Array();
	$ids = Array();
	$types = Array();
	
	if ($filter=='all' || $filter=='assessments') {
		$query = "SELECT name,startdate,enddate,reviewdate,id FROM imas_assessments WHERE courseid='$cid' ";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$types[] = "Assessment";
			$names[] = $row[0];
			$startdates[] = $row[1];
			$enddates[] = $row[2];
			$reviewdates[] = $row[3];
			$ids[] = $row[4];
		}
	}
	if ($filter=='all' || $filter=='inlinetext') {
		$query = "SELECT title,startdate,enddate,id FROM imas_inlinetext WHERE courseid='$cid' ";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$types[] = "InlineText";
			$names[] = $row[0];
			$startdates[] = $row[1];
			$enddates[] = $row[2];
			$reviewdates[] = 0;
			$ids[] = $row[3];
		}
	}
	if ($filter=='all' || $filter=='linkedtext') {
		$query = "SELECT title,startdate,enddate,id FROM imas_linkedtext WHERE courseid='$cid' ";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$types[] = "LinkedText";
			$names[] = $row[0];
			$startdates[] = $row[1];
			$enddates[] = $row[2];
			$reviewdates[] = 0;
			$ids[] = $row[3];
		}
	}
	if ($filter=='all' || $filter=='forums') {
		$query = "SELECT name,startdate,enddate,id FROM imas_forums WHERE courseid='$cid' ";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$types[] = "Forum";
			$names[] = $row[0];
			$startdates[] = $row[1];
			$enddates[] = $row[2];
			$reviewdates[] = 0;
			$ids[] = $row[3];
		}
	}
	if ($filter=='all' || $filter=='blocks') {
		$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$items = unserialize(mysql_result($result,0,0));
		
		function getblockinfo($items,$parent) {
			global $ids,$types,$names,$startdates,$enddates,$reviewdates,$ids;
			foreach($items as $k=>$item) {
				if (is_array($item)) {
					$ids[] = $parent.'-'.($k+1);
					$types[] = "Block";
					$names[] = stripslashes($item['name']);
					$startdates[] = $item['startdate'];
					$enddates[] = $item['enddate'];
					$reviewdates[] = 0;
					if (count($item['items'])>0) {
						getblockinfo($item['items'],$parent.'-'.($k+1));
					}
				} 
			}
		}
		getblockinfo($items,'0');
	}
	$cnt = 0;
	$now = time();
	if ($orderby==0) {
		asort($startdates);
		$keys = array_keys($startdates);
	} else if ($orderby==1) {
		asort($enddates);
		$keys = array_keys($enddates);
	} else if ($orderby==2) {
		natcasesort($names);
		$keys = array_keys($names);
	}
	foreach ($keys as $i) {
		$sdate = tzdate("m/d/Y",$startdates[$i]);
		$stime = tzdate("g:i a",$startdates[$i]);
		$edate = tzdate("m/d/Y",$enddates[$i]);
		$etime = tzdate("g:i a",$enddates[$i]);
		$rdate = tzdate("m/d/Y",$reviewdates[$i]);
		$rtime = tzdate("g:i a",$reviewdates[$i]);
		echo '<tr class=grid>';
		echo "<td>{$names[$i]}<input type=hidden name=\"id$cnt\" value=\"{$ids[$i]}\"/>";
		echo "<script> basesdates[$cnt] = ";
		if ($startdates[$i]==0) { echo '"NA"';} else {echo $startdates[$i];}
		echo "; baseedates[$cnt] = ";
		if ($enddates[$i]==0 || $enddates[$i]==2000000000) { echo '"NA"';} else {echo $enddates[$i];}
		echo "; baserdates[$cnt] = ";
		if ($reviewdates[$i]==0 || $reviewdates[$i]==2000000000) {echo '"NA"';} else { echo $reviewdates[$i];}
		echo ";</script>";
		echo "</td><td>";
		echo "{$types[$i]}<input type=hidden name=\"type$cnt\" value=\"{$types[$i]}\"/>";
		if ($types[$i]=='Assessment') {
			if ($now>$startdates[$i] && $now<$enddates[$i]) {
				echo " <i><a href=\"addquestions.php?aid={$ids[$i]}&cid=$cid\">Q</a></i>";	
			} else {
				echo " <a href=\"addquestions.php?aid={$ids[$i]}&cid=$cid\">Q</a>";
			}
			echo " <a href=\"addassessment.php?id={$ids[$i]}&cid=$cid&from=mcd\">S</a>\n";
		}
		echo "</td>";
		
		
		if ($startdates[$i]==0) {
			echo "<td><input type=hidden id=\"sdate$cnt\" name=\"sdate$cnt\" value=\"0\"/>Always</td>";
		} else {
			echo "<td><input type=text size=10 id=\"sdate$cnt\" name=\"sdate$cnt\" value=\"$sdate\" onblur=\"ob(this)\"/>(";
			echo "<span id=\"sd$cnt\">".getshortday($startdates[$i]).'</span>';
			echo ") <a href=\"#\" onClick=\"cal1.select(document.forms[0].sdate$cnt,'anchor$cnt','MM/dd/yyyy',document.forms[0].sdate$cnt.value); return false;\" NAME=\"anchor$cnt\" ID=\"anchor$cnt\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";
			echo " at <input type=text size=8 id=\"stime$cnt\" name=\"stime$cnt\" value=\"$stime\"></td>";
		}
		
		if ($enddates[$i]==2000000000) {
			echo "<td><input type=hidden id=\"edate$cnt\" name=\"edate$cnt\" value=\"2000000000\"/>Always</td>";
		} else {
			echo "<td><input type=text size=10 id=\"edate$cnt\" name=\"edate$cnt\" value=\"$edate\" onblur=\"ob(this)\"/>(";
			echo "<span id=\"ed$cnt\">".getshortday($enddates[$i]).'</span>';
			echo ") <a href=\"#\" onClick=\"cal1.select(document.forms[0].edate$cnt,'anchor2$cnt','MM/dd/yyyy',document.forms[0].edate$cnt.value); return false;\" NAME=\"anchor2$cnt\" ID=\"anchor2$cnt\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";
			echo " at <input type=text size=8 id=\"etime$cnt\" name=\"etime$cnt\" value=\"$etime\"></td>";
		}
		if ($reviewdates[$i]==0) {
			echo "<td><input type=hidden id=\"rdate$cnt\" name=\"rdate$cnt\" value=\"0\"/>Never</td>";
		} else if ($reviewdates[$i]==2000000000) {
			echo "<td><input type=hidden id=\"rdate$cnt\" name=\"rdate$cnt\" value=\"2000000000\"/>Always</td>";
		} else {
			echo "<td><input type=text size=10 id=\"rdate$cnt\" name=\"rdate$cnt\" value=\"$rdate\" onblur=\"ob(this)\"/>(";
			echo "<span id=\"rd$cnt\">".getshortday($reviewdates[$i]).'</span>';
			echo ") <a href=\"#\" onClick=\"cal1.select(document.forms[0].rdate$cnt,'anchor3$cnt','MM/dd/yyyy',document.forms[0].rdate$cnt.value); return false;\" NAME=\"anchor3$cnt\" ID=\"anchor3$cnt\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";
			echo " at <input type=text size=8 id=\"rtime$cnt\" name=\"rtime$cnt\" value=\"$rtime\"></td>";
		}
		echo "<td><input type=button value=\"Send Down List\" onclick=\"senddown($cnt)\"/></td>";
		echo "</tr>";
		$cnt++;
	}
	echo '</tbody></table>';
	echo "<input type=hidden name=\"chgcnt\" value=\"$cnt\" />";
	echo '<input type=submit value="Save Changes"/>';
	echo '</form>';
	//echo "<script>var acnt = $cnt;</script>";
	
	require("../footer.php");

?>
