<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Construct footer links
$link_items = array("<a href='http://www.project-redcap.org/' target='_blank' style='text-decoration:underline;font-size:11px;'>The REDCap Consortium</a>",
					"<a href='http://www.project-redcap.org/cite.php' target='_blank' style='text-decoration:underline;font-size:11px;'>Citing REDCap</a>");
foreach (explode("\n", $footer_links) as $value) 
{
	if (trim($value) != "") {
		list ($this_url, $this_text) = explode(",", $value, 2);
		$link_items[] = "<a href='" . trim($this_url) . "' target='_blank' style='-webkit-text-size-adjust:none;text-decoration:underline;font-size:11px;'>" . trim($this_text) . "</a>";
	}
	$link_items_html = implode(" &nbsp;|&nbsp; ", $link_items);
}


// Close main window div
?>
			</div>
			<div id="southpad">&nbsp;</div>
			<div id="south" class="notranslate">
				<table cellpadding=0 cellspacing=0 width=100%>
					<tr>
						<td valign="middle" style="font-size:11px;color:#555;padding:6px 10px 0 10px;">
							<?php echo $link_items_html ?><br>
							<?php echo $footer_text ?>
						</td>
						<td valign="middle" style="color:#888;font-size:11px;text-align:right;padding:0 20px 0 0;">
							REDCap Software - Version <?php echo $redcap_version ?> - &copy; <?php echo date("Y") ?> Vanderbilt University
						</td>
					</tr>
				</table>		
			</div>
		</td>
	</tr>
</table>

</body>
</html>