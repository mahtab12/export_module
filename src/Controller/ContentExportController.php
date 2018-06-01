<?php
namespace Drupal\content_export_csv\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use \Drupal\node\Entity\Node;

class ContentExportController extends ControllerBase{
	public function getContentType(){
		$contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
		$contentTypesList = [];
		foreach ($contentTypes as $contentType) {
    		$contentTypesList[$contentType->id()] = $contentType->label();
		}
		return $contentTypesList;
	}


	function getNodeIds($nodeType){
		\Drupal::logger('node-type')->notice($nodeType);
		$entityQuery = \Drupal::entityQuery('node');
		$entityQuery->condition('status',1);
		$entityQuery->condition('type',$nodeType);
		$entityIds = $entityQuery->execute();
		return $entityIds;
	}

	function getNodeDataList($entityIds,$nodeType, $selectedFields){
		$nodeData = Node::loadMultiple($entityIds);
 		foreach($nodeData as $nodeDataEach){
 			$nodeCsvData[] = implode(',',ContentExportController::getNodeData($nodeDataEach,$nodeType,$selectedFields));
 		}
 		return $nodeCsvData;
	}

	function getValidFieldList($nodeType , $selectedFields){
		$nodeArticleFields = \Drupal::entityManager()->getFieldDefinitions('node',$nodeType);

		$nodeFields = array_keys($nodeArticleFields);
		$selectedFields = array_keys($selectedFields);
		array_push($selectedFields, "nid","type","status","title","uid",'created','changed','path');
		// $unwantedFields= array('comment','sticky','revision_default','revision_translation_affected','revision_timestamp','revision_uid','revision_log','vid','uuid','promote');

		// foreach($unwantedFields as $unwantedField){
		// 	$unwantedFieldKey = array_search($unwantedField,$nodeFields);
		// 	unset($nodeFields[$unwantedFieldKey]);
		// }
		return $selectedFields;
	}


	function getNodeData($nodeObject,$nodeType,$selectedFields){
		$nodeData = array();
		$nodeFields = ContentExportController::getValidFieldList($nodeType,$selectedFields);
		// kint($nodeObject); die;
		foreach($nodeFields as $nodeField){
			$nodeData[] = (isset($nodeObject->{$nodeField}->value)) ? '"' . htmlspecialchars(strip_tags($nodeObject->{$nodeField}->value)) . '"': ((isset($nodeObject->{$nodeField}->target_id)) ? '"' . htmlspecialchars(strip_tags($nodeObject->{$nodeField}->target_id)) . '"' : '"' . htmlspecialchars(strip_tags($nodeObject->{$nodeField}->langcode)) . '"');

		}
		$nodeData[] = $nodeObject->toUrl()->toString();

		return $nodeData;
	}

	function getNodeCsvData($nodeType , $selectedFields){
		$entityIds = ContentExportController::getNodeIds($nodeType);
		$nodeCsvData = ContentExportController::getNodeDataList($entityIds,$nodeType , $selectedFields);
		return $nodeCsvData;
	}
}
