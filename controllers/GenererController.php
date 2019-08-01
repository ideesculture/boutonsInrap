<?php
/* ----------------------------------------------------------------------
 * plugins/statisticsViewer/controllers/StatisticsController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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
 
	define("__INRAP_TYPE_ID_OP__", 125);

 	require_once(__CA_LIB_DIR__.'/core/TaskQueue.php');
 	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
	require_once(__CA_LIB_DIR__.'/ca/Search/CollectionSearch.php');
	require_once(__CA_LIB_DIR__.'/ca/Browse/CollectionBrowse.php');
	require_once(__CA_LIB_DIR__.'/ca/Search/ObjectSearch.php');
	require_once(__CA_LIB_DIR__.'/ca/Browse/ObjectBrowse.php');
 	require_once(__CA_MODELS_DIR__.'/ca_lists.php');
 	require_once(__CA_MODELS_DIR__.'/ca_objects.php');
 	require_once(__CA_MODELS_DIR__.'/ca_object_representations.php');
 	require_once(__CA_MODELS_DIR__.'/ca_locales.php');

	require_once(__CA_LIB_DIR__.'/ca/ResultContext.php');

 	error_reporting(E_ERROR);

 	class GenererController extends ActionController {
 		# -------------------------------------------------------
  		protected $opo_config,		// plugin configuration file
        $ops_plugin_name, $ops_plugin_path,
		$ops_user_groups, $opo_result_context;


 		# -------------------------------------------------------
 		# Constructor
 		# -------------------------------------------------------

 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			global $allowed_universes;
 			
 			parent::__construct($po_request, $po_response, $pa_view_paths);

 			$this->ops_plugin_name = "boutonsInrap";
 			$this->ops_plugin_path = __CA_APP_DIR__."/plugins/".$this->ops_plugin_name;

 			$vs_conf_file = $this->ops_plugin_path."/conf/".$this->ops_plugin_name.".conf";
 			if(is_file($vs_conf_file)) {
                $this->opo_config = Configuration::load($vs_conf_file);
            }

			$va_groups = $this->getRequest()->getUser()->getUserGroups();
			$this->ops_user_groups = [];
 			foreach($va_groups as $group) {
 				if(in_array($group["code"], ["gestion","admin"])) continue;
				$this->ops_user_groups[] =$group["code"];
			}

 		}

		# -------------------------------------------------------
		# Functions to render widgets
		# -------------------------------------------------------

		public function Index($type="") {
			$this->view->setvar("deplist", null);
			$this->render('index_html.php');
		}

		public function Creer() {
 			$type = $this->getRequest()->getParameter("type", pString);
 			$collection_id = $this->getRequest()->getParameter("collection", pInteger);
 			if(!$collection_id) {
 				$object_id = $this->getRequest()->getParameter("objet", pInteger);
 				if(!$object_id) return false;
			} else {
				$vt_coll = new ca_collections($collection_id);
			}

 			switch ($type) {
				case "mouvement":
					$vs_table = "ca_movements";
					$type_id = 81;
					$editor = "MovementEditor";
					$editorid = "movement_id";
					$editoraction = "movements";
					$collection_relation_type_id = 81;
					$objet_relation_type_id = 16;
					break;
				case "mobilier":
					$vs_table = "ca_objects";
					$type_id = 24;
					$editor = "ObjectEditor";
					$editorid = "object_id";
					$editoraction = "objects";
					$collection_relation_type_id = 152;
					$objet_relation_type_id = 177;
					break;
				case "document":
					$vs_table = "ca_objects";
					$type_id = 26;
					$editor = "ObjectEditor";
					$editorid = "object_id";
					$editoraction = "objects";
					$collection_relation_type_id = 152;
					$objet_relation_type_id = 177;

					break;
				case "prélévement":
					$vs_table = "ca_objects";
					$type_id = 25;
					$editor = "ObjectEditor";
					$editorid = "object_id";
					$editoraction = "objects";
					$collection_relation_type_id = 152;
					$objet_relation_type_id = 177;
					break;
				case "valorisation":
					$vs_table = "ca_objects";
					$type_id = 27;
					$editor = "ObjectEditor";
					$editorid = "object_id";
					$editoraction = "objects";
					$collection_relation_type_id = 152;
					$objet_relation_type_id = 177;
					break;
				case "contenant":
					$vs_table = "ca_objects";
					$type_id = 28;
					$editor = "ObjectEditor";
					$editorid = "object_id";
					$editoraction = "objects";
					$collection_relation_type_id = 152;
					$objet_relation_type_id = 177;
					break;
				default:
					// No default case, exit in that case
					print json_encode(["result"=>0,"errors"=>"unknown type"]);
					die();
			}
 			$vt_create = new $vs_table();
			//$vt_create= new ca_objects();
 			// TODO : récupérer depuis un paramètre dans le .conf
 			$vt_create->set("type_id", $type_id);
			$vt_create->set("locale_id", 2);
			$vt_create->set("access", 2);
			$vt_create->set("status", 1);

			// IDNO equivalent
			$o_data = new Db();
			$qr_result = $o_data->query("SELECT idno FROM $vs_table WHERE deleted=0 and idno regexp '^[0-9]+$' ORDER BY idno DESC LIMIT 1");
			$result = reset($qr_result->getAllRows());
			$idno = $result["idno"]*1 + 1;
			$vt_create->set("idno", $idno);

			$vt_create->setMode(ACCESS_WRITE);
			$vt_create_id = $vt_create->insert();
			if($collection_id) {
				$vt_create->addRelationship("ca_collections", $collection_id, $collection_relation_type_id );
				$vt_create->update();
			}
			if($object_id){
				$vt_create->addRelationship("ca_objects", $object_id, $objet_relation_type_id );
				$vt_create->update();
				$vt_object = new ca_objects($object_id);
				$collection_id = $vt_object->get("ca_collections.collection_id");
				// Next relation is between the thing to be created and the collection the object is linked too
				$vt_create->addRelationship("ca_collections", $collection_id, $collection_relation_type_id );
			}

			if(!$vt_create->numErrors()) {
				print json_encode(["result"=>1,"id"=>$vt_create_id, "idno"=>$idno, "editor"=>$editor, "editorid"=>$editorid, "editoraction"=>$editoraction]);
			} else {
				print json_encode(["result"=>0,"errors"=>var_export($vt_create->getErrors(), true)]);
			}


 			die();
		}

 	}
 ?>
