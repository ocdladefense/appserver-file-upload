<?php

use Mysql\DbHelper;
use Salesforce\ContentDocument;

class FileService {

	private $linkedEntityIds = array();


	public function __construct($linkedEntityIds = array()) {

		$this->linkedEntityIds = $linkedEntityIds;
	}


	// Get the ids for the user's contact, account, and any committees the user is a member of.
	public static function getUserAssociatedEntityIds($user = null) {

		$user = empty($user) ? current_user() : $user;

		$contactId = $user->getContactId();
		$accountId = $user->query("Contact.AccountId");

		$associatedIds = [$contactId, $accountId];

		// Get the Id of any committees the user is associated with.
		$api = loadApi();
		$format = "SELECT Committee__c FROM Relationship__c WHERE Contact__c = '%s'";
		$query = sprintf($format, $contactId);
		$records = $api->query($query)->getRecords();

		foreach($records as $rec) $associatedIds[] = $rec["Committee__c"];

		return $associatedIds;
	}




	// Return an array of "Salesforce\ContentDocument" objects.
	public function getDocuments() {

		// Get the ContentDocumentLinks with all of the ContentDocument data.
		$format = "SELECT Id, ContentDocument.Title, ContentDocument.ContentSize, ContentDocument.FileType, ContentDocument.FileExtension, ContentDocumentId, LinkedEntityId FROM ContentDocumentLink WHERE LinkedEntityId IN (:array)";
		$query = DbHelper::parseArray($format, $this->linkedEntityIds);
		$resp = loadApi()->query($query);
		$result = $resp->getQueryResult();

		if($result->count() == 0) return [];

		$data = $result->group(function($link){ return $link["ContentDocumentId"];});

		$ids = $result->getField("ContentDocumentId");

		// All of the linked entities for all of the documents
		$format = "SELECT ContentDocumentId, LinkedEntityId FROM ContentDocumentLink WHERE ContentDocumentId IN (:array)";
		$query = DbHelper::parseArray($format, $ids);
		$result = loadApi()->query($query)->getQueryResult();

		$grouped = $result->group(function($link){ return $link["ContentDocumentId"];});

		$docs = array();

		// It all got grouped by ContentDocumentId, above.
		foreach($grouped as $id => $links) {

			$doc = new ContentDocument($id);
			// Only need the first element since all elements are the same with the exception of the LinkedEntityIds.
			// We are passing the linked entity data in seperatly.
			$doc->setDocumentData($data[$id][0]["ContentDocument"]);
			$doc->setLinkedEntities($links);
			$docs []= $doc;
		}

		return $docs;
	}




	// I think we need this function in core.
	public static function getEntityName($id) {

		$sObjectType = self::getSobjectType($id);
		$query = "SELECT Name FROM $sObjectType WHERE Id = '$id'";
		
		return loadApi()->query($query)->getRecord()["Name"];
	}


	// I think we need a more complete version of this function in core.
	public static function getSobjectType($id) {

		$prefix = substr($id, 0, 3);

		switch ($prefix) {
			case 'a2G':
				return "Committee__c";
				break;
			case '005':
				return "User";
				break;
			case '003':
				return "Contact";
				break;
			case '001':
				return "Account";
				break;
			default:
				throw new Exception("NO SOBJECT TYPE FOUND FOR PREFIX $prefix");
				break;
		}
	}


	public function downloadContentDocument($id) {

		$api = $this->loadForceApi();

		$query = "SELECT VersionData, Title FROM ContentVersion WHERE ContentDocumentId = '$id' AND IsLatest = True";

		$version = $api->query($query)->getRecord();
		$versionUrl = $version["VersionData"];

		$api2 = $this->loadForceApi();
		$resp = $api2->send($versionUrl);

		$file = new File($version["Title"]);
		$file->setContent($resp->getBody());
		$file->setType($resp->getHeader("Content-Type"));

		return $file;
	}
}