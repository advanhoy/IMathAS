<?php
//IMathAS:  New threads list for a course
//(c) 2006 David Lippman
   	require("../validate.php");
	$cid = $_GET['cid'];
	
	/*
	$query = "SELECT imas_forums.name,imas_forums.id,imas_forum_posts.threadid,max(imas_forum_posts.postdate) as lastpost,mfv.lastview,count(imas_forum_posts.id) as pcount FROM imas_forum_posts ";
	$query .= "JOIN imas_forums ON imas_forum_posts.forumid=imas_forums.id LEFT JOIN (SELECT * FROM imas_forum_views WHERE userid='$userid') AS mfv ";
	$query .= "ON mfv.threadid=imas_forum_posts.threadid WHERE imas_forums.courseid='$cid' AND imas_forums.grpaid=0 ";
	$query .= "GROUP BY imas_forum_posts.threadid HAVING ((max(imas_forum_posts.postdate)>mfv.lastview) OR (mfv.lastview IS NULL))";
	*/
	$query = "SELECT imas_forums.name,imas_forums.id,imas_forum_threads.id as threadid,imas_forum_threads.lastposttime FROM imas_forum_threads ";
	$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id LEFT JOIN imas_forum_views AS mfv ";
	$query .= "ON mfv.threadid=imas_forum_threads.id AND mfv.userid='$userid' WHERE imas_forums.courseid='$cid' AND imas_forums.grpaid=0 ";
	$query .= "AND (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL))";
	
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$forumname = array();
	$forumids = array();
	$lastpost = array();
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$forumname[$line['threadid']] = $line['name'];
		$forumids[$line['threadid']] = $line['id'];
		$lastpost[$line['threadid']] = tzdate("F j, Y, g:i a",$line['lastposttime']);
	}
	$lastforum = '';
	
	if (isset($_GET['markallread'])) {
		foreach($forumids as $forumid) {
			$query = "SELECT DISTINCT threadid FROM imas_forum_posts WHERE forumid='$forumid'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			$now = time();
			while ($row = mysql_fetch_row($result)) {
				$query = "SELECT id FROM imas_forum_views WHERE userid='$userid' AND threadid='{$row[0]}'";
				$r2 = mysql_query($query) or die("Query failed : $query " . mysql_error());
				if (mysql_num_rows($r2)>0) {
					$r2id = mysql_result($r2,0,0);
					$query = "UPDATE imas_forum_views SET lastview=$now WHERE id='$r2id'";
					mysql_query($query) or die("Query failed : $query " . mysql_error());
				} else{
					$query = "INSERT INTO imas_forum_views (userid,threadid,lastview) VALUES ('$userid','{$row[0]}',$now)";
					mysql_query($query) or die("Query failed : $query " . mysql_error());
				}
			}
		}
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/../index.php");
	}
	
	
	$placeinhead = "<style type=\"text/css\">\n@import url(\"$imasroot/forums/forums.css\");\n</style>\n";
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; New Forum Topics</div>\n";
	echo "<h3>New Forum Posts</h3>\n";
	echo "<p><a href=\"newthreads.php?cid=$cid&markallread=true\">Mark all Read</a></p>";

	if (count($lastpost)>0) {
		$threadids = implode(',',array_keys($lastpost));
		$query = "SELECT imas_forum_posts.*,imas_users.LastName,imas_users.FirstName FROM imas_forum_posts,imas_users ";
		$query .= "WHERE imas_forum_posts.userid=imas_users.id AND imas_forum_posts.threadid IN ($threadids) AND imas_forum_posts.parent=0 ORDER BY imas_forum_posts.forumid";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());

		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			if ($forumname[$line['threadid']]!=$lastforum) {
				if ($lastforum!='') { echo '</tbody></table>';}
				echo "<h4>Forum: <a href=\"thread.php?cid=$cid&forum={$forumids[$line['threadid']]}\">".$forumname[$line['threadid']].'</a></h4><table class="forum"><thead><th>Topic</th><th>Last Post Date</th></thead><tbody>';
				$lastforum = $forumname[$line['threadid']];
			}
			if ($line['isanon']==1) {
				$name = "Anonymous";
			} else {
				$name = "{$line['LastName']}, {$line['FirstName']}";
			}
			echo "<tr><td><a href=\"posts.php?cid=$cid&forum={$forumids[$line['threadid']]}&thread={$line['threadid']}&page=-2\">{$line['subject']}</a></b>: $name</td>";
			echo "<td>{$lastpost[$line['threadid']]}</td></tr>";
		}
		echo '</ul>';
	} else {
		echo "No new posts";
	}
	require("../footer.php");
?>
