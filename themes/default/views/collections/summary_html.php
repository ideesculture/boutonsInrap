<?php
/* ----------------------------------------------------------------------
 * views/editor/collections/summary_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2018 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * ----------------------------------------------------------------------
 */
 	$t_item 				= $this->getVar('t_subject');
	$vn_item_id 			= $this->getVar('subject_id');
	
	$t_display 				= $this->getVar('t_display');
	$va_placements 			= $this->getVar("placements");
?>
	<div id="summary" style="clear: both;">
<?php
	if ($vs_display_select_html = $t_display->getBundleDisplaysAsHTMLSelect('display_id', array('onchange' => 'jQuery("#caSummaryDisplaySelectorForm").submit();',  'class' => 'searchFormSelector'), array('table' => $t_item->tableNum(), 'value' => $t_display->getPrimaryKey(), 'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__, 'user_id' => $this->request->getUserID(), 'restrictToTypes' => array($t_item->getTypeID()), 'context' => 'editor_summary'))) {
?>
		<div id="printLabelsButton">
			<a onclick="jQuery('#printLabelsDialog').toggle();">
				<i class="caIcon fa fa-tags fa-2x"></i>
			</a>
		</div>
		<div id="printButton">
			<a href="<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), "PrintSummary", array($t_item->PrimaryKey() => $t_item->getPrimaryKey()))?>">
				<?php print caNavIcon(__CA_NAV_ICON_PDF__, 2); ?>
			</a>
		</div>
<?php
			print caFormTag($this->request, 'Summary', 'caSummaryDisplaySelectorForm');
?>
			<div class='searchFormSelector' style='float: right;'>
<?php
				print _t('Display').': '.$vs_display_select_html;
?>
			</div>
			<input type="hidden" name="<?php print $t_item->PrimaryKey(); ?>" value="<?php print $vn_item_id; ?>"/>
		</form>
<?php
	}
?>
	<div id="title">
		<?php print $t_item->getLabelForDisplay(); ?>
	</div><!-- end title -->
	<table border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>
			<td valign="top" align="left" style="padding-right:10px;">
<?php
		foreach($va_placements as $vn_placement_id => $va_info) {
			$vs_class = "";
			if (!strlen($vs_display_value = $t_display->getDisplayValue($t_item, $vn_placement_id, array_merge(array('request' => $this->request), is_array($va_info['settings']) ? $va_info['settings'] : array())))) {
				if (!(bool)$t_display->getSetting('show_empty_values')) { continue; }
				$vs_display_value = "&lt;"._t('not defined')."&gt;";
				$vs_class = " notDefined";
			}
			print "<div class=\"unit".$vs_class."\"><span class=\"heading".$vs_class."\">".$va_info['display'].":</span> ".$vs_display_value."</div>\n";
		}



		if ($t_item->get('ca_collections.children.collection_id')) {
			print "<div class='heading' style='margin-bottom:10px;'>"._t("%1 contents", $t_item->get('ca_collections.type_id', array('convertCodesToDisplayText' => true)))."</div>";
			//
			if (
				(!is_array($va_sort_fields = $t_item->getAppConfig()->get('ca_collections_hierarchy_summary_sort_values')) && !sizeof($va_sort_fields))
				&&
				(!is_array($va_sort_fields = $t_item->getAppConfig()->get('ca_collections_hierarchy_browser_sort_values')) && !sizeof($va_sort_fields))
			) {
				$va_sort_fields = ['ca_collections.preferred_labels.name'];
			}
			if(
				!($vs_template = $t_item->getAppConfig()->get('ca_collections_hierarchy_summary_display_settings'))
				&&
				!($vs_template = $t_item->getAppConfig()->get('ca_collections_hierarchy_browser_display_settings'))
			) {
				$vs_template = "<l>^ca_collections.preferred_labels.name</l> (^ca_collections.idno)";
			}
			
			$va_hierarchy = $t_item->hierarchyWithTemplate($vs_template, array('collection_id' => $vn_item_id, 'sort' => $va_sort_fields, 'objectTemplate' => $t_item->getAppConfig()->get('ca_objects_hierarchy_summary_display_settings')));
			foreach($va_hierarchy as $vn_i => $va_hierarchy_item) {
				$vs_margin = $va_hierarchy_item['level']*20;
				print "<div style='margin-left:".$vs_margin."px;margin-bottom:10px;'><i class='fa fa-angle-right' ></i> ".$va_hierarchy_item['display']."</div>";
			}
		}
?>
			</td>
			</td>
		</tr>
	</table>
		<div class="buttonsInrap">Ajouter
			<button>mouvement</button>
			<button>mobilier</button>
			<button>document</button>
			<button>prélévement</button>
			<button>valorisation</button>
			<button>contenant</button>
			<img src="/providence/loading.gif" style="height: 30px;display:none;" align="absmiddle" id="loading">
		</div>
		<div id="buttonsResult"></div>
</div><!-- end summary -->


<div id="printLabelsDialog">
	<div style="display: inline;float:right"><i class="caIcon fa fa-close" onClick="$('#printLabelsDialog').hide();"></i></div>
	<table>
		<form method="post" action="<?php print caNavUrl($this->request, "etatsInrap", "PrintLabels", "Generate", array($t_item->PrimaryKey() => $t_item->getPrimaryKey())); ?>">
	Format d'étiquettes : <select name="format_etiquette" class="searchFormSelector" id="format_etiquette">

				<option value="1" selected="selected" id="select_1" data-nb="10">inrap 10 vues</option>
				<option value="2" id="select_2" data-nb="16">inrap 16 vues</option>
				<option value="3" id="select_3" data-nb="24">inrap 24 vues</option>
	</select><br/>
		Nombre : <input type="text" name="nombre" id="nombre" width="20" value="10"><br/>
		<input type="hidden" name="display" value="<?php print $t_display->getPrimaryKey(); ?>">
		<input type="hidden" name="collection" value="<?php print $vn_item_id; ?>">
		<button type="submit">Générer</button><br/>
		</form>

	</table>
</div>
<?php
		TooltipManager::add('#printButton', _t("Download Summary as PDF"));
		TooltipManager::add('a.downloadMediaContainer', _t("Download Media"));
		TooltipManager::add('#printLabelsButton', "Etiquettes terrain");

?>
<style>
	#printLabelsButton {
		display: inline-block;
		float:right;
		width: 20px;
		margin: 0px 5px 5px 0px;

	}
	#printLabelsButton a,
	#printLabelsButton a i {
		cursor:pointer;
	}
	#printLabelsDialog {
		border:2px solid #ddd;
		background-color: white;
		width:50%;
		position: absolute;
		top:120px;
		margin-left:24%;
		z-index:4000;
		padding:20px;
		display: none;
	}
	.buttonsInrap button {
		background-color:#1ab3c8;
		border-radius: 6px;
		padding:8px;
		margin-right: 10px;
		color:white;
		border:none;
	}

</style>
<script>
	$( "#format_etiquette" ).change(function() {
		value = $("#format_etiquette").val();
		$('#nombre').val($('#format_etiquette OPTION#select_'+value).data("nb"));
	});
	$(".buttonsInrap button").on("click", function() {
	    $("#loading").show();
	    var bouton = $(this).html();
		$.ajax("<?php print __CA_URL_ROOT__."/index.php/boutonsInrap/Generer/Creer/type/"; ?>"+bouton+"/collection/<?php print $vn_item_id; ?>",
			{
			    "dataType": "json",
			    "success": function(data) {
			        console.log(data);
					if(data.result == 1) {
					    $("#buttonsResult").html("<p>Le "+bouton+" <i class=\"caIcon fa fa-file editIcon\"></i> <a target='_blank' href='<?php print __CA_URL_ROOT__; ?>/index.php/editor/"+data.editoraction+"/"+data.editor+"/Edit/"+data.editorid+"/"+data.id+"'>"+data.idno+"</a> a été créé.</p>");
					} else {

					}
                    $("#loading").hide();
				},
				"error": function(error) {
			        console.log("error");
			        console.log(error.responseText);
                    $("#loading").hide();

				}
			}

		)
	});

</script>
