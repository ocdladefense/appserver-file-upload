<?php

class DocumentsComponent extends Presentation\Component {


	public $active = true;

	private $linkedEntityIds;



	public function __construct($name, $params) {
		
		parent::__construct($name, $params);	

		// $input = $this->getInput();
	}


    public function getStyles() {
        return array(
            "active" => true,
            "href" => module_path() . "/components/documents/main.css?bust=001"
        );
    }

    public function getScripts() {
        return array(
            "src" => module_path() . "/components/documents/main.js?bust=001"
        );
    }


	/**
	 * Revise this to print the HTML table of documents for *either documents that are mine *or documents that are shared with me.  BUT NOT BOTH.
	 */
	public function toHtml($params = array()) {

		$user = current_user();
		$contactId = $user->getContactId();


		if(true) {
			$sharing = FileService::getUserSharePoints($user);
		} else {
			$sharing = $params;
		}

		$service = new FileService($sharing);
		$service->setContactId($contactId);

		// Possible sharing targets for the current user.
		// These usually include the contactId, accountId and any committeeIds.
		// $sharePoints = $service->getSharePoints();

		// The actual shared documents.
		$targets = $service->getSharingTargets();



		// If no documents, then display accordingly.
		if($targets->count() == 0) {
			$tpl = new Template("no-records");
			$tpl->addPath(__DIR__ . "/templates");
			return $tpl;
		}

		
		$service->loadAvailableDocuments();


		$docs = $service->getDocuments();

		/*
		$docs = $this->template == "my-documents" ?
			$service->getMyDocuments() : 
			$service->getDocumentsSharedWithMe();
		*/

		$salesforceUrl = cache_get("instance_url") . "/lightning/r/CombinedAttachment/$contactId/related/CombinedAttachments/view";

		// Template depends on the params that get passed into this function; or maybe the $id value that is passed into the "component()" function call.
		$tpl = new Template("documents");
		$tpl->addPath(__DIR__ . "/templates");

		return $tpl->render(["documents" => $docs, "contactUrl" => $salesforceUrl]);
	}


}