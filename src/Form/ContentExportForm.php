<?php

namespace Drupal\content_export_csv\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\content_export_csv\Controller\ContentExportController;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\StreamWrapper\PublicStream;

class ContentExportForm extends FormBase{
	public function getFormId(){
		return 'content_export_csv_form';
	}

	public function buildForm(array $form,FormStateInterface $form_state){
		$form['content_type_list'] = [
			'#title'=> $this->t('Content Type'),
			'#type'=> 'select',
			'#options'=> ContentExportController::getContentType(),
		];

		$entity_type_id = 'node';
 		$bundle = 'article';
		foreach (\Drupal::entityManager()->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
			if (!empty($field_definition->getTargetBundle())) {
				$bundleFields[$field_name] = $field_definition->getLabel();
			}
		}
		// print_r($bundleFields); die;
		$form['fields'] = [
			'#title'=> $this->t('Fields'),
			'#type'=> 'checkboxes',
			'#options'=> $bundleFields,
			'#required' => TRUE,
		];

		$form['export'] = [
			'#value'=> 'Export',
			'#type'=> 'submit'
		];

		return $form;
	}

	public function submitForm(array &$form,FormStateInterface $form_state){
		global $base_url;
		$nodeType = $form_state->getValue('content_type_list');
		$nodeSelectedfields = $form_state->getValue('fields');
		foreach ($nodeSelectedfields as $key => $value) {
			if(!empty($value)){
				$selectedFields[$value] = $value;
			}
		}
		$csvData = ContentExportController::getNodeCsvData($nodeType, $selectedFields);
		$private_path = PrivateStream::basepath();
		$public_path = PublicStream::basepath();
		$file_base = ($private_path) ? $private_path : $public_path;
		$filename = 'content_export'. time(). '.csv';
		$filepath = $file_base . '/' . $filename;
		$csvFile = fopen($filepath, "w");
		$fieldNames = implode(',',ContentExportController::getValidFieldList($nodeType ,$selectedFields));
		array_push($fieldNames, 'path_alias');
		fwrite($csvFile,$fieldNames . "\n");
		foreach($csvData as $csvDataRow){
			fwrite($csvFile,$csvDataRow . "\n");
		}
		fclose($csvFile);
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="'. basename($filepath) . '";');
		header('Content-Length: ' . filesize($filepath));
		readfile($filepath);
		unlink($filepath);
		exit;
	}
}
